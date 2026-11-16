<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class StockService
{
    private const SCALE = 1000;
    private const MAX_UNITS = 999_999_999_999;

    public function record(
        Product $product,
        string $type,
        string|int|float $quantity,
        ?User $user,
        ?string $reason = null,
        ?Model $reference = null,
    ): StockMovement {
        if (! in_array($type, StockMovement::TYPES, true)) {
            throw new InvalidArgumentException('Type de mouvement de stock inconnu.');
        }

        $quantityUnits = $this->toUnits($quantity);
        if ($quantityUnits <= 0) {
            throw ValidationException::withMessages(['quantity' => 'La quantité doit être supérieure à zéro.']);
        }

        return DB::transaction(function () use ($product, $type, $quantityUnits, $user, $reason, $reference) {
            $lockedProduct = Product::query()->lockForUpdate()->findOrFail($product->getKey());
            $before = $this->toUnits($lockedProduct->stock_quantity);
            $direction = in_array($type, ['entry', 'adjustment_positive', 'purchase'], true) ? 1 : -1;
            $after = $before + ($direction * $quantityUnits);

            if ($after < 0) {
                throw ValidationException::withMessages([
                    'quantity' => "Stock insuffisant pour « {$lockedProduct->name} ». Disponible : ".$this->fromUnits($before).'.',
                ]);
            }
            if ($after > self::MAX_UNITS) {
                throw ValidationException::withMessages(['quantity' => 'Le stock dépasserait la quantité maximale autorisée.']);
            }

            $lockedProduct->stock_quantity = $this->fromUnits($after);
            $lockedProduct->save();

            $movement = new StockMovement([
                'movement_type' => $type,
                'quantity' => $this->fromUnits($quantityUnits),
                'stock_before' => $this->fromUnits($before),
                'stock_after' => $this->fromUnits($after),
                'reason' => $reason,
                'occurred_at' => now(),
            ]);
            $movement->product()->associate($lockedProduct);
            $movement->user()->associate($user);
            if ($reference) {
                $movement->reference()->associate($reference);
            }
            $movement->save();

            return $movement;
        }, attempts: 3);
    }

    private function toUnits(string|int|float $value): int
    {
        $value = trim((string) $value);
        if (! preg_match('/^\d{1,9}(?:\.\d{1,3})?$/', $value)) {
            throw ValidationException::withMessages(['quantity' => 'La quantité doit comporter au maximum trois décimales.']);
        }

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        return ((int) $whole * self::SCALE) + (int) str_pad($fraction, 3, '0');
    }

    private function fromUnits(int $units): string
    {
        return intdiv($units, self::SCALE).'.'.str_pad((string) ($units % self::SCALE), 3, '0', STR_PAD_LEFT);
    }
}
