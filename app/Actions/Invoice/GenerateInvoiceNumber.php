<?php

declare(strict_types=1);

namespace App\Actions\Invoice;

use App\Models\Invoice;
use App\Models\Team;

final readonly class GenerateInvoiceNumber
{
    /**
     * Generate the next invoice number for the given team.
     * Format: INV-YYYY-NNNN (current year + sequential number).
     */
    public function generate(Team $team): string
    {
        $year = now()->format('Y');

        $lastInvoice = Invoice::query()
            ->where('team_id', $team->id)
            ->where('invoice_number', 'like', "INV-{$year}-%")
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastInvoice === null) {
            $sequence = 1;
        } else {
            $parts = explode('-', $lastInvoice->invoice_number);
            $sequence = ((int) end($parts)) + 1;
        }

        return sprintf('INV-%s-%04d', $year, $sequence);
    }
}