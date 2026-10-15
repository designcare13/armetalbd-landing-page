<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\Seo;
use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $base = Seo::baseUrl(Setting::allWithDefaults());

        $rules = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /forgot-password',
            'Disallow: /reset-password',
            'Disallow: /*?buy=',
            '',
            "Sitemap: {$base}/sitemap.xml",
        ];

        return response(implode("\n", $rules)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
