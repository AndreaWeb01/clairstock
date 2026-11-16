<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class SettingService
{
    private ?array $values = null;

    public const DEFAULTS = [
        'business_name' => 'ClairStock', 'business_phone' => '', 'business_email' => '',
        'business_address' => '', 'business_country' => '', 'currency' => 'FCFA',
        'date_format' => 'd/m/Y', 'default_minimum_stock' => '0.000', 'business_logo_path' => '',
    ];

    public function all(): array
    {
        return $this->values ??= array_replace(self::DEFAULTS, Setting::query()->pluck('value', 'key')->all());
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function update(array $values): void
    {
        DB::transaction(function () use ($values) {
            foreach ($values as $key => $value) {
                Setting::query()->updateOrCreate(['key' => $key], ['value' => (string) ($value ?? '')]);
            }
        });
        $this->values = null;
    }
}
