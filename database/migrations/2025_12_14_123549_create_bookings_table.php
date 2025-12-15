<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('apartment_id')
                ->constrained('apartments')
                ->cascadeOnDelete();

            $table->foreignId('tenant_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Booking dates
            $table->date('start_date');
            $table->date('end_date');

            // Booking status
            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
                'cancelled'
            ])->default('pending');

            // Price calculated at booking time
            $table->decimal('total_price', 10, 2);

            $table->timestamps();

            // Prevent duplicate identical bookings
            $table->index(['apartment_id', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
