<?php

declare(strict_types=1);

namespace App\Actions\Invoice;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

final readonly class UpdateInvoiceStatus
{
    /**
     * @var array<string, list<string>>
     */
    private const VALID_TRANSITIONS = [
        'draft' => ['sent', 'cancelled'],
        'sent' => ['viewed', 'overdue', 'cancelled'],
        'viewed' => ['partial_paid', 'paid', 'overdue', 'cancelled'],
        'partial_paid' => ['paid', 'cancelled'],
        'paid' => [],
        'overdue' => ['partial_paid', 'paid', 'cancelled'],
        'cancelled' => [],
    ];

    public function updateStatus(Invoice $invoice, InvoiceStatus $status, ?int $amountPaid = null): Invoice
    {
        $this->validateTransition($invoice->status, $status);

        if ($amountPaid !== null && $amountPaid < 0) {
            throw new \InvalidArgumentException('Amount paid must be a positive integer.');
        }

        return DB::transaction(function () use ($invoice, $status, $amountPaid): Invoice {
            $updateData = ['status' => $status];

            if ($amountPaid !== null) {
                $updateData['amount_paid'] = $amountPaid;
            }

            $invoice->update($updateData);

            return $invoice->refresh()->load(['items']);
        });
    }

    private function validateTransition(InvoiceStatus $from, InvoiceStatus $to): void
    {
        $allowed = self::VALID_TRANSITIONS[$from->value] ?? [];

        if (! in_array($to->value, $allowed, true)) {
            throw new \InvalidArgumentException(
                "Cannot transition invoice from {$from->value} to {$to->value}."
            );
        }
    }
}