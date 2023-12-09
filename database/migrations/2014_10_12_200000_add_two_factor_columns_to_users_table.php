<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('moonshine_users', static function (Blueprint $table) {
            $table->text('two_factor_secret')
                ->after('password')
                ->nullable();

            $table->text('two_factor_recovery_codes')
                ->after('two_factor_secret')
                ->nullable();

            $table->timestamp('two_factor_confirmed_at')
                ->after('two_factor_recovery_codes')
                ->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('moonshine_users', static function (Blueprint $table) {
            $table->dropColumn(array_merge([
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at'
            ]));
        });
    }
};
