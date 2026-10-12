<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Setting extends Model
{
    use HasFactory;

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

    /**
     * Every landing-page setting: schema defaults overridden by saved values.
     *
     * @return array<string, string>
     */
    public static function allWithDefaults(): array
    {
        $values = static::query()->pluck('value', 'key')->all();
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
