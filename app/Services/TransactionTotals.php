<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

trait TransactionTotals
{
    private function cents(string|int|float|null $amount): int
    {
        $amount = trim((string) ($amount ?? '0'));
        if (! preg_match('/^\d{1,13}(?:\.\d{1,2})?$/', $amount)) {
            throw ValidationException::withMessages(['total' => 'Le montant doit être positif et comporter au maximum deux décimales.']);
        }
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    private function amount(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    private function lineAmount(int $unitCents, string $quantity): int
    {
        [$whole, $fraction] = array_pad(explode('.', $quantity, 2), 2, '');
        $milli = ((int) $whole * 1000) + (int) str_pad($fraction, 3, '0');
        if ($milli <= 0 || $unitCents > intdiv(PHP_INT_MAX - 500, $milli)) {
            throw ValidationException::withMessages(['total' => 'Le montant de cette ligne dépasse la limite autorisée.']);
        }
        $line = intdiv(($unitCents * $milli) + 500, 1000);
        if ($line > 999_999_999_999_999) {
            throw ValidationException::withMessages(['total' => 'Le montant de cette ligne dépasse la limite autorisée.']);
        }
        return $line;
    }

    private function paymentStatus(int $total, int $paid): string
    {
        if ($total === 0) {
            return 'paid';
        }
        return $paid === 0 ? 'unpaid' : ($paid >= $total ? 'paid' : 'partial');
    }
}
