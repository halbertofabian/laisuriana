<?php

namespace App\Services\Operacion;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class DeviceIdentityService
{
    public const COOKIE_NAME = 'laisuriana_device_id';

    public function resolve(Request $request): string
    {
        $current = trim((string) $request->cookie(self::COOKIE_NAME));

        if ($this->isValid($current)) {
            return $current;
        }

        return (string) Str::uuid();
    }

    public function queueCookie(string $deviceId, bool $secure = false): void
    {
        Cookie::queue(cookie(
            self::COOKIE_NAME,
            $deviceId,
            60 * 24 * 365 * 5,
            '/',
            null,
            $secure,
            true,
            false,
            'lax'
        ));
    }

    private function isValid(string $deviceId): bool
    {
        if ($deviceId === '') {
            return false;
        }

        return (bool) preg_match('/^[A-Z0-9-]{16,64}$/i', $deviceId);
    }
}
