<?php

namespace App\Policies;

use App\Models\Purchase;
use App\Models\User;

class PurchasePolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Purchase $purchase): bool { return true; }
    public function create(User $user): bool { return $user->isAdmin(); }
    public function update(User $user, Purchase $purchase): bool { return $user->isAdmin() && $purchase->status === 'draft'; }
    public function receive(User $user, Purchase $purchase): bool { return $user->isAdmin(); }
    public function cancel(User $user, Purchase $purchase): bool { return $user->isAdmin() && in_array($purchase->status, ['draft', 'received'], true) && $purchase->amount_paid === '0.00'; }
    public function addPayment(User $user, Purchase $purchase): bool { return $user->isAdmin() && $purchase->status === 'received' && (float) $purchase->amount_due > 0; }
}
