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

    public function test_report_handles_slashes_in_event_names(): void
    {
        $event = Event::factory()->create(['name' => 'Sports Fest 2026/2027']);

        Sanctum::actingAs(User::factory()->admin()->create());

        $this->get("/api/events/{$event->id}/report")
            ->assertOk()
            ->assertDownload('event-report-sports-fest-20262027.pdf');
    }
}
