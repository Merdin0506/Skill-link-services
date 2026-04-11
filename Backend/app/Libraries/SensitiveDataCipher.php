<?php

namespace App\Libraries;

class SensitiveDataCipher
{
    public function encrypt(?string $value): ?string
    {
        $value = $this->normalizeNullable($value);
        if ($value === null) {
            return null;
        }

        try {
            return base64_encode(service('encrypter')->encrypt($value));
        } catch (\Throwable) {
            return $value;
        }
    }

    public function decrypt(?string $value): ?string
    {
        $value = $this->normalizeNullable($value);
        if ($value === null) {
            return null;
        }

        $decoded = base64_decode($value, true);
        if ($decoded === false) {
            return $value;
        }

        try {
            return service('encrypter')->decrypt($decoded);
        } catch (\Throwable) {
            return $value;
        }
    }

    public function phoneLastFour(?string $value): ?string
    {
        $value = $this->normalizeNullable($value);
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value);
        if ($digits === null || $digits === '') {
            return null;
        }

        return substr($digits, -4);
    }

    private function normalizeNullable(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
