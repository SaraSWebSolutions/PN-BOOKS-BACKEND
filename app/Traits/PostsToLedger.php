<?php

namespace App\Traits;

use App\Models\CustomerLedger;

trait PostsToLedger
{
    /**
     * Call this in SalesTransaction::booted() after a sale is created:
     *
     *   static::created(fn($s) => $s->postSaleToLedger());
     *   static::deleted(fn($s) => $s->removeSaleFromLedger());
     */
    public function postSaleToLedger(): void
    {
        CustomerLedger::updateOrCreate(
            ['transaction_type' => 'sale', 'reference_id' => $this->id],
            [
                'contact_id'       => $this->contact_id,
                'reference_no'     => $this->invoice_no,
                'transaction_date' => $this->sale_date,
                'description'      => 'Sale Invoice — ' . ($this->invoice_no ?? ''),
                'debit'            => $this->subtotal ?? 0,
                'credit'           => 0,
                'created_by'       => $this->sales_person_id,
            ]
        );
        CustomerLedger::recalculateRunningBalance($this->contact_id);
    }

    public function removeSaleFromLedger(): void
    {
        CustomerLedger::removeEntry('sale', $this->id, $this->contact_id);
    }

    /**
     * Call this in CustomerAdvancePayment::booted() after a payment is saved:
     *
     *   static::saved(fn($p) => $p->postAdvanceToLedger());
     *   static::deleted(fn($p) => $p->removeAdvanceFromLedger());
     */
    public function postAdvanceToLedger(): void
    {
        $advance = $this->advance;
        CustomerLedger::updateOrCreate(
            ['transaction_type' => 'advance_payment', 'reference_id' => $this->id],
            [
                'contact_id'       => $advance->contact_id,
                'reference_no'     => $advance->voucher_no,
                'transaction_date' => $this->payment_date,
                'description'      => 'Advance Payment — ' . $advance->voucher_no,
                'debit'            => 0,
                'credit'           => $this->amount,
                'payment_method'   => $this->payment_method,
                'created_by'       => $this->created_by,
            ]
        );
        CustomerLedger::recalculateRunningBalance($advance->contact_id);
    }

    public function removeAdvanceFromLedger(): void
    {
        $advance = $this->advance;
        CustomerLedger::removeEntry('advance_payment', $this->id, $advance->contact_id);
    }
}