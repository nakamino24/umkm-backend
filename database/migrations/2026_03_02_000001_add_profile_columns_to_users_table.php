<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'business_name')) {
                $table->string('business_name')->nullable()->after('name');
            }

            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 20)->nullable()->after('email');
            }

            if (! Schema::hasColumn('users', 'category')) {
                $table->string('category', 50)->nullable()->after('phone');
            }

            if (! Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar')->nullable()->after('password');
            }

            if (! Schema::hasColumn('users', 'address')) {
                $table->text('address')->nullable()->after('avatar');
            }

            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('address');
            }

            if (! Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [
                'business_name',
                'phone',
                'category',
                'avatar',
                'address',
                'is_active',
                'deleted_at',
            ];

            $existingColumns = array_values(array_filter($columns, fn (string $column) => Schema::hasColumn('users', $column)));

            if ($existingColumns !== []) {
                $table->dropColumn($existingColumns);
            }
        });
    }
};
