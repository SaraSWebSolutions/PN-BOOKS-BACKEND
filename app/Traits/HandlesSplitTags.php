<?php
// app/Traits/HandlesSplitTags.php

namespace App\Traits;

use App\Models\StockItem;
use App\Models\StockItemSplit;

trait HandlesSplitTags
{
    /**
     * Process split_tag_weights from the request.
     * Renames stock item tags, updates weights, and records StockItemSplit rows.
     *
     * @param array  $splitTagWeights  — from $request->split_tag_weights
     * @param string $splitLabel       — 'A', 'B', 'C'
     * @param string $sourceType       — 'sale' | 'quotation'
     * @param int    $sourceId         — sales_transaction.id | quotation.id
     * @param string $sourceLabel      — invoice_no | quotation_no
     */
    protected function processSplitTags(
        array  $splitTagWeights,
        string $splitLabel,
        string $sourceType,
        int    $sourceId,
        string $sourceLabel
    ): void {
        foreach ($splitTagWeights as $entry) {
            $originalTagNo = $entry['original_tag_no'] ?? null;
            $newTagNo      = $entry['new_tag_no']      ?? null;
            $weight        = isset($entry['weight']) ? (float) $entry['weight'] : null;

            if (!$originalTagNo || !$newTagNo || !$weight) continue;

            // Find the original stock item BEFORE renaming
            $stockItem = StockItem::where('tag_no', $originalTagNo)->first();
            if (!$stockItem) continue;

            $originalWeight = (float) $stockItem->gross_weight;
            $ratio          = $originalWeight > 0 ? $weight / $originalWeight : 1;

            // Rename + update weight on the stock item
            $stockItem->update([
                'tag_no'       => $newTagNo,
                'gross_weight' => $weight,
                'net_weight'   => round(((float) $stockItem->net_weight) * $ratio, 3),
            ]);

            // Calculate proportional price
            $splitPrice = round(((float) ($stockItem->sale_price ?? $stockItem->cost_price ?? 0)) * $ratio, 2);

            // Group key — ties all children from one split together
            $groupKey = $originalTagNo . '_' . $sourceType . '_' . $sourceId;

            // Record the split
            StockItemSplit::create([
                'parent_stock_item_id' => $stockItem->id,  // same ID, tag_no just changed
                'parent_tag_no'        => $originalTagNo,
                'parent_gross_weight'  => $originalWeight,
                'child_stock_item_id'  => $stockItem->id,  // same row — tag renamed in-place
                'child_tag_no'         => $newTagNo,
                'split_label'          => $splitLabel,
                'split_weight'         => $weight,
                'split_price'          => $splitPrice,
                'split_ratio'          => round($ratio, 6),
                'source_type'          => $sourceType,
                'source_id'            => $sourceId,
                'source_label'         => $sourceLabel,
                'split_group_key'      => $groupKey,
                'status'               => 'active',
                'created_by'           => auth()->id(),
            ]);
        }
    }
}