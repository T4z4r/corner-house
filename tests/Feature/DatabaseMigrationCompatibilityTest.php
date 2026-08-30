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
}
