<?php

namespace App\Services;

use App\Models\ExamQuestion;
use App\Models\User;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * One image per question, shown above the question text. PNG, JPG or WebP only (no SVG).
 * The image is decoded and written again, which drops metadata (such as location in a phone photo)
 * and anything hidden in the file. It is stored on the private disk and only streamed to people the
 * exam policy allows. Audit values hold ids and flags only.
 */
class ExamImageService
{
    public const MAX_WIDTH = 1600;
    public const MAX_PIXELS = 16000000;

    private const DISK = 'local';

    public function store(User $user, ExamQuestion $question, UploadedFile $file, ?string $alt): ExamQuestion
    {
        $exam = $question->set?->exam;
        if (! $exam) {
            $this->fail('question', 'This question is not available.');
        }

        [$bytes, $ext] = $this->clean($file);

        $disk = Storage::disk(self::DISK);
        $path = "exam-images/{$exam->id}/q{$question->id}-" . Str::random(16) . ".{$ext}";
        $old = $question->image_path;
        $alt = $alt !== null && trim($alt) !== '' ? trim($alt) : null;

        if (! $disk->put($path, $bytes)) {
            $this->fail('image', 'The image could not be stored.');
        }

        try {
            DB::transaction(function () use ($user, $question, $exam, $path, $alt) {
                $question->image_path = $path;
                $question->image_alt = $alt;
                $question->save();

                AuditLogger::log($user, $exam, 'image_set', null, [
                    'question_id' => $question->id,
                    'has_alt' => $alt !== null,
                ]);
            });
        } catch (Throwable $e) {
            $disk->delete($path); // do not leave an orphan file behind
            throw $e;
        }

        if ($old) {
            $disk->delete($old);
        }

        return $question;
    }

    public function remove(User $user, ExamQuestion $question): void
    {
        $exam = $question->set?->exam;
        $path = $question->image_path;
        if (! $exam || $path === null) {
            return;
        }

        DB::transaction(function () use ($user, $question, $exam) {
            $question->image_path = null;
            $question->image_alt = null;
            $question->save();

            AuditLogger::log($user, $exam, 'image_removed', null, ['question_id' => $question->id]);
        });

        Storage::disk(self::DISK)->delete($path);
    }

    /** The stored file as a response, or null when there is none. */
    public function response(ExamQuestion $question)
    {
        $path = $question->image_path;
        $disk = Storage::disk(self::DISK);
        if ($path === null || ! $disk->exists($path)) {
            return null;
        }

        return $disk->response($path, null, [
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    // ---------------------------------------------------------------- internals

    /** Reads the real content (not the file name), checks the type and size, and returns clean bytes. */
    private function clean(UploadedFile $file): array
    {
        $raw = @file_get_contents($file->getRealPath());
        $info = $raw === false ? false : @getimagesizefromstring($raw);

        $types = [IMAGETYPE_PNG => 'png', IMAGETYPE_JPEG => 'jpg', IMAGETYPE_WEBP => 'webp'];
        if (! $info || ! isset($types[$info[2]])) {
            $this->fail('image', 'Upload a PNG, JPG or WebP image.');
        }
        if ($info[0] * $info[1] > self::MAX_PIXELS) {
            $this->fail('image', 'That image is too large. Use one under 16 megapixels.');
        }

        $img = @imagecreatefromstring($raw);
        if (! $img instanceof GdImage) {
            $this->fail('image', 'That image could not be read.');
        }

        $ext = $types[$info[2]];
        if ($ext === 'jpg') {
            $img = $this->orient($img, $file->getRealPath());
        }

        if (imagesx($img) > self::MAX_WIDTH) {
            $scaled = imagescale($img, self::MAX_WIDTH);
            if ($scaled instanceof GdImage) {
                $img = $scaled;
            }
        }

        if ($ext !== 'jpg') {
            imagealphablending($img, false);
            imagesavealpha($img, true);
        }

        ob_start();
        $ok = match ($ext) {
            'png' => imagepng($img, null, 6),
            'jpg' => imagejpeg($img, null, 85),
            'webp' => imagewebp($img, null, 85),
        };
        $bytes = ob_get_clean();

        if (! $ok || ! is_string($bytes) || $bytes === '') {
            $this->fail('image', 'That image could not be processed.');
        }

        return [$bytes, $ext];
    }

    /** Phone photos carry a rotation flag in metadata; apply it before the metadata is dropped. */
    private function orient(GdImage $img, string $path): GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $img;
        }

        $exif = @exif_read_data($path);
        $angle = [3 => 180, 6 => -90, 8 => 90][$exif['Orientation'] ?? 1] ?? 0;
        if ($angle === 0) {
            return $img;
        }

        $rotated = imagerotate($img, $angle, 0);

        return $rotated instanceof GdImage ? $rotated : $img;
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => [$message]]);
    }
}