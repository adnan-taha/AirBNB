<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('apartments', function (Blueprint $table) {
            $table->unsignedTinyInteger('bathrooms')->default(1);
            $table->boolean('parking')->default(false);
            $table->unsignedInteger('area')->nullable(); // square meters
            $table->unsignedSmallInteger('build_year')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('apartments', function (Blueprint $table) {
            $table->dropColumn([
                'bathrooms',
                'parking',
                'area',
                'build_year'
            ]);
        });
    }
};
