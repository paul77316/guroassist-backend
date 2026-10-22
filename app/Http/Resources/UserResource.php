<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'must_change_password' => $this->must_change_password,
            'initial_password_expires_at' => $this->initial_password_expires_at?->toIso8601String(),
            'initial_password_expired' => $this->must_change_password
                && (! $this->initial_password_expires_at || $this->initial_password_expires_at->isPast()),
        ];
    }
}
