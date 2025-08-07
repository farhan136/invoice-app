<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property-read \App\Models\User|null $user
 */
class Invoice extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id', 'invoice_number', 'due_date', 'total'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    // Calculate and store total
    public function calculateAndStoreTotal(): void
    {
        $total = $this->items->sum('subtotal');
        $this->update(['total' => $total]);
    }

    public static function createWithItems(array $data): self
    {
        // Create invoice and items in transaction
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

    public function customer()
    {
        return $this->belongsTo(Customer::class);
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
        // Format: INV-YYYYMMDD-XXXX
        $date = now()->format('Ymd');
        $lastId = self::max('id') + 1;
        return 'INV-' . $date . '-' . str_pad($lastId, 4, '0', STR_PAD_LEFT);
    }
}