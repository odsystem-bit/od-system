<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WithdrawalController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $status = $request->input('status', 'pending');

        $withdrawals = Transaction::where('type', 'withdrawal')
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->with('user:id,name,email,role,country,phone')
            ->latest()
            ->paginate(25)
            ->through(fn ($w) => tap($w, fn ($w) => $w->recipient_phone = $w->momo_number ?: ($w->user?->phone ?? '-')));

        $pendingCount   = Transaction::where('type', 'withdrawal')->where('status', 'pending')->count();
        $completedCount = Transaction::where('type', 'withdrawal')->where('status', 'completed')->count();
        $failedCount    = Transaction::where('type', 'withdrawal')->where('status', 'failed')->count();

        // Trust score: per-user stats for all users with pending withdrawals
        $pendingUserIds = $withdrawals->pluck('user_id')->unique();

        $trustScores = Transaction::where('type', 'withdrawal')
            ->where('status', 'completed')
            ->whereIn('user_id', $pendingUserIds)
            ->groupBy('user_id')
            ->select('user_id', DB::raw('COUNT(*) as completed_count'), DB::raw('SUM(amount_total) as completed_total'))
            ->limit(500)
            ->get()
            ->keyBy('user_id');

        $recentWithdrawals = Transaction::where('type', 'withdrawal')
            ->where('status', 'completed')
            ->whereIn('user_id', $pendingUserIds)
            ->orderByDesc('created_at')
            ->limit(500)
            ->get()
            ->groupBy('user_id')
            ->map(fn ($items) => $items->take(3));

        return Inertia::render('Withdrawals/Index', [
            'withdrawals'       => $withdrawals,
            'status'            => $status,
            'pendingCount'      => $pendingCount,
            'completedCount'    => $completedCount,
            'failedCount'       => $failedCount,
            'trustScores'       => $trustScores,
            'recentWithdrawals' => $recentWithdrawals,
        ]);
    }

    public function export(): StreamedResponse
    {
        $withdrawals = Transaction::where('type', 'withdrawal')
            ->with('user:id,name,email,role,phone')
            ->latest()
            ->get();

        $filename = 'retraits_' . date('Y-m-d') . '.csv';

        return new StreamedResponse(function () use ($withdrawals) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

            fputcsv($handle, [
                'ID', 'Utilisateur', 'Email', 'Role', 'Telephone',
                'Montant', 'Commission', 'Net', 'Statut', 'Date',
            ], ';');

            foreach ($withdrawals as $w) {
                fputcsv($handle, [
                    $w->id,
                    $w->user?->name ?? '-',
                    $w->user?->email ?? '-',
                    $w->user?->role ?? '-',
                    $w->momo_number ?: ($w->user?->phone ?? '-'),
                    number_format((float) $w->amount_target, 0, ',', ' '),
                    number_format((float) $w->mantota_markup + (float) $w->gateway_fee, 0, ',', ' '),
                    number_format((float) $w->amount_total, 0, ',', ' '),
                    $w->status,
                    $w->created_at?->format('d/m/Y H:i'),
                ], ';');
            }

            fclose($handle);
        }, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
