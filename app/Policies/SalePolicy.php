<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

class SalePolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Sale $sale): bool { return true; }
    public function create(User $user): bool { return true; }
    public function addPayment(User $user, Sale $sale): bool { return $sale->status === 'completed' && (float) $sale->amount_due > 0; }
}
