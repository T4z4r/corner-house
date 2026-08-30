<?php

namespace Tests\Feature;

use Tests\TestCase;

class DatabaseMigrationCompatibilityTest extends TestCase
{
    public function test_jobs_migration_uses_mysql_safe_string_lengths_for_indexes(): void
    {
        $migration = file_get_contents(database_path('migrations/0001_01_01_000002_create_jobs_table.php'));

        $this->assertIsString($migration);
        $this->assertStringContainsString("string('queue', 100)->index()", $migration);
        $this->assertStringContainsString("string('id', 191)->primary()", $migration);
        $this->assertStringContainsString("string('uuid', 191)->unique()", $migration);
        $this->assertStringContainsString("string('connection', 100)", $migration);
        $this->assertStringContainsString("string('queue', 100)", $migration);
    }

    public function test_audit_logs_migration_uses_mysql_safe_string_lengths_for_composite_index(): void
    {
        $migration = file_get_contents(database_path('migrations/2026_08_28_142452_create_audit_logs_table.php'));

        $this->assertIsString($migration);
        $this->assertStringContainsString("string('module', 50)->nullable()->index()", $migration);
        $this->assertStringContainsString("string('record_type', 50)->nullable()", $migration);
        $this->assertStringContainsString("string('record_id', 50)->nullable()", $migration);
        $this->assertStringContainsString("index(['module', 'record_type', 'record_id'])", $migration);
    }

    public function test_permissions_migration_uses_mysql_safe_string_lengths_for_role_and_permission_indexes(): void
    {
        $migration = file_get_contents(database_path('migrations/2026_08_28_142037_create_permission_tables.php'));

        $this->assertIsString($migration);
        $this->assertStringContainsString("string('name', 100);", $migration);
        $this->assertStringContainsString("string('guard_name', 100);", $migration);
        $this->assertStringContainsString("unique(['name', 'guard_name'])", $migration);
    }

    public function test_channel_migration_uses_mysql_safe_string_lengths_for_composite_indexes(): void
    {
        $migration = file_get_contents(database_path('migrations/2026_08_29_100100_create_channel_tables.php'));

        $this->assertIsString($migration);
        $this->assertStringContainsString("string('provider', 50);", $migration);
        $this->assertStringContainsString("string('external_room_id', 100)->nullable();", $migration);
        $this->assertStringContainsString("string('external_id', 100)->nullable()->index();", $migration);
        $this->assertStringContainsString("index(['provider', 'external_room_id'])", $migration);
    }

    public function test_reservations_migration_uses_mysql_safe_string_lengths_for_external_booking_index(): void
    {
        $migration = file_get_contents(database_path('migrations/2026_08_28_160926_create_reservations_table.php'));

        $this->assertIsString($migration);
        $this->assertStringContainsString("string('external_channel', 50)->nullable();", $migration);
        $this->assertStringContainsString("string('external_booking_id', 100)->nullable();", $migration);
        $this->assertStringContainsString("unique(['external_channel', 'external_booking_id']", $migration);
    }
}
