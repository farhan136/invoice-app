<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class InvoiceItem extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'invoice_id', 'item_name', 'qty', 'price', 'subtotal'
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}