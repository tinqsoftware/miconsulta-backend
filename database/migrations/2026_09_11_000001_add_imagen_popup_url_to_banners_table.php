<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('banners', 'imagen_popup_url')) {
            Schema::table('banners', function (Blueprint $table): void {
                $table->string('imagen_popup_url')->nullable()->after('imagen_url');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('banners', 'imagen_popup_url')) {
            Schema::table('banners', function (Blueprint $table): void {
                $table->dropColumn('imagen_popup_url');
            });
        }
    }
};
