<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function __invoke(Request $request, string $locale)
    {
        abort_unless(in_array($locale, ['en', 'ar']), 404);
        $request->session()->put('locale', $locale);

        return back();
    }
}
