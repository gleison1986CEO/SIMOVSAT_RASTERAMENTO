<?php namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\View;

class LanguageController extends Controller
{
    public function index()
    {
        $languages = array_sort(settings('languages'), function ($language) {
            return $language['title'];
        });

        $languages = array_filter($languages, function ($language) {
            return $language['active'];
        });

        return View::make('front::Languages.index', compact('languages'));
    }
}