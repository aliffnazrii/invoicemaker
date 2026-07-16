<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class CompanySettings
{
    public const LOGO_WIDTH = 200;

    public const LOGO_HEIGHT = 200;

    public const LOGO_PATH = 'company/logo.png';

    public static function forInvoice(): array
    {
        return [
            'company_name'           => config('settings.company_name'),
            'company_extras'         => config('settings.company_extras'),
            'company_address_line_1' => config('settings.company_address_line_1'),
            'company_address_line_2' => config('settings.company_address_line_2'),
            'company_postal_code'    => config('settings.company_postal_code'),
            'company_city'           => config('settings.company_city'),
            'company_state'          => config('settings.company_state'),
            'company_phone'          => config('settings.company_phone'),
            'company_logo'           => self::logoBase64(),
        ];
    }

    public static function logoBase64(): ?string
    {
        $path = config('settings.company_logo');

        if (!$path || !Storage::disk('public')->exists($path)) {
            return null;
        }

        $fullPath = Storage::disk('public')->path($path);
        $mime = mime_content_type($fullPath) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fullPath));
    }

    public static function logoUrl(?string $path = null): ?string
    {
        $path = $path ?: config('settings.company_logo');

        if (!$path || !Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    public static function saveLogoFromBase64(string $base64): string
    {
        if (str_contains($base64, ',')) {
            $base64 = explode(',', $base64, 2)[1];
        }

        Storage::disk('public')->makeDirectory('company');
        Storage::disk('public')->put(self::LOGO_PATH, base64_decode($base64));

        return self::LOGO_PATH;
    }

    public static function deleteLogo(): void
    {
        $path = config('settings.company_logo') ?: self::LOGO_PATH;

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
