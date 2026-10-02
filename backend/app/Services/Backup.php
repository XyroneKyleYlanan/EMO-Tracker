<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

/**
 * Backs up everything the EMO would lose with the laptop: the database and the
 * uploaded documents, in one .zip. Written in plain PHP (no mysqldump), so it
 * works the same on Laragon, Homebrew, or any machine that can run the app.
 */
class Backup
{
    // Short-lived tables whose rows are not worth restoring. Their structure is
    // still saved. Restoring without old login tokens also signs everyone out.
    private const SKIP_ROWS = ['cache', 'cache_locks', 'sessions', 'jobs', 'job_batches', 'failed_jobs', 'personal_access_tokens'];

    private const DOCUMENTS = 'documents';

    /**
     * A named snapshot (e.g. "fresh-import") is kept until someone deletes it;
     * plain daily backups are cleaned up after the newest 14.
     */
    public static function create(?string $name = null): string
    {
        $dir = config('backup.path');
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new RuntimeException("Can't create the backup folder: {$dir}");
        }

        $label = $name ? preg_replace('/[^A-Za-z0-9_-]+/', '-', trim($name)) : null;
        $path = $dir.DIRECTORY_SEPARATOR.($label
            ? 'emo-snapshot-'.self::database().'-'.$label.'.zip'
            : self::prefix().now()->format('Y-m-d_His').'.zip');
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Can't write the backup file: {$path}");
        }

        $zip->addFromString('database.json', json_encode(self::dumpDatabase(), JSON_UNESCAPED_UNICODE));

        $disk = Storage::disk('local');
        foreach ($disk->allFiles(self::DOCUMENTS) as $file) {
            $zip->addFile($disk->path($file), $file);
        }

        $zip->close();
        self::prune();

        return $path;
    }

    /**
     * Which database a backup was made from, e.g. "emo_tracker".
     */
    public static function databaseOf(string $path): ?string
    {
        $zip = new ZipArchive;
        if (! is_file($path) || $zip->open($path) !== true) {
            return null;
        }
        $dump = json_decode((string) $zip->getFromName('database.json'), true);
        $zip->close();

        return $dump['database'] ?? null;
    }

    /**
     * Replaces the whole database and the uploaded documents with the backup.
     */
    public static function restore(string $path): void
    {
        $zip = new ZipArchive;
        if (! is_file($path) || $zip->open($path) !== true) {
            throw new RuntimeException("Not a readable backup file: {$path}");
        }

        $dump = json_decode((string) $zip->getFromName('database.json'), true);
        if (! is_array($dump) || ($dump['driver'] ?? null) !== DB::getDriverName()) {
            $zip->close();
            throw new RuntimeException('This backup is not for this kind of database.');
        }

        Schema::withoutForeignKeyConstraints(function () use ($dump) {
            foreach (self::tables() as $table) {
                Schema::drop($table);
            }
            foreach ($dump['tables'] as $table) {
                foreach ($table['schema'] as $statement) {
                    DB::unprepared($statement);
                }
                foreach (array_chunk($table['rows'], 200) as $chunk) {
                    DB::table($table['name'])->insert($chunk);
                }
            }
        });

        $disk = Storage::disk('local');
        $disk->deleteDirectory(self::DOCUMENTS);
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (str_starts_with($name, self::DOCUMENTS.'/') && ! str_contains($name, '..')) {
                $disk->put($name, $zip->getFromIndex($i));
            }
        }
        $zip->close();
    }

    /**
     * @return array<int, string> every backup in the folder, newest first
     */
    public static function all(): array
    {
        $files = glob(config('backup.path').DIRECTORY_SEPARATOR.'emo-{backup,snapshot}-*.zip', GLOB_BRACE) ?: [];
        usort($files, fn ($a, $b) => [filemtime($b), $b] <=> [filemtime($a), $a]);

        return $files;
    }

    /**
     * @return array<int, string> this database's backups, newest first
     */
    public static function mine(): array
    {
        return array_values(array_filter(self::all(), fn ($file) => str_starts_with(basename($file), self::prefix())));
    }

    // Each database keeps its own newest backups, so one can't crowd out another.
    private static function prune(): void
    {
        foreach (array_slice(self::mine(), max(1, config('backup.keep'))) as $old) {
            @unlink($old);
        }
    }

    // Only this database's automatic backups; snapshots are never pruned.
    private static function prefix(): string
    {
        return 'emo-backup-'.self::database().'-';
    }

    private static function database(): string
    {
        return preg_replace('/[^A-Za-z0-9_]+/', '', basename(DB::getDatabaseName()));
    }

    private static function dumpDatabase(): array
    {
        $tables = [];
        foreach (self::tables() as $name) {
            $tables[] = [
                'name' => $name,
                'schema' => self::schema($name),
                'rows' => in_array($name, self::SKIP_ROWS, true)
                    ? []
                    : DB::table($name)->get()->map(fn ($row) => (array) $row)->all(),
            ];
        }

        return [
            'driver' => DB::getDriverName(),
            'database' => DB::getDatabaseName(),
            'created_at' => now()->toIso8601String(),
            'tables' => $tables,
        ];
    }

    private static function tables(): array
    {
        return DB::getDriverName() === 'sqlite'
            ? DB::table('sqlite_master')->where('type', 'table')->where('name', 'not like', 'sqlite_%')->pluck('name')->all()
            : collect(DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'"))->map(fn ($row) => array_values((array) $row)[0])->all();
    }

    private static function schema(string $table): array
    {
        if (DB::getDriverName() === 'sqlite') {
            return DB::table('sqlite_master')->where('tbl_name', $table)->whereNotNull('sql')
                ->orderByRaw("type = 'table' desc")->pluck('sql')->all();
        }

        return [array_values((array) DB::selectOne("SHOW CREATE TABLE `{$table}`"))[1]];
    }
}
