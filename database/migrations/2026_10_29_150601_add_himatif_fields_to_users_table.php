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
        Schema::table('users', function (Blueprint $table) {
            $table->string('npp', 30)->unique()->after('id');
            $table->string('npm', 30)->unique()->after('npp');
            $table->string('email')->nullable()->change();
            $table->string('role', 20)->default('pengurus')->after('password')->index();
            $table->foreignId('organizational_field_id')
                ->nullable()
                ->after('role')
                ->constrained('organizational_fields')
                ->restrictOnDelete();
            $table->boolean('is_active')->default(true)->after('organizational_field_id');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['organizational_field_id']);
            $table->dropUnique(['npp']);
            $table->dropUnique(['npm']);
            $table->dropColumn(['npp', 'npm', 'role', 'organizational_field_id', 'is_active', 'last_login_at']);
        });
    }
};
