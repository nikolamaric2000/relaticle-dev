<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasTeam;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

use Illuminate\Database\Eloquent\SoftDeletes;

final class Invoice extends Model
{
    use HasCreator;
    use HasFactory;
    use HasTeam;
    use HasUlids;
    use SoftDeletes;

    /** @use HasFactory<InvoiceFactory> */

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'invoiceable_id',
        'invoiceable_type',
        'invoice_number',
        'status',
        'date',
        'due_date',
        'subtotal',
        'tax_total',
        'discount_total',
        'total',
        'amount_paid',
        'notes',
        'creator_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
        ];
    }

    public function invoiceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Scope a query to only include unpaid invoices.
     */
    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            InvoiceStatus::Paid,
            InvoiceStatus::Cancelled,
        ]);
    }

    /**
     * Scope a query to only include overdue invoices.
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->unpaid()->where('due_date', '<', now())->where('status', '!=', InvoiceStatus::Paid);
    }
}