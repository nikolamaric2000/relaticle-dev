<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('team_id')->constrained()->cascadeOnDelete();
            $table->string('invoiceable_id', 26);
            $table->string('invoiceable_type');
            $table->string('invoice_number', 50)->default('INV-2026-0001');
            $table->string('status', 20)->default('draft');
            $table->date('date');
            $table->date('due_date');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_total', 15, 2)->default(0);
            $table->decimal('discount_total', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('creator_id', 26)->nullable();
            $table->timestamps();

            $table->index(['invoiceable_id', 'invoiceable_type']);
            $table->index('status');
            $table->index('invoice_number');
        });
    }
};