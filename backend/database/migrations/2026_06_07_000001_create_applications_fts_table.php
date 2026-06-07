<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // FTS5 virtual table for full-text search on applications.
        // content= keeps this as an external-content table pointing to `applications`.
        DB::statement(<<<'SQL'
            CREATE VIRTUAL TABLE IF NOT EXISTS applications_fts
            USING fts5(
                name,
                email,
                position,
                cover_letter,
                content=applications,
                content_rowid=id
            )
        SQL);

        // Triggers keep the FTS index in sync with the applications table.
        DB::statement(<<<'SQL'
            CREATE TRIGGER IF NOT EXISTS applications_fts_insert
            AFTER INSERT ON applications BEGIN
                INSERT INTO applications_fts(rowid, name, email, position, cover_letter)
                VALUES (new.id, new.name, new.email, new.position, new.cover_letter);
            END
        SQL);

        DB::statement(<<<'SQL'
            CREATE TRIGGER IF NOT EXISTS applications_fts_update
            AFTER UPDATE ON applications BEGIN
                INSERT INTO applications_fts(applications_fts, rowid, name, email, position, cover_letter)
                VALUES ('delete', old.id, old.name, old.email, old.position, old.cover_letter);
                INSERT INTO applications_fts(rowid, name, email, position, cover_letter)
                VALUES (new.id, new.name, new.email, new.position, new.cover_letter);
            END
        SQL);

        DB::statement(<<<'SQL'
            CREATE TRIGGER IF NOT EXISTS applications_fts_delete
            AFTER DELETE ON applications BEGIN
                INSERT INTO applications_fts(applications_fts, rowid, name, email, position, cover_letter)
                VALUES ('delete', old.id, old.name, old.email, old.position, old.cover_letter);
            END
        SQL);

        // Backfill existing rows into the FTS index.
        DB::statement(<<<'SQL'
            INSERT INTO applications_fts(rowid, name, email, position, cover_letter)
            SELECT id, name, email, position, cover_letter FROM applications
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS applications_fts_delete');
        DB::statement('DROP TRIGGER IF EXISTS applications_fts_update');
        DB::statement('DROP TRIGGER IF EXISTS applications_fts_insert');
        DB::statement('DROP TABLE IF EXISTS applications_fts');
    }
};
