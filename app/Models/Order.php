<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'client_name',
        'date',
        'payment',
        'total',
        'total_paid',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'total_paid' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Client, Order>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return HasMany<OrderItem>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<Payment>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get payment status: 'paid', 'partial', 'unpaid'
     */
    public function getPaymentStatusAttribute(): string
    {
        $totalPaid = (float) ($this->total_paid ?? 0);
        $total = (float) $this->total;

        if ($totalPaid >= $total) {
            return 'paid';
        } elseif ($totalPaid > 0) {
            return 'partial';
        }

        return 'unpaid';
    }

    /**
     * Get remaining balance
     */
    public function getRemainingBalanceAttribute(): float
    {
        $totalPaid = (float) ($this->total_paid ?? 0);
        $total = (float) $this->total;

        return max(0, $total - $totalPaid);
    }

    /**
     * Get payment percentage
     */
    public function getPaymentPercentageAttribute(): float
    {
        $total = (float) $this->total;
        if ($total <= 0) {
            return 0;
        }

        $totalPaid = (float) ($this->total_paid ?? 0);

        return min(100, ($totalPaid / $total) * 100);
    }
}
