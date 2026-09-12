<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    use HasFactory;

    public const DEFAULTS = [
        'shop_name' => 'Gundam Shop',
        'default_deposit_amount' => '200000',
        'default_deadline_days' => '14',
        'contact_address' => 'Hà Nội, Việt Nam',
        'contact_phone' => '0123 456 789',
        'contact_email' => 'info@gundamshop.vn',
    ];

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    public static function get(string $key, ?string $default = null): ?string
    {
        static $tableExists = null;
        $tableExists ??= Schema::hasTable('settings');

        if (! $tableExists) {
            return $default ?? self::DEFAULTS[$key] ?? null;
        }

        return Cache::rememberForever("settings.{$key}", function () use ($key, $default) {
            return self::where('key', $key)->value('value')
                ?? $default
                ?? self::DEFAULTS[$key]
                ?? null;
        });
    }

    public static function set(string $key, ?string $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("settings.{$key}");
    }
}
