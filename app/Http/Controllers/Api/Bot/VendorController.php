<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Bot;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class VendorController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'phone' => 'required|string|unique:users,phone',
            'whatsapp_bot_number' => 'required|string',
            'shop_type' => 'required|in:physical,digital,both',
            'bot_plan' => 'required|in:starter,standard,pro,tpf',
            'shop_address' => 'nullable|string',
            'shop_latitude' => 'nullable|numeric',
            'shop_longitude' => 'nullable|numeric',
            'manual_access' => 'nullable|boolean',
        ]);

        $email = $validated['email'] ?? 'bot_' . $validated['phone'] . '@mantota-bot.local';
        $password = Str::random(12);
        $botAccessType = ($request->boolean('manual_access', false)) ? 'manual' : 'paid';

        $user = DB::transaction(function () use ($validated, $email, $password, $botAccessType) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $email,
                'password' => Hash::make($password),
                'phone' => $validated['phone'],
                'role' => UserRole::VENDOR,
                'bot_status' => 'active',
                'bot_access_type' => $botAccessType,
                'whatsapp_bot_number' => $validated['whatsapp_bot_number'],
                'shop_type' => $validated['shop_type'],
                'shop_address' => $validated['shop_address'] ?? null,
                'shop_latitude' => $validated['shop_latitude'] ?? null,
                'shop_longitude' => $validated['shop_longitude'] ?? null,
                'bot_plan' => $validated['bot_plan'],
            ]);

            Wallet::create([
                'user_id' => $user->id,
                'balance' => 0,
                'pending_balance' => 0,
            ]);

            return $user;
        });

        return response()->json([
            'success' => true,
            'vendor_id' => $user->id,
            'temp_password' => $password,
            'shop_url' => "https://mantota.com/boutique/{$user->slug}",
        ]);
    }

    public function findByPhone(string $phone): JsonResponse
    {
        $user = User::where('whatsapp_bot_number', $phone)
            ->orWhere('phone', $phone)
            ->first();

        if (! $user) {
            return response()->json(['error' => 'Vendor not found'], 404);
        }

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'shop_name' => $user->shop_name,
            'bot_plan' => $user->bot_plan,
            'bot_status' => $user->bot_status,
        ]);
    }
}
