<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Serves the static informational pages. Using explicit controller methods
 * (rather than route closures) allows the routes to be cached in production.
 */
class PageController extends Controller
{
    public function orderSuccess(): View
    {
        return view('order_success');
    }

    public function privacyPolicy(): View
    {
        return view('privacy-policy');
    }

    public function termsAndConditions(): View
    {
        return view('terms-and-conditions');
    }

    public function faq(): View
    {
        return view('faq');
    }

    public function storePolicies(): View
    {
        return view('store-policies');
    }

    /**
     * GET endpoint used to confirm the Maya webhook route is reachable.
     */
    public function mayaWebhook(): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }
}
