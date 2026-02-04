<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('inventory_code')->unique();
            $table->uuid('member_id')->nullable();
            $table->string('name');
            $table->string('type')->nullable();
            $table->string('serial_number')->nullable();
            $table->text('specification')->nullable();
            $table->enum('status', [
                'baik',
                'rusak',
                'dilelang',
                'tidak_dipakai'
            ])->default('baik');

            $table->string('department')->nullable();

            $table->timestamps();
            $table->foreign('member_id')
                  ->references('id')
                  ->on('members')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
