<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['uuid', 'invoice_number', 'customer_id', 'payment_category_id', 'receipt_template_id', 'amount', 'admin_fee', 'total_amount', 'paid_at', 'sender_name', 'destination_bank', 'transaction_id', 'proof_path', 'ocr_data', 'status', 'printed_at', 'print_count', 'notes'])]
class Transaction extends Model
{
    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'admin_fee' => 'decimal:2', 'total_amount' => 'decimal:2', 'paid_at' => 'datetime', 'printed_at' => 'datetime', 'ocr_data' => 'array'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function paymentCategory(): BelongsTo
    {
        return $this->belongsTo(PaymentCategory::class);
    }

    public function receiptTemplate(): BelongsTo
    {
        return $this->belongsTo(ReceiptTemplate::class);
    }

    public function printLogs(): HasMany
    {
        return $this->hasMany(PrintLog::class);
    }
}
