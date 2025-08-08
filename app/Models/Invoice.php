<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property-read \App\Models\User|null $user
 * @property-read \App\Models\Customer|null $customer
 */
class Invoice extends Model
{
    use HasFactory;
    
    // Add 'customer_id' to the fillable array
    protected $fillable = [
        'user_id', 'customer_id', 'invoice_number', 'due_date', 'total'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }
    
    // The relationship to the Customer model
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function calculateAndStoreTotal(): void
    {
        $total = $this->items->sum('subtotal');
        $this->update(['total' => $total]);
    }

    public static function createWithItems(array $data): self
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'];
            unset($data['items']);

            $invoice = self::create($data);

            foreach ($items as $item) {
                $item['subtotal'] = $item['qty'] * $item['price'];
                $invoice->items()->create($item);
            }

            $invoice->calculateAndStoreTotal();

            return $invoice;
        });
    }

    public function updateWithItems(array $data): void
    {
        DB::transaction(function () use ($data) {
            $items = $data['items'];
            unset($data['items']);

            $this->update($data);

            $this->items()->delete(); 

            foreach ($items as $item) {
                $item['subtotal'] = $item['qty'] * $item['price'];
                $this->items()->create($item);
            }

            $this->calculateAndStoreTotal();
        });
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = self::generateInvoiceNumber();
            }
        });
    }

    public static function generateInvoiceNumber(): string
    {
        $date = now()->format('Ymd');
        $lastId = self::max('id') + 1;
        return 'INV-' . $date . '-' . str_pad($lastId, 4, '0', STR_PAD_LEFT);
    }
}