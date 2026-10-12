<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(): View
    {
        return view('admin.settings.index', [
            'groups' => config('landing.groups'),
            'tabs' => config('landing.tabs'),
            'settings' => Setting::allWithDefaults(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $fields = Setting::schemaFields();

        $validated = $request->validate($this->rules($fields), $this->messages($fields));

        foreach ($fields as $key => $field) {
            if ($field['type'] === 'image') {
                $this->saveImageField($request, $key);

                continue;
            }

            if (array_key_exists($key, $validated)) {
                Setting::set($key, $this->normalize($field, $validated[$key]));
            }
        }

        return back()->with('success', 'সেটিংস সফলভাবে আপডেট করা হয়েছে।');
    }

    /**
     * @param  array<string, array<string, mixed>>  $fields
     * @return array<string, mixed>
     */
    private function rules(array $fields): array
    {
        $rules = [];

        foreach ($fields as $key => $field) {
            $fieldRules = $field['rules'];

            if ($field['type'] === 'image') {
                $fieldRules = 'nullable|image|'.$fieldRules;
            }

            $rules[$key] = $fieldRules;
        }

        return $rules + ['remove_image' => 'nullable|array', 'remove_image.*' => 'string'];
    }

    /**
     * @param  array<string, array<string, mixed>>  $fields
     * @return array<string, string>
     */
    private function messages(array $fields): array
    {
        $messages = [];

        foreach ($fields as $key => $field) {
            $label = $field['label'];

            if ($field['type'] === 'image') {
                $messages["{$key}.image"] = "{$label}: ফাইলটি অবশ্যই একটি ছবি হতে হবে।";
                $messages["{$key}.mimes"] = "{$label}: অনুমোদিত ফরম্যাট ছাড়া অন্য ফাইল দেওয়া যাবে না।";
                $messages["{$key}.max"] = "{$label}: ছবির সাইজ অনুমোদিত লিমিটের বেশি।";
                $messages["{$key}.dimensions"] = "{$label}: ছবির সাইজ (পিক্সেল) গ্রহণযোগ্য নয়।";
                $messages["{$key}.uploaded"] = "{$label}: ছবি আপলোড হয়নি। ফাইলের সাইজ সার্ভারের লিমিটের চেয়ে বড় হতে পারে।";

                continue;
            }

            $messages["{$key}.regex"] = "{$label}: ফরম্যাট সঠিক নয়।";
            $messages["{$key}.url"] = "{$label}: সঠিক লিংক দিন (https:// দিয়ে শুরু)।";
            $messages["{$key}.email"] = "{$label}: সঠিক ইমেইল ঠিকানা দিন।";
            $messages["{$key}.numeric"] = "{$label}: শুধু সংখ্যা দিন।";
            $messages["{$key}.max"] = "{$label}: অনেক বড় হয়ে গেছে।";
        }

        return $messages;
    }

    private function saveImageField(Request $request, string $key): void
    {
        $current = Setting::get($key);

        if ($request->hasFile($key)) {
            Setting::set($key, $this->storeImage($request->file($key), 'site'));
            Setting::deleteImage($current);

            return;
        }

        if (in_array($key, (array) $request->input('remove_image', []), true)) {
            Setting::deleteImage($current);
            Setting::set($key, '');
        }
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private function normalize(array $field, mixed $value): string
    {
        $value = trim((string) ($value ?? ''));

        if ($field['type'] === 'list') {
            $lines = array_filter(array_map('trim', preg_split('/\R/', $value)));

            return implode("\n", $lines);
        }

        return $value;
    }
}
