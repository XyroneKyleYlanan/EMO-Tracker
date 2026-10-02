<?php

use App\Services\Backup;
use App\Services\ScheduleImport;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('backup:run {--name= : Save a named snapshot that is never deleted automatically}', function () {
    $path = Backup::create($this->option('name'));
    $this->info(($this->option('name') ? 'Snapshot' : 'Backup').' saved: '.$path);
})->purpose('Back up the database and uploaded documents');

Artisan::command('backup:list', function () {
    $files = Backup::all();
    if (! $files) {
        return $this->warn('No backups yet. Folder: '.config('backup.path'));
    }
    $this->table(['Backup', 'Database', 'Size'], array_map(
        fn ($f) => [basename($f), Backup::databaseOf($f) ?? '?', round(filesize($f) / 1024).' KB'],
        $files,
    ));
})->purpose('List saved backups, newest first');

Artisan::command('backup:restore {file? : Backup file name, snapshot name, or path (default: the newest backup of this database)} {--force : Skip the confirmation}', function () {
    $file = $this->argument('file');
    $snapshot = $file ? collect(Backup::all())->first(fn ($f) => str_starts_with(basename($f), 'emo-snapshot-') && str_ends_with(basename($f), "-{$file}.zip")) : null;
    $path = $file
        ? (is_file($file) ? $file : ($snapshot ?? config('backup.path').DIRECTORY_SEPARATOR.$file))
        : (Backup::mine()[0] ?? null);

    if (! $path || ! is_file($path)) {
        return $this->error('Backup not found. Run "php artisan backup:list" to see your backups.');
    }

    $this->warn('This replaces ALL current data and documents with: '.basename($path));
    $from = Backup::databaseOf($path);
    if ($from && $from !== DB::getDatabaseName()) {
        $this->warn("Careful: this backup is from the \"{$from}\" database, but you're restoring into \"".DB::getDatabaseName().'".');
    }
    if (! $this->option('force') && ! $this->confirm('Restore it?')) {
        return $this->info('Cancelled. Nothing was changed.');
    }

    Backup::restore($path);
    $this->info('Restored. Everyone will need to log in again.');
})->purpose('Restore a backup (replaces all current data)');

Artisan::command('schedule:import {file : The schedule spreadsheet (.xlsx)} {--dry-run : Check the file without saving anything}', function () {
    $file = $this->argument('file');
    if (! is_file($file)) {
        return $this->error("File not found: {$file}");
    }

    $dryRun = (bool) $this->option('dry-run');
    $this->info(($dryRun ? 'Checking' : 'Importing').' '.basename($file).'…');
    ['summary' => $summary, 'review' => $review] = ScheduleImport::run($file, $dryRun);

    foreach ($summary['tabs'] as $tab) {
        $this->line('  Tab '.$tab);
    }
    $this->newLine();
    $this->line("  Rows read:            {$summary['read']}");
    $this->line('  '.($dryRun ? 'Would be imported:    ' : 'Imported:             ').$summary['imported']);
    $this->line("  Already in system:    {$summary['duplicates']}");
    $this->line("  Not imported:         {$summary['skipped']}");
    $this->line('  New venues to sort:   '.count($summary['new_venues']).($summary['new_venues'] ? ' ('.implode(', ', $summary['new_venues']).')' : ''));

    if ($review) {
        // Saved in the project's private storage (never committed); it holds real names.
        $out = fopen('php://temp', 'w+');
        fputcsv($out, ['Tab', 'Row', 'Date', 'Event', 'What to check']);
        foreach ($review as $item) {
            fputcsv($out, array_values($item));
        }
        rewind($out);
        $csv = 'imports/import-review-'.now()->format('Y-m-d_His').'.csv';
        Storage::disk('local')->put($csv, stream_get_contents($out));
        fclose($out);
        $this->newLine();
        $this->warn('  '.count($review).' rows need a quick check. List saved to:');
        $this->line('  '.Storage::disk('local')->path($csv));
    }

    if ($dryRun) {
        $this->newLine();
        $this->comment('  Nothing was saved. Run again without --dry-run to import.');
    }
})->purpose("Import the EMO's schedule spreadsheet");
