<?php

namespace App\Observers;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogObserver
{
    private const HIDDEN = ['password', 'remember_token', 'updated_at', 'created_at'];

    public function created(Model $model): void
    {
        $new = array_diff_key($model->getAttributes(), array_flip(self::HIDDEN));
        $this->write($model, 'created', [], $new);
    }

    public function updated(Model $model): void
    {
        $new = array_diff_key($model->getChanges(), array_flip(self::HIDDEN));
        if ($new === []) return;
        $old = [];
        foreach (array_keys($new) as $key) $old[$key] = $model->getRawOriginal($key);
        $this->write($model, 'updated', $old, $new);
    }

    public function deleted(Model $model): void
    {
        $old = array_diff_key($model->getRawOriginal(), array_flip(self::HIDDEN));
        $this->write($model, 'deleted', $old, []);
    }

    private function write(Model $model, string $action, array $old, array $new): void
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'subject_type' => $model::class,
            'subject_id' => $model->getKey(),
            'description' => $this->description($model, $action),
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
        ]);
    }

    private function description(Model $model, string $action): string
    {
        $names = [
            'Product' => 'produit', 'Category' => 'catégorie', 'Customer' => 'client',
            'Supplier' => 'fournisseur', 'Sale' => 'vente', 'Purchase' => 'achat',
            'StockMovement' => 'mouvement de stock', 'Expense' => 'dépense',
            'Payment' => 'paiement', 'User' => 'utilisateur', 'Setting' => 'paramètre',
        ];
        $subject = $names[class_basename($model)] ?? 'donnée';
        $verb = ['created' => 'Création', 'updated' => 'Modification', 'deleted' => 'Suppression'][$action] ?? 'Action';
        $reference = $model->getAttribute('name') ?? $model->getAttribute('number') ?? $model->getAttribute('sku') ?? $model->getKey();
        return mb_substr($verb.' '.$subject.' : '.$reference, 0, 255);
    }
}
