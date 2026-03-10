<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class RequirementsController extends Controller
{
    /**
     * Display the Final Project Requirements page.
     */
    public function __invoke(): View
    {
        return view('requirements.index', [
            'requirements' => config('bhcas.requirements'),
            'pricingTiers' => config('bhcas.pricing_tiers'),
            'appName' => config('bhcas.name'),
            'acronym' => config('bhcas.acronym'),
            'subtitle' => config('bhcas.subtitle'),
        ]);
    }
}
