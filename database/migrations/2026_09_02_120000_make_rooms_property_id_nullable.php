<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table): void {
            $this->dropPropertyForeignKey($table);

            $table->foreignId('property_id')->nullable()->change();

            $table->foreign('property_id')->references('id')->on('properties')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table): void {
            $this->dropPropertyForeignKey($table);

            $table->foreignId('property_id')->nullable(false)->change();

            $table->foreign('property_id')->references('id')->on('properties')->cascadeOnDelete();
        });
    }

    /**
     * Drop the foreign key on the property_id column regardless of its actual
     * name, which can differ across environments.
     */
    private function dropPropertyForeignKey(Blueprint $table): void
    {
        foreach (Schema::getForeignKeys('rooms') as $foreignKey) {
            if (in_array('property_id', $foreignKey['columns'], true) && $foreignKey['name'] !== null) {
                $table->dropForeign($foreignKey['name']);

                break;
            }
        }
    }
};
