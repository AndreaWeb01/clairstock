<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseService
{
    use TransactionTotals;

    public function __construct(private readonly StockService $stock) {}

    public function create(array $data, User $user): Purchase
    {
        return DB::transaction(function () use ($data, $user) {
            $purchase = new Purchase([
                'number' => 'ACH-'.now()->format('Ymd').'-'.strtoupper(bin2hex(random_bytes(3))),
                'supplier_id' => $data['supplier_id'], 'user_id' => $user->id,
                'purchased_at' => $data['purchased_at'] ?? now()->toDateString(),
                'status' => 'draft', 'discount_amount' => $this->amount($this->cents($data['discount_amount'] ?? 0)),
                'notes' => $data['notes'] ?? null,
            ]);
            $purchase->save();

            $subtotal = 0;
            $lines = [];
            foreach ($data['items'] as $line) {
                $product = Product::query()->findOrFail($line['product_id']);
                $quantity = (string) $line['quantity'];
                $unitCost = $this->cents($line['unit_cost']);
                $lineTotal = $this->lineAmount($unitCost, $quantity);
                $subtotal += $lineTotal;
                if ($subtotal > 999_999_999_999_999) {
                    throw ValidationException::withMessages(['total' => 'Le total de l’achat dépasse la limite autorisée.']);
                }
                $lines[] = [$product, $quantity, $unitCost, $lineTotal];
            }

            $discount = $this->cents($data['discount_amount'] ?? 0);
            if ($discount > $subtotal) {
                throw ValidationException::withMessages(['discount_amount' => 'La remise ne peut pas dépasser le sous-total.']);
            }
            $total = $subtotal - $discount;
            foreach ($lines as [$product, $quantity, $unitCost, $lineTotal]) {
                $purchase->items()->create([
                    'product_id' => $product->id, 'product_name' => $product->name, 'sku' => $product->sku,
                    'quantity' => $quantity, 'unit_cost' => $this->amount($unitCost), 'line_total' => $this->amount($lineTotal),
                ]);
            }
            $purchase->update([
                'subtotal' => $this->amount($subtotal), 'total_amount' => $this->amount($total),
                'amount_due' => $this->amount($total), 'payment_status' => $this->paymentStatus($total, 0),
            ]);

            if (($data['status'] ?? 'draft') === 'received') {
                $this->receiveWithinTransaction($purchase, $user);
            }

            return $purchase->load(['supplier', 'items.product', 'user']);
        }, attempts: 3);
    }

    public function receive(Purchase $purchase, User $user): Purchase
    {
        return DB::transaction(function () use ($purchase, $user) {
            $purchase = Purchase::query()->lockForUpdate()->with('items.product')->findOrFail($purchase->id);
            $this->receiveWithinTransaction($purchase, $user);
            return $purchase->load(['supplier', 'items.product', 'user']);
        }, attempts: 3);
    }

    public function updateDraft(Purchase $purchase, array $data): Purchase
    {
        return DB::transaction(function () use ($purchase, $data) {
            $purchase = Purchase::query()->lockForUpdate()->findOrFail($purchase->id);
            if ($purchase->status !== 'draft') {
                throw ValidationException::withMessages(['purchase' => 'Seul un brouillon peut être modifié.']);
            }

            $subtotal = 0;
            $lines = [];
            foreach ($data['items'] as $line) {
                $product = Product::query()->findOrFail($line['product_id']);
                $quantity = (string) $line['quantity'];
                $unitCost = $this->cents($line['unit_cost']);
                $lineTotal = $this->lineAmount($unitCost, $quantity);
                $subtotal += $lineTotal;
                if ($subtotal > 999_999_999_999_999) {
                    throw ValidationException::withMessages(['total' => 'Le total de l’achat dépasse la limite autorisée.']);
                }
                $lines[] = [$product, $quantity, $unitCost, $lineTotal];
            }
            $discount = $this->cents($data['discount_amount'] ?? 0);
            if ($discount > $subtotal) {
                throw ValidationException::withMessages(['discount_amount' => 'La remise ne peut pas dépasser le sous-total.']);
            }
            $total = $subtotal - $discount;

            $purchase->items()->delete();
            foreach ($lines as [$product, $quantity, $unitCost, $lineTotal]) {
                $purchase->items()->create([
                    'product_id' => $product->id, 'product_name' => $product->name, 'sku' => $product->sku,
                    'quantity' => $quantity, 'unit_cost' => $this->amount($unitCost), 'line_total' => $this->amount($lineTotal),
                ]);
            }
            $purchase->update([
                'supplier_id' => $data['supplier_id'], 'purchased_at' => $data['purchased_at'],
                'discount_amount' => $this->amount($discount), 'subtotal' => $this->amount($subtotal),
                'total_amount' => $this->amount($total), 'amount_due' => $this->amount($total),
                'payment_status' => $this->paymentStatus($total, 0), 'notes' => $data['notes'] ?? null,
            ]);
            return $purchase->load(['supplier', 'items.product', 'user']);
        }, attempts: 3);
    }

    public function cancel(Purchase $purchase, User $user): Purchase
    {
        return DB::transaction(function () use ($purchase, $user) {
            $purchase = Purchase::query()->lockForUpdate()->with('items.product')->findOrFail($purchase->id);
            if ($this->cents($purchase->amount_paid) > 0) {
                throw ValidationException::withMessages(['purchase' => 'Cet achat comporte déjà un paiement. Enregistrez d’abord le remboursement ou l’avoir du fournisseur avant son annulation.']);
            }
            if ($purchase->status === 'received') {
                foreach ($purchase->items as $item) {
                    $this->stock->record($item->product, 'adjustment_negative', $item->quantity, $user, 'Annulation achat '.$purchase->number, $purchase);
                }
            } elseif ($purchase->status !== 'draft') {
                throw ValidationException::withMessages(['purchase' => 'Cet achat est déjà annulé.']);
            }
            $purchase->update(['status' => 'cancelled']);
            return $purchase;
        }, attempts: 3);
    }

    private function receiveWithinTransaction(Purchase $purchase, User $user): void
    {
        if ($purchase->status !== 'draft') {
            throw ValidationException::withMessages(['purchase' => 'Seul un brouillon peut être réceptionné.']);
        }
        foreach ($purchase->items as $item) {
            $this->stock->record($item->product, 'purchase', $item->quantity, $user, 'Réception achat '.$purchase->number, $purchase);
            $item->product->update(['purchase_price' => $item->unit_cost]);
        }
        $purchase->update(['status' => 'received']);
    }
}
