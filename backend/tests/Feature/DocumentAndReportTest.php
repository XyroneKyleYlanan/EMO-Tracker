<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DocumentAndReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploads_are_stored_privately_and_downloadable_through_the_api(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $event = Event::factory()->create();

        Sanctum::actingAs(User::factory()->officer()->create());

        $response = $this->postJson("/api/events/{$event->id}/documents", [
            'file' => UploadedFile::fake()->create('program.pdf', 100, 'application/pdf'),
        ])->assertCreated();

        $this->assertArrayNotHasKey('file_path', $response->json('document'));

        $document = Document::first();
        Storage::disk('local')->assertExists($document->file_path);
        $this->assertEmpty(Storage::disk('public')->allFiles());

        $this->get("/api/documents/{$document->id}/download")
            ->assertOk()
            ->assertDownload('program.pdf');
    }

    // Documents are records: officers add them, only the admin removes them.
    public function test_only_the_admin_can_delete_documents(): void
    {
        Storage::fake('local');
        $event = Event::factory()->create();
        Sanctum::actingAs(User::factory()->officer()->create());
        $this->postJson("/api/events/{$event->id}/documents", [
            'file' => UploadedFile::fake()->create('program.pdf', 100, 'application/pdf'),
        ])->assertCreated();
        $document = Document::first();

        $this->deleteJson("/api/documents/{$document->id}")->assertForbidden();
        Storage::disk('local')->assertExists($document->file_path);

        Sanctum::actingAs(User::factory()->admin()->create());
        $this->deleteJson("/api/documents/{$document->id}")->assertOk();
        Storage::disk('local')->assertMissing($document->file_path);
    }

    public function test_deleting_an_event_also_deletes_its_files(): void
    {
        Storage::fake('local');
        $event = Event::factory()->create();
        $other = Event::factory()->create();
        Sanctum::actingAs(User::factory()->admin()->create());
        foreach ([$event, $other] as $e) {
            $this->postJson("/api/events/{$e->id}/documents", [
                'file' => UploadedFile::fake()->create('program.pdf', 100, 'application/pdf'),
            ])->assertCreated();
        }

        $this->deleteJson("/api/events/{$event->id}")->assertOk();

        $this->assertSame([], Storage::disk('local')->allFiles($event->documentsFolder()));
        $this->assertCount(1, Storage::disk('local')->allFiles($other->documentsFolder()), "other events' files stay");
    }

    public function test_files_that_are_too_large_or_the_wrong_kind_get_a_clear_message(): void
    {
        Storage::fake('local');
        $event = Event::factory()->create();
        Sanctum::actingAs(User::factory()->officer()->create());

        $this->postJson("/api/events/{$event->id}/documents", [
            'file' => UploadedFile::fake()->create('scan.pdf', Document::MAX_UPLOAD_KB + 1, 'application/pdf'),
        ])->assertStatus(422)->assertJsonPath('errors.file.0', Document::tooLargeMessage());

        $this->postJson("/api/events/{$event->id}/documents", [
            'file' => UploadedFile::fake()->create('setup.exe', 10, 'application/octet-stream'),
        ])->assertStatus(422)->assertJsonPath('errors.file.0', "This kind of file isn't accepted. Use a PDF, Word, Excel, JPG or PNG file.");

        // Bigger than PHP accepts at all: the request arrives without the file.
        $this->call('POST', "/api/events/{$event->id}/documents", [], [], [], ['CONTENT_LENGTH' => 100 * 1024 * 1024, 'HTTP_ACCEPT' => 'application/json'])
            ->assertStatus(413)
            ->assertJsonPath('message', Document::tooLargeMessage());
    }

    public function test_something_deleted_meanwhile_gets_a_plain_message(): void
    {
        Sanctum::actingAs(User::factory()->officer()->create());

        $this->postJson('/api/events/999/tasks', ['name' => 'Book the sound system', 'due_date' => '2026-12-01'])
            ->assertNotFound()
            ->assertExactJson(['message' => 'This item no longer exists. It may have been deleted. Refresh the page.']);
    }

    public function test_report_handles_slashes_in_event_names(): void
    {
        $event = Event::factory()->create(['name' => 'Sports Fest 2026/2027']);

        Sanctum::actingAs(User::factory()->admin()->create());

        $this->get("/api/events/{$event->id}/report")
            ->assertOk()
            ->assertDownload('event-report-sports-fest-20262027.pdf');
    }
}
