<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Endpoint HTTP notification Midtrans (POST /webhooks/midtrans).
 * Controller sengaja tipis: seluruh logika di PaymentService agar mudah
 * dites. Selalu 204/200 agar Midtrans tidak retry — kegagalan bisnis
 * ditangani di service (payment dibiarkan pending).
 */
class MidtransController extends Controller
{
    public function __invoke(Request $request, PaymentService $payments): Response
    {
        $payments->handleMidtransNotification($request->json()->all());

        return response()->noContent();
    }
}
