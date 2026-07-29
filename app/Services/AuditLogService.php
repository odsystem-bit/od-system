<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

/**
 * AuditLogService — Service pour enregistrer les actions critiques de l'admin.
 *
 * Utilisé pour tracer toutes les actions sensibles:
 * - Approbation/rejet KYC
 * - Résolution de litiges
 * - Approbation/rejet retraits
 * - Modification des abonnés
 * - Messages admin
 * - Archivage de campagnes
 */
class AuditLogService
{
    /**
     * Enregistre une action critique de l'admin.
     *
     * @param  string  $action  Type d'action (approve_kyc, reject_kyc, resolve_dispute, etc.)
     * @param  string  $modelType  Type de modèle affecté (User, Order, Dispute, ServiceOrder, Campaign)
     * @param  int  $modelId  ID de la ressource affectée
     * @param  array  $oldValues  Anciennes valeurs (optionnel)
     * @param  array  $newValues  Nouvelles valeurs (optionnel)
     */
    public static function log(
        string $action,
        string $modelType,
        int $modelId,
        array $oldValues = [],
        array $newValues = []
    ): AuditLog {
        $admin = Auth::guard('admin')->user();

        return AuditLog::create([
            'admin_id' => $admin?->id,
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'old_values' => ! empty($oldValues) ? $oldValues : null,
            'new_values' => ! empty($newValues) ? $newValues : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->header('User-Agent'),
        ]);
    }

    /**
     * Obtient tous les logs d'audit avec pagination.
     */
    public static function getAllLogs($perPage = 50)
    {
        return AuditLog::with('admin')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Obtient les logs pour un admin spécifique.
     */
    public static function getLogsForAdmin(int $adminId, $perPage = 50)
    {
        return AuditLog::where('admin_id', $adminId)
            ->with('admin')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Obtient les logs pour une action spécifique.
     */
    public static function getLogsForAction(string $action, $perPage = 50)
    {
        return AuditLog::where('action', $action)
            ->with('admin')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Obtient les logs pour une ressource spécifique.
     */
    public static function getLogsForModel(string $modelType, int $modelId)
    {
        return AuditLog::where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->with('admin')
            ->latest()
            ->get();
    }
}
