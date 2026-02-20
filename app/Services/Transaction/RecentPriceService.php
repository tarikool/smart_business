<?php

namespace App\Services\Transaction;

use App\Models\ProductRecentPrice;
use App\Models\Transaction;

class RecentPriceService
{
    public function updateRecentPrice(Transaction $transaction)
    {
        $transaction->load([
            'transactionItems.productUser',
            'transactionItems.product:id,global_product_id',
            'transactionItems.unitOption:id,base_unit_id,multiplier',
        ]);

        foreach ($transaction->transactionItems as $item) {
            $recentPrice = ProductRecentPrice::firstOrNew([
                'user_id' => $item->user_id,
                'product_id' => $item->product_id,
            ]);

            $newQty = $item->quantity * $item->unitOption->multiplier;

            if ($item->txn_type->isBuy()) {
                $recentPrice->recent_buy = $item->unit_price;
                $recentPrice->avg_buy = recentAvg(
                    lastAvg: $recentPrice->avg_buy,
                    counter: $recentPrice->total_buy_qty,
                    unitPrice: $item->unit_price,
                    quantity: $newQty,
                );
                $recentPrice->total_buy_qty += $newQty;
                $recentPrice->buy_date = $item->txn_date;
            }

            if ($item->txn_type->isSale()) {
                $recentPrice->recent_sell = $item->unit_price;
                $recentPrice->avg_sell = recentAvg(
                    lastAvg: $recentPrice->avg_sell,
                    counter: $recentPrice->total_sell_qty,
                    unitPrice: $item->unit_price,
                    quantity: $newQty,
                );
                $recentPrice->total_sell_qty += $newQty;
                $recentPrice->sell_date = $item->txn_date;
            }

            $recentPrice->base_unit_id = $recentPrice->base_unit_id ?: $item->unitOption->base_unit_id;
            $recentPrice->global_product_id = $item->product->global_product_id;
            $recentPrice->has_stock = $item->productUser->current_stock > 0;

            $recentPrice->save();
        }
    }
}
