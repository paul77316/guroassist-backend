<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SetupResource;
use App\Models\User;
use App\Services\SetupService;
use Illuminate\Database\Eloquent\Model;

abstract class SetupController extends Controller
{
    public function __construct(protected SetupService $service) {}

    abstract protected function model(): string;

    abstract protected function listQuery();

    public function index()
    {
        $this->authorize('viewAny', $this->model());

        return SetupResource::collection($this->listQuery()->get());
    }

    protected function storeItem(array $data, User $user)
    {
        return (new SetupResource($this->service->create($this->model(), $data, $user)))
            ->response()->setStatusCode(201);
    }

    protected function updateItem(Model $item, array $data, User $user)
    {
        return new SetupResource($this->service->update($item, $data, $user));
    }

    protected function destroyItem(Model $item, User $user)
    {
        $this->authorize('delete', $item);
        $this->service->delete($item, $user);

        return response()->noContent();
    }
}