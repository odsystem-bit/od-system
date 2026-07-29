<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AuditLog — Traçabilité complète de toutes les actions critiques de l'admin.
 *
 * Modèle pour le "Black Box" de MANTOTA: chaque action sensible de l'équipe admin
 * est enregistrée avec les anciennes et nouvelles valeurs pour auditabilité totale.
 */
class AuditLog extends Model
{
    protected $fillable = [
        'admin_id',
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'json',
        'new_values' => 'json',
    ];

    /**
     * Admin qui a effectué l'action.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Scope pour filtrer par action.
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope pour filtrer par admin.
     */
    public function scopeByAdmin($query, int $adminId)
    {
        return $query->where('admin_id', $adminId);
    }

    /**
     * Scope pour filtrer par modèle.
     */
    public function scopeByModel($query, string $modelType, ?int $modelId = null)
    {
        $query = $query->where('model_type', $modelType);
        if ($modelId) {
            $query = $query->where('model_id', $modelId);
        }

        return $query;
    }
}
