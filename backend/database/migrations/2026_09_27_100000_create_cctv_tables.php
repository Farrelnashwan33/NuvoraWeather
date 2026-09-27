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
        Schema::create('cctv_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('region')->default('Indonesia');
            $table->string('base_url')->nullable();
            $table->string('api_url')->nullable();
            $table->string('status')->default('active'); // active, maintenance, inactive
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();
            
            $table->index(['region', 'status']);
        });

        Schema::create('cctv_cameras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->nullable()->constrained('cctv_sources')->nullOnDelete();
            $table->string('name');
            $table->string('province');
            $table->string('city');
            $table->string('district')->nullable();
            $table->string('road')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->text('stream_url')->nullable();
            $table->text('thumbnail_url')->nullable();
            $table->string('source_name')->default('Dishub / ATCS');
            $table->string('source_url')->nullable();
            $table->string('status')->default('online'); // online, offline, maintenance
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();

            // Performance indexes
            $table->index(['province', 'city']);
            $table->index(['latitude', 'longitude']);
            $table->index('status');
            $table->index('district');
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cctv_cameras');
        Schema::dropIfExists('cctv_sources');
    }
};
