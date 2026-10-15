<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incomplete_orders', function (Blueprint $table) {
            $table->string('fbp', 150)->nullable()->after('status');
            $table->string('fbc', 200)->nullable()->after('fbp');
            $table->string('utm_source')->nullable()->after('fbc');
            $table->string('utm_medium')->nullable()->after('utm_source');
            $table->string('utm_campaign')->nullable()->after('utm_medium');
            $table->string('utm_content')->nullable()->after('utm_campaign');
            $table->string('utm_term')->nullable()->after('utm_content');
            $table->string('referrer')->nullable()->after('utm_term');
        });
    }

    public function down(): void
    {
        Schema::table('incomplete_orders', function (Blueprint $table) {
            $table->dropColumn(['fbp', 'fbc', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'referrer']);
        });
    }
};
