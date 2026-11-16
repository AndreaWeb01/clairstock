<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = ['user_id', 'action', 'subject_type', 'subject_id', 'description', 'old_values', 'new_values', 'ip_address'];
    protected function casts(): array { return ['old_values' => 'array', 'new_values' => 'array']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function actionLabel(): string
    {
        return ['created' => 'Création', 'updated' => 'Modification', 'deleted' => 'Suppression'][$this->action] ?? ucfirst($this->action);
    }

    public function subjectLabel(): string
    {
        return match (class_basename($this->subject_type)) {
            'Product' => 'Produit', 'Category' => 'Catégorie', 'Customer' => 'Client',
            'Supplier' => 'Fournisseur', 'Sale' => 'Vente', 'Purchase' => 'Achat',
            'StockMovement' => 'Mouvement de stock', 'Expense' => 'Dépense',
            'Payment' => 'Paiement', 'User' => 'Utilisateur', default => class_basename($this->subject_type),
        };
    }
}
