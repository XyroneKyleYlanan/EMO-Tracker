<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\Event;
use App\Models\Setting;
use App\Models\Task;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'name' => 'EMO Administrator',
            'email' => 'admin@emo.test',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $officer1 = User::create([
            'name' => 'Maria Santos',
            'email' => 'maria.officer@emo.test',
            'password' => Hash::make('password123'),
            'role' => 'officer',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $officer2 = User::create([
            'name' => 'Juan Dela Cruz',
            'email' => 'juan.officer@emo.test',
            'password' => Hash::make('password123'),
            'role' => 'officer',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $staffData = [
            ['Anna Reyes', 'anna.staff@emo.test'],
            ['Mark Tan', 'mark.staff@emo.test'],
            ['Joy Garcia', 'joy.staff@emo.test'],
            ['Paolo Cruz', 'paolo.staff@emo.test'],
            ['Liza Ramos', 'liza.staff@emo.test'],
            ['Ben Aquino', 'ben.staff@emo.test'],
            ['Carla Lim', 'carla.staff@emo.test'],
        ];

        $staff = [];
        foreach ($staffData as [$name, $email]) {
            $staff[] = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make('password123'),
                'role' => 'staff',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        }

        // Event 1: COMPLETED (past) — University Foundation Day
        $foundation = Event::create([
            'name' => 'University Foundation Day',
            'description' => 'Annual celebration of NEU\'s founding anniversary.',
            'venue' => 'NEU Main Quadrangle',
            'event_date' => now()->subDays(20)->toDateString(),
            'event_time' => '08:00:00',
            'budget' => 75000,
            'status' => 'completed',
            'created_by' => $admin->id,
        ]);
        $foundation->staff()->attach([$staff[0]->id, $staff[1]->id, $staff[2]->id]);
        $this->makeTasks($foundation->id, [
            ['Book main hall', 'done', 'high', $staff[0]->id, -25],
            ['Print programs', 'done', 'medium', $staff[1]->id, -23],
            ['Coordinate guests', 'done', 'high', $staff[2]->id, -22],
            ['Sound system setup', 'done', 'medium', $staff[0]->id, -21],
        ]);

        // Event 2: ON TRACK (GREEN) — Freshmen Orientation, 14 days away, mostly done, all assigned
        $orientation = Event::create([
            'name' => 'Freshmen Orientation 2026',
            'description' => 'Welcome orientation for incoming first-year students.',
            'venue' => 'NEU Auditorium',
            'event_date' => now()->addDays(14)->toDateString(),
            'event_time' => '09:00:00',
            'budget' => 50000,
            'status' => 'upcoming',
            'created_by' => $officer1->id,
        ]);
        $orientation->staff()->attach([$staff[0]->id, $staff[3]->id, $staff[4]->id]);
        $this->makeTasks($orientation->id, [
            ['Prepare welcome kits', 'done', 'medium', $staff[0]->id, 10],
            ['Invite keynote speaker', 'done', 'high', $staff[3]->id, 7],
            ['Reserve auditorium', 'done', 'high', $staff[4]->id, 5],
            ['Print ID lanyards', 'in_progress', 'low', $staff[0]->id, 12],
            ['Setup registration booth', 'pending', 'medium', $staff[3]->id, 13],
        ]);

        // Event 3: AT RISK (YELLOW) — Sports Fest, 5 days away
        $sportsFest = Event::create([
            'name' => 'Sports Fest Opening Ceremony',
            'description' => 'Annual inter-college sports festival kickoff.',
            'venue' => 'NEU Gymnasium',
            'event_date' => now()->addDays(5)->toDateString(),
            'event_time' => '14:00:00',
            'budget' => 120000,
            'status' => 'upcoming',
            'created_by' => $officer2->id,
        ]);
        $sportsFest->staff()->attach([$staff[1]->id, $staff[5]->id]);
        $this->makeTasks($sportsFest->id, [
            ['Coordinate with college reps', 'done', 'high', $staff[1]->id, 2],
            ['Print event tarpaulins', 'done', 'medium', $staff[5]->id, 3],
            ['Rent sound equipment', 'in_progress', 'high', $staff[1]->id, 4],
            ['Brief torch bearers', 'pending', 'medium', null, 4],
            ['Arrange refreshments', 'pending', 'low', $staff[5]->id, 4],
        ]);

        // Event 4: CRITICAL (RED) — Faculty Recognition Night, 1 day away, barely started
        $facultyNight = Event::create([
            'name' => 'Faculty Recognition Night',
            'description' => 'Annual awards ceremony honoring outstanding faculty members.',
            'venue' => 'NEU Function Hall',
            'event_date' => now()->addDays(1)->toDateString(),
            'event_time' => '18:00:00',
            'budget' => 90000,
            'status' => 'upcoming',
            'created_by' => $officer1->id,
        ]);
        $facultyNight->staff()->attach([$staff[6]->id]);
        $this->makeTasks($facultyNight->id, [
            ['Print awardee certificates', 'pending', 'high', $staff[6]->id, 1],
            ['Confirm dinner catering', 'pending', 'high', null, 1],
            ['Setup stage decor', 'pending', 'medium', null, 1],
            ['Brief emcees', 'pending', 'medium', null, 1],
        ]);

        // Event 5: YELLOW (edge case — 0 tasks) — Inter-College Quiz Bee
        $quizBee = Event::create([
            'name' => 'Inter-College Quiz Bee',
            'description' => 'Annual academic competition between colleges.',
            'venue' => 'NEU Lecture Hall A',
            'event_date' => now()->addDays(30)->toDateString(),
            'event_time' => '13:00:00',
            'budget' => 25000,
            'status' => 'upcoming',
            'created_by' => $officer2->id,
        ]);
        $quizBee->staff()->attach([$staff[2]->id]);

        // Uploaded files aren't committed, so generate the sample document's file here.
        $samplePath = 'documents/sample/foundation-day-program.pdf';
        Storage::disk('local')->put($samplePath, Pdf::loadHTML(
            '<h1>University Foundation Day</h1>'
            .'<p>NEU Main Quadrangle &middot; 8:00 AM</p>'
            .'<ol><li>Opening prayer and national anthem</li><li>Welcome remarks</li>'
            .'<li>Founding anniversary address</li><li>Awarding of loyalty service awards</li>'
            .'<li>Closing remarks</li></ol>'
        )->output());

        Document::create([
            'event_id' => $foundation->id,
            'uploaded_by' => $officer1->id,
            'file_name' => 'foundation-day-program.pdf',
            'file_path' => $samplePath,
            'file_size' => Storage::disk('local')->size($samplePath),
            'mime_type' => 'application/pdf',
        ]);

        Setting::set('school_year_start_month', '8');
        Setting::set('school_year_end_month', '5');
        Setting::set('current_school_year', '2025-2026');
    }

    private function makeTasks(int $eventId, array $tasks): void
    {
        foreach ($tasks as [$name, $status, $priority, $assignedTo, $dueDateOffset]) {
            Task::create([
                'event_id' => $eventId,
                'name' => $name,
                'due_date' => now()->addDays($dueDateOffset)->toDateString(),
                'status' => $status,
                'priority' => $priority,
                'assigned_to' => $assignedTo,
            ]);
        }
    }
}
