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
        Schema::create('article_views', function (Blueprint $table) {
            Schema::create('article_views', function (Blueprint $table) {
                $table->id();
                $table->foreignId('article_id')->constrained()->onDelete('cascade');
                $table->string('ip_address', 45);
                $table->text('user_agent')->nullable();
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
                $table->timestamp('viewed_at')->useCurrent();
    
                $table->index(['article_id', 'ip_address', 'user_id', 'viewed_at']);
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_views');
    }
};
