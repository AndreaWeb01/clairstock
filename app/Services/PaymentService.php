<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    use TransactionTotals;

    public function record(Model $payable, array $data, User $user): Payment
    {
        if ($payable instanceof Sale) {
            $model = Sale::class;
            $statusAllowed = 'completed';
        } elseif ($payable instanceof Purchase) {
            $model = Purchase::class;
            $statusAllowed = 'received';
        } else {
            throw new \InvalidArgumentException('Ce type de paiement n’est pas pris en charge.');
        }

        return DB::transaction(function () use ($model, $payable, $data, $user, $statusAllowed) {
            $record = $model::query()->lockForUpdate()->findOrFail($payable->getKey());
            if ($record->status !== $statusAllowed) {
                throw ValidationException::withMessages(['payment' => 'Les paiements ne peuvent être enregistrés que sur une opération validée.']);
            }

            $amount = $this->cents($data['amount']);
            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'Le paiement doit être supérieur à zéro.']);
            }
            $paidBefore = $record->payments()->get(['amount'])->sum(fn ($payment) => $this->cents($payment->amount));
            $total = $this->cents($record->total_amount);
            if ($paidBefore + $amount > $total) {
                throw ValidationException::withMessages(['amount' => 'Le paiement dépasse le montant restant dû.']);
            }

            $payment = $record->payments()->create([
                'user_id' => $user->id,
                'amount' => $this->amount($amount),
                'method' => $data['method'],
                'paid_at' => $data['paid_at'],
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
            $paid = $paidBefore + $amount;
            $record->update([
                'amount_paid' => $this->amount($paid),
                'amount_due' => $this->amount($total - $paid),
                'payment_status' => $this->paymentStatus($total, $paid),
            ]);

            return $payment->load('user');
        }, attempts: 3);
    }
}
