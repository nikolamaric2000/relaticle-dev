<?php

declare(strict_types=1);

namespace App\Actions\Invoice;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final readonly class CreateInvoice
{
    public function __construct(
        private GenerateInvoiceNumber $generateInvoiceNumber,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, Team $team, User $user): Invoice
    {
        abort_unless($user->can('create', Invoice::class), 403);

        $items = Arr::pull($data, 'items', []);

        $attributes = Arr::only($data, [
            'due_date',
            'notes',
            'invoiceable_id',
            'invoiceable_type',
            'custom_fields',
        ]);

        $attributes['team_id'] = $team->id;
        $attributes['creator_id'] = $user->id;
        $attributes['status'] = InvoiceStatus::DRAFT;
        $attributes['invoice_number'] = $this->generateInvoiceNumber->generate($team);

        /** @var Invoice $invoice */
        $invoice = DB::transaction(function () use ($attributes, $items): Invoice {
            $invoice = Invoice::query()->create($attributes);

            $subtotal = 0;

            foreach ($items as $itemData) {
                $itemSubtotal = $itemData['quantity'] * $itemData['unit_price'];
                $subtotal += $itemSubtotal;

                $invoice->items()->create([
                    'description' => $itemData['description'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'subtotal' => $itemSubtotal,
                ]);
            }

            $invoice->update([
                'subtotal' => $subtotal,
                'total' => $subtotal,
            ]);

            return $invoice;
        });

        return $invoice->load(['items']);
    }
}