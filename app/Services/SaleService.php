<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    use TransactionTotals;

    public function __construct(private readonly StockService $stock) {}

    public function create(array $data, User $user): Sale
    {
        return DB::transaction(function () use ($data, $user) {
            $sale = new Sale([
                'number' => 'VTE-'.now()->format('Ymd').'-'.strtoupper(bin2hex(random_bytes(3))),
                'customer_id' => $data['customer_id'] ?? null,
                'user_id' => $user->id,
                'sold_at' => $data['sold_at'] ?? now()->toDateString(),
                'status' => 'completed',
                'discount_amount' => $this->amount($this->cents($data['discount_amount'] ?? 0)),
                'payment_method' => $data['payment_method'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
            $sale->save();

            $subtotal = 0;
            $lines = [];
            foreach ($data['items'] as $line) {
                $product = Product::query()->where('status', 'active')->findOrFail($line['product_id']);
                $quantity = (string) $line['quantity'];
                $unitPrice = $this->cents($product->sale_price);
                $unitCost = $this->cents($product->purchase_price);
                $lineTotal = $this->lineAmount($unitPrice, $quantity);
                $subtotal += $lineTotal;
                if ($subtotal > 999_999_999_999_999) {
                    throw ValidationException::withMessages(['total' => 'Le total de la vente dépasse la limite autorisée.']);
                }
                $lines[] = [$product, $quantity, $unitPrice, $unitCost, $lineTotal];
            }

            $discount = $this->cents($data['discount_amount'] ?? 0);
            if ($discount > $subtotal) {
                throw ValidationException::withMessages(['discount_amount' => 'La remise ne peut pas dépasser le sous-total.']);
            }
            $total = $subtotal - $discount;
            $paid = $this->cents($data['amount_paid'] ?? 0);
            if ($paid > $total) {
                throw ValidationException::withMessages(['amount_paid' => 'Le montant payé ne peut pas dépasser le total de la vente.']);
            }

            foreach ($lines as [$product, $quantity, $unitPrice, $unitCost, $lineTotal]) {
                $sale->items()->create([
                    'product_id' => $product->id, 'product_name' => $product->name, 'sku' => $product->sku,
                    'quantity' => $quantity, 'unit_price' => $this->amount($unitPrice), 'unit_cost' => $this->amount($unitCost),
                    'line_total' => $this->amount($lineTotal),
                ]);
                $this->stock->record($product, 'sale', $quantity, $user, 'Vente '.$sale->number, $sale);
            }

            if ($paid > 0) {
                $sale->payments()->create([
                    'user_id' => $user->id, 'amount' => $this->amount($paid),
                    'method' => $sale->payment_method ?: 'other', 'paid_at' => $sale->sold_at,
                    'notes' => 'Paiement initial de la vente',
                ]);
            }

            $sale->update([
                'subtotal' => $this->amount($subtotal), 'total_amount' => $this->amount($total),
                'amount_paid' => $this->amount($paid), 'amount_due' => $this->amount($total - $paid),
                'payment_status' => $this->paymentStatus($total, $paid),
            ]);

            return $sale->load(['customer', 'items.product', 'user']);
        }, attempts: 3);
    }
}
