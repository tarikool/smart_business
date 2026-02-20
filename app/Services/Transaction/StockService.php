<?php

namespace App\Services\Transaction;

use App\Enums\StockEntryType;
use App\Models\Product;
use App\Models\ProductUser;
use App\Models\StockLedger;
use App\Models\TransactionItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockService
{
    public static function recordFromTxn(TransactionItem $txnItem, $maxStock = null, $note = null)
    {
        return self::record(
            productId: $txnItem->product_id,
            userId: $txnItem->user_id,
            entryType: $txnItem->txn_type->entryType(),
            qty: $txnItem->quantity * $txnItem->unitOption->multiplier,
            maxStock: $maxStock,
            relatedId: $txnItem->id,
            note: $note
        );
    }

    public static function recordReturn(TransactionItem $txnItem, $returnQty, $note = 'Return')
    {
        return self::record(
            productId: $txnItem->product_id,
            userId: $txnItem->user_id,
            entryType: StockEntryType::RETURN,
            qty: $returnQty * $txnItem->unitOption->multiplier,
            relatedId: $txnItem->id,
            note: $note
        );
    }

    public static function recordUpdate(TransactionItem $txnItem, $updateQty, $note = 'Update')
    {
        return self::record(
            productId: $txnItem->product_id,
            userId: $txnItem->user_id,
            entryType: StockEntryType::ADJUST,
            qty: $updateQty * $txnItem->unitOption->multiplier,
            relatedId: $txnItem->id,
            note: $note
        );
    }

    public static function record($productId, $userId, StockEntryType $entryType, $qty, $maxStock = null, $relatedId = null, $note = null)
    {
        if (! $qty) {
            throw ValidationException::withMessages([
                "products.$productId.qty" => 'Quantity cannot be zero',
            ]);
        }

        return DB::transaction(function () use ($productId, $userId, $entryType, $qty, $maxStock, $relatedId, $note) {
            $productUser = ProductUser::whereProductId($productId)
                ->whereUserId($userId)
                ->lockForUpdate()
                ->firstOrFail();

            $before = $productUser->current_stock;
            $signedQty = $entryType->signedQty($qty);
            $after = $before + $signedQty;

            if ($after < 0) {
                throw ValidationException::withMessages([
                    "products.$productId.stock" => "Insufficient stock for {$productUser->product->name}. Current stock is {$before}.",
                ]);
            }

            $productUser->current_stock = $after;
            $productUser->max_stock = ! blank($maxStock) ? $maxStock : $productUser->max_stock;
            $productUser->save();

            return StockLedger::create([
                'product_id' => $productId,
                'user_id' => $userId,
                'entry_type' => $entryType,
                'related_id' => $relatedId,
                'quantity' => $signedQty,
                'balance_after' => $after,
                'note' => $note,
            ]);
        });
    }

    public function recordNonTxn($productId, $userId) {}

    public static function resetProductStock($productId, $userId, $qty, $note = null)
    {
        $before = StockLedger::where([
            'user_id' => $userId,
            'product_id' => $productId,
        ])->sum('quantity');

        $entryType = StockEntryType::ADJUST;
        $signedQty = $entryType->signedQty($qty);
        $after = $before + $signedQty;

        if ($after != 0) {
            $productName = Product::find($productId)->name;
            throw ValidationException::withMessages([
                "products.$productId.stock" => "Invalid stock for {$productName}. Final stock is {$after}, should be 0.",
            ]);
        }

        return StockLedger::create([
            'product_id' => $productId,
            'user_id' => $userId,
            'entry_type' => $entryType,
            'quantity' => $signedQty,
            'balance_after' => $after,
            'note' => $note,
        ]);
    }
}
