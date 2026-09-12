<?php

namespace App\Services;

use App\Models\Content;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ContentPasswordAccess
{
    public function grant(Content $content, Request $request): void
    {
        $data = $request->validate(['password' => ['required', 'string', 'max:128']]);
        $hash = data_get($content->access_rules, 'password_hash');
        if (! is_string($hash) || ! Hash::check($data['password'], $hash)) {
            throw ValidationException::withMessages(['password' => '内容密码不正确。']);
        }
        Cache::store('file')->put($this->key($content, $request->user()), true, now()->addHour());
    }

    public function allows(Content $content, ?User $user): bool
    {
        return filled(data_get($content->access_rules, 'password_hash')) && Cache::store('file')->get($this->key($content, $user), false);
    }

    private function key(Content $content, ?User $user): string
    {
        $identity = $user ? 'user:'.$user->id : (request()->hasSession() ? 'session:'.request()->session()->getId() : 'unavailable:'.bin2hex(random_bytes(12)));

        return 'zfy.content-password.'.hash('sha256', $content->id.'|'.data_get($content->access_rules, 'password_hash').'|'.$identity);
    }
}
