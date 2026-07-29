<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * AuditLogController — Boîte Noire MANTOTA.
 *
 * Affiche la traçabilité complète de toutes les actions critiques
 * de l'équipe admin avec filtres par action, admin et date.
 */
class AuditLogController extends Controller
{
    /**
     * Liste paginée des logs d'audit avec filtres.
     */
    public function index(Request $request): InertiaResponse
    {
        $query = AuditLog::with('admin:id,name,email')
            ->latest();

        // Filtre par action
        if ($action = $request->input('action')) {
            $query->where('action', $action);
        }

        // Filtre par admin
        if ($adminId = $request->input('admin_id')) {
            $query->where('admin_id', $adminId);
        }

        // Filtre par modèle
        if ($modelType = $request->input('model_type')) {
            $query->where('model_type', $modelType);
        }

        // Filtre par date (début)
        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        // Filtre par date (fin)
        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $logs = $query->paginate(50)->withQueryString();

        // Liste des admins pour le filtre
        $admins = User::where('role', UserRole::ADMIN)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        // Liste des actions uniques pour le filtre
        $actions = AuditLog::distinct()->pluck('action')->sort()->values();

        // Liste des types de modèles pour le filtre
        $modelTypes = AuditLog::distinct()->pluck('model_type')->sort()->values();

        return Inertia::render('AuditLogs/Index', [
            'logs'       => $logs,
            'admins'     => $admins,
            'actions'    => $actions,
            'modelTypes' => $modelTypes,
            'filters'    => $request->only(['action', 'admin_id', 'model_type', 'date_from', 'date_to']),
        ]);
    }
}
