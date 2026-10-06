<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Event;
use App\Models\User;
use App\Services\Backup;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

// DatabaseMigrations rather than RefreshDatabase: restoring drops and recreates
// tables, which can't happen inside the test's wrapping transaction.
class BackupTest extends TestCase
{
    use DatabaseMigrations;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir().'/emo-backup-test-'.uniqid();
        config(['backup.path' => $this->dir]);
        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        parent::tearDown();
    }

    public function test_restore_brings_back_data_and_documents(): void
    {
        $kept = Event::factory()->create(['name' => 'Foundation Day']);
        Storage::disk('local')->put('documents/event_1/program.pdf', 'PDF BYTES');
        Document::create([
            'event_id' => $kept->id, 'uploaded_by' => $kept->created_by, 'file_name' => 'program.pdf',
            'file_path' => 'documents/event_1/program.pdf', 'file_size' => 9, 'mime_type' => 'application/pdf',
        ]);

        $backup = Backup::create();

        // Things go wrong after the backup...
        $kept->delete();
        Event::factory()->create(['name' => 'Added later']);
        Storage::disk('local')->delete('documents/event_1/program.pdf');

        Backup::restore($backup);

        $this->assertSame(['Foundation Day'], Event::pluck('name')->all());
        $this->assertSame(1, Document::count());
        $this->assertSame('PDF BYTES', Storage::disk('local')->get('documents/event_1/program.pdf'));
    }

    public function test_restoring_a_backup_from_an_older_version_brings_it_up_to_date(): void
    {
        Event::factory()->create(['name' => 'Kept']);

        // A backup taken before the latest database update...
        $this->artisan('migrate:rollback', ['--step' => 1])->assertSuccessful();
        Backup::create('older-version');
        $this->artisan('migrate')->assertSuccessful();

        $this->artisan('backup:restore', ['file' => 'older-version', '--force' => true])->assertSuccessful();

        $this->assertSame(count(File::files(database_path('migrations'))), DB::table('migrations')->count());
        $this->assertFalse(Schema::hasTable('event_staff'));
        $this->assertSame(['Kept'], Event::pluck('name')->all());
    }

    public function test_only_the_newest_backups_are_kept(): void
    {
        config(['backup.keep' => 2]);

        foreach (['08:00:00', '09:00:00', '10:00:00'] as $time) {
            $this->travelTo(today()->setTimeFromTimeString($time));
            Backup::create();
        }

        $this->assertCount(2, Backup::all());
        $this->assertStringContainsString('_100000', Backup::all()[0]);
    }

    public function test_named_snapshots_are_never_pruned_and_restore_by_name(): void
    {
        config(['backup.keep' => 1]);
        Event::factory()->create(['name' => 'Real schedule']);
        $snapshot = Backup::create('fresh-import');

        foreach (['08:00:00', '09:00:00'] as $time) {
            $this->travelTo(today()->setTimeFromTimeString($time));
            Backup::create();
        }
        $this->assertFileExists($snapshot);
        $this->assertCount(1, Backup::mine());

        Event::query()->delete();
        $this->artisan('backup:restore', ['file' => 'fresh-import', '--force' => true])->assertSuccessful();
        $this->assertSame(['Real schedule'], Event::pluck('name')->all());
    }

    public function test_each_database_keeps_its_own_backups(): void
    {
        config(['backup.keep' => 1]);
        File::ensureDirectoryExists($this->dir);
        File::put($this->dir.'/emo-backup-other_db-2020-01-01_000000.zip', 'another database');
        touch($this->dir.'/emo-backup-other_db-2020-01-01_000000.zip', now()->addYear()->timestamp);

        $path = Backup::create();
        $this->travel(1)->hours();
        Backup::create();

        $this->assertFileExists($this->dir.'/emo-backup-other_db-2020-01-01_000000.zip', 'not pruned by this database');
        $this->assertFileDoesNotExist($path);
        $this->assertCount(1, Backup::mine());
        $this->assertSame(DB::getDatabaseName(), Backup::databaseOf(Backup::mine()[0]));
    }

    public function test_the_first_request_of_the_day_makes_one_backup(): void
    {
        config(['backup.daily' => true]);
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson('/api/events')->assertOk();
        $this->getJson('/api/events')->assertOk();
        $this->assertCount(1, Backup::all());

        $this->travel(1)->days();
        $this->getJson('/api/events')->assertOk();
        $this->assertCount(2, Backup::all());
    }

    public function test_a_failing_backup_never_breaks_the_app(): void
    {
        config(['backup.daily' => true, 'backup.path' => '/dev/null/not-a-folder']);
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson('/api/events')->assertOk();
    }

    public function test_restore_rejects_files_that_are_not_backups(): void
    {
        $fake = $this->dir.'/not-a-backup.zip';
        File::ensureDirectoryExists($this->dir);
        File::put($fake, 'hello');

        $this->expectException(RuntimeException::class);
        Backup::restore($fake);
    }
}
