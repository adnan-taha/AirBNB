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
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // id INTEGER PRIMARY KEY AUTOINCREMENT
            $table->string('first_name'); // TEXT NOT NULL
            $table->string('last_name');  // TEXT NOT NULL
            $table->string('phone')->unique(); // TEXT UNIQUE NOT NULL
            $table->string('email')->unique()->nullable(); // TEXT UNIQUE, optional

            $table->string('password'); // TEXT NOT NULL

            $table->enum('role', ['tenant', 'owner', 'admin'])->default('tenant'); // role with check
            $table->string('photo'); // photo TEXT NOT NULL
            $table->string('id_photo_front'); // id_photo_front TEXT NOT NULL
            $table->string('id_photo_back'); // id_photo_back TEXT NOT NULL
            $table->date('birth_date')->nullable(); // birth_date DATE
            $table->boolean('is_approved')->default(false); // is_approved BOOLEAN DEFAULT 0

            $table->timestamps(); // created_at & updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
