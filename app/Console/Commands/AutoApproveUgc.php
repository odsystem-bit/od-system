<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ServiceOrder;
use App\Models\Wallet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AutoApproveUgc extends Command
{
    protected $signature = 'mantota:auto-approve-ugc';

    protected $description = 'Auto-approuve les commandes UGC livrees depuis plus de 48h et paie les createurs de contenu.';

    public function handle(): int
    {
        $commissionRate = (float) mantota_setting('ugc_studio_fee_percent', 15) / 100;

        $orders = ServiceOrder::where('status', ServiceOrder::STATUS_DELIVERED)
            ->where('delivered_at', '<=', now()->subHours(48))
            ->get();

        if ($orders->isEmpty()) {
            $this->info('Aucune commande a auto-approuver.');
            return self::SUCCESS;
        }

        $count = 0;

        foreach ($orders as $order) {
            try {
                DB::transaction(function () use ($order) {
                    $vendorWallet = Wallet::where('user_id', $order->vendor_id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $influencerWallet = Wallet::firstOrCreate(
                        ['user_id' => $order->influencer_id],
                        ['balance' => 0, 'pending_balance' => 0, 'escrow_balance' => 0]
                    );
                    $influencerWallet = Wallet::where('id', $influencerWallet->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $amount     = (float) $order->amount;
                    $commission = round($amount * $commissionRate, 2);
                    $payout     = round($amount - $commission, 2);

                    $vendorWallet->decrement('escrow_balance', $amount);
                    $influencerWallet->increment('balance', $payout);

                    $order->update(['status' => ServiceOrder::STATUS_COMPLETED]);
                });

                $count++;
            } catch (\Throwable $e) {
                $this->error("Erreur sur commande #{$order->id} : {$e->getMessage()}");
            }
        }

        $this->info("{$count} commande(s) auto-approuvee(s).");

        return self::SUCCESS;
    }
}
