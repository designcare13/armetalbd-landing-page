<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class Setting extends Model
{
    use HasFactory;

    /**
     * Stored encrypted and never part of allWithDefaults(), so they cannot leak into a view by accident.
     *
     * @var array<int, string>
     */
    public const SECRET_KEYS = ['meta_capi_token'];

    protected $fillable = ['key', 'value'];

    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();

        return $setting ? $setting->value : $default;
    }

    public static function set(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function setSecret(string $key, ?string $value): void
    {
        static::set($key, blank($value) ? '' : Crypt::encryptString($value));
    }

    /**
     * The decrypted secret, or null when it is missing or was saved under a different app key.
     */
    public static function secret(string $key): ?string
    {
        $stored = static::get($key);

        if (blank($stored)) {
            return null;
        }

        try {
            return Crypt::decryptString($stored);
        } catch (DecryptException) {
            return null;
        }
    }

    /**
     * Every landing-page setting: schema defaults overridden by saved values.
     *
     * @return array<string, string>
     */
    public static function allWithDefaults(): array
    {
        $values = Arr::except(static::query()->pluck('value', 'key')->all(), self::SECRET_KEYS);
        $merged = [];

        foreach (static::schemaFields() as $key => $field) {
            $hasValue = array_key_exists($key, $values) && $values[$key] !== null && $values[$key] !== '';
            $keepsBlank = $field['type'] !== 'image' && array_key_exists($key, $values) && $values[$key] !== null;

            $merged[$key] = $hasValue || $keepsBlank
                ? (string) $values[$key]
                : (string) ($field['default'] ?? '');
        }

        return $merged + $values;
    }

    /**
     * Flat list of every field declared in config/landing.php.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function schemaFields(): array
    {
        $fields = [];

        foreach (config('landing.groups') as $group) {
            $fields += $group['fields'];
        }

        return $fields;
    }

    /**
     * Turn a stored image value (uploaded path or external URL) into a public URL.
     */
    public static function imageUrl(?string $path, ?string $fallback = null): ?string
    {
        if (blank($path)) {
            return $fallback;
        }

        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        return asset('uploads/'.ltrim($path, '/'));
    }

    /**
     * Delete a previously uploaded image from disk. External URLs are ignored.
     */
    public static function deleteImage(?string $path): void
    {
        if (blank($path) || preg_match('#^https?://#i', $path)) {
            return;
        }

        Storage::disk(config('landing.image_disk'))->delete($path);
    }
}
