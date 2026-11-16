<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Models\Purchase;
use App\Models\Sale;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;

class PaymentController extends Controller
{
    public function storeSale(StorePaymentRequest $request, Sale $sale, PaymentService $service): RedirectResponse
    {
        $service->record($sale, $request->validated(), $request->user());
        return back()->with('status', 'Paiement client enregistré. Le solde a été recalculé.');
    }

    public function storePurchase(StorePaymentRequest $request, Purchase $purchase, PaymentService $service): RedirectResponse
    {
        $service->record($purchase, $request->validated(), $request->user());
        return back()->with('status', 'Paiement fournisseur enregistré. Le solde a été recalculé.');
    }
}
