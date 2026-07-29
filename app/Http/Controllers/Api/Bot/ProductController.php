<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Bot;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vendor_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'type' => 'required|in:physical,digital',
            'description' => 'required|string|max:5000',
            'price' => 'required|numeric|min:100',
            'image_url' => 'nullable|url',
            'commission_percent' => 'nullable|numeric|min:0|max:100',
            'stock' => 'nullable|integer|min:0',
            'delivery_type' => 'nullable|string',
        ]);

        $commissionPercent = $validated['commission_percent'] ?? 5.00;
        $data = [
            'vendor_id' => $validated['vendor_id'],
            'name' => $validated['name'],
            'type' => $validated['type'],
            'description' => $validated['description'],
            'price' => $validated['price'],
            'commission_percent' => $commissionPercent,
        ];

        if ($validated['type'] === 'physical') {
            $data['stock'] = $validated['stock'] ?? 100;
            $data['delivery_type'] = $validated['delivery_type'] ?? 'pay_on_delivery';
        }

        $product = Product::create($data);

        if (! empty($validated['image_url'])) {
            try {
                $response = Http::get($validated['image_url']);
                if ($response->successful()) {
                    $extension = pathinfo(parse_url($validated['image_url'], PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
                    $filename = 'products/' . Str::uuid() . '.' . $extension;
                    Storage::disk('public')->put($filename, $response->body());

                    $product->update(['image_path' => $filename]);

                    ProductImage::create([
                        'product_id' => $product->id,
                        'path' => $filename,
                        'sort_order' => 0,
                    ]);
                }
            } catch (\Throwable $e) {
            }
        }

        return response()->json([
            'success' => true,
            'product_id' => $product->id,
        ]);
    }
}
