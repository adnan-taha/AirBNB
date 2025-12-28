<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('apartments', function (Blueprint $table) {
            // Convert province to enum
            $table->enum('province', ['Damascus', 'Aleppo', 'Homs', 'Rif Dimashq', 'Tartous', 'Latakia'])
                ->nullable();

            // Convert city to enum
            $table->enum('city', [
                // Damascus
                'Mouhajrin', 'Mazzeh', 'Dummar',
                // Aleppo
                'Shahba', 'Jamiliyah', 'Soleymanye',
                // Homs
                'Al-Waer', 'Hamidiya', 'Al Zahraa',
                // Rif Dimashq
                'Douma', 'Yafour', 'Zamalka',
                // Latakia
                'Kessab', 'Al Kournish', 'Jableh',
                // Tartous
                'Baniyas', 'Al Qadmous', 'Safita'
            ])->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apartments', function (Blueprint $table) {
            // Revert to string (or previous type)
            $table->string('province')->nullable()->change();
            $table->string('city')->nullable()->change();
        });
    }
};
