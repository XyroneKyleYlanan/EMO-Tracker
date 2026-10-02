<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Seeder;

/**
 * Sample entries for the Schedule page: other offices' bookings that the EMO
 * schedules but doesn't prepare. All fictional; real data is never seeded.
 */
class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::where('role', 'admin')->value('id');

        // [days from today, last day (multi-day), start, end, name, department, venue, room/details, control #, remarks, cancelled]
        $entries = [
            [-12, null, '08:00', '17:00', 'CAS OJT Seminar', 'CAS', 'University Hall', null, '2026-0412', null, false],
            [-10, null, '13:00', '16:00', 'General Orientation for New Students', 'CBA', 'SOM Building', '504', '2026-0418', null, false],
            [-8, null, '07:00', '08:00', 'Weekly Flag Ceremony', 'Integrated School', 'IS Covered Court', null, null, null, false],
            [-6, null, '08:00', '12:00', 'Psychology Pinning Ceremony', 'CAS', 'University Hall', null, '2026-0420', 'Rescheduled from last week', false],
            [-4, null, '09:00', '12:00', 'Comelec Voters Education', 'Office of Student Affairs', 'SOM MPH', null, '2026-0431', null, false],
            [-2, null, '07:00', '17:00', 'Comprehensive Review Classes', 'CED', 'IS MPH', null, '2026-0402', null, false],
            [0, null, '07:00', '08:00', 'Weekly Flag Ceremony', 'Integrated School', 'IS Covered Court', null, null, null, false],
            [0, null, '08:00', '17:00', 'Medical Technology Enhancement Program', 'College of Medical Technology', 'PSB MPH', null, '2026-0433', null, false],
            [2, null, '10:00', '12:00', 'Student Orientation', 'College of Midwifery', 'PSB Building', 'Room 201', '2026-0440', null, false],
            [3, null, '08:00', '17:00', 'Guidance Seminar: Learn with a Heart', 'Guidance Office', 'University Hall', null, '2026-0441', null, false],
            [4, null, '06:00', '12:00', 'Battalion Formation (training day)', 'NSTP ROTC', 'NEU Open Field', null, '2026-0444', null, false],
            [5, null, '13:00', '17:00', 'Team Building', 'CICS', 'SOM Building', '506', '2026-0447', null, false],
            [7, null, '07:00', '08:00', 'Weekly Flag Ceremony', 'Integrated School', 'IS Covered Court', null, null, null, false],
            [7, null, '08:00', '17:00', 'National Journalism Workshop', 'College of Communication', 'University Hall', null, '2026-0450', 'Pencil booking', false],
            [8, null, '09:00', '12:00', 'Mental Health Program', 'Integrated School', 'IS MPH', null, '2026-0452', null, false],
            [9, 11, '08:00', '17:00', 'Nurses Week', 'College of Nursing', 'PSB MPH', null, '2026-0455', null, false],
            [10, null, '13:00', '15:00', 'PTCA Appreciation and Election of Officers', 'IS PTCA', 'IS MPH', null, '2026-0457', null, false],
            [11, null, '06:00', '12:00', 'Battalion Formation (training day)', 'NSTP ROTC', 'NEU Open Field', null, '2026-0444', null, false],
            [12, null, '08:00', '17:00', 'COE General Assembly', 'College of Engineering', 'PSB MPH', null, '2026-0459', 'Moved from SOM MPH', false],
            [14, null, '08:00', '12:00', 'Civil Service Examination Review Seminar', 'CAS', 'University Hall', null, '2026-0462', null, true],
            [15, null, '10:00', '15:00', 'Career Orientation', 'Administration', 'Main Library', null, '2026-0465', null, false],
            [16, null, null, null, 'Strategic Planning', 'NEU Staff', 'Main Building', 'Boardroom', null, 'Time to follow', false],
            [17, null, '08:00', '16:00', 'Guidance Week: Art Therapy', 'Guidance Office', '2nd Floor Lobby', null, '2026-0441', null, false],
            [18, null, '13:00', '17:00', 'Innovative Career and Business Convention', 'CBA', 'SOM Building', '504–507', '2026-0470', null, false],
            [21, null, '08:00', '17:00', 'Indigenous Month Celebration', 'CAS', 'University Hall', '& 2nd floor lobby', '2026-0472', null, false],
            [22, null, '09:00', '12:00', 'News Writing Seminar', 'CAS', 'Main Building', 'Multimedia Room C', '2026-0475', null, false],
            [24, null, '08:00', '17:00', 'Teachers Day Celebration', 'CED', 'IS MPH', null, '2026-0478', null, true],
            [28, 30, '09:00', '16:00', 'United Nations Week', 'Integrated School', 'IS MPH', null, '2026-0480', null, false],
            [35, null, '07:00', '17:00', 'Pinning Ceremony', 'CED', 'University Hall', null, '2026-0483', null, false],
            [42, null, '08:00', '17:00', 'Christmas Program', 'Administration', 'NEU Covered Court', null, '2026-0490', 'Pencil booking', false],
        ];

        foreach ($entries as [$offset, $lastDay, $start, $end, $name, $department, $venue, $details, $control, $remarks, $cancelled]) {
            $date = today()->addDays($offset)->toDateString();
            $endDate = $lastDay !== null ? today()->addDays($lastDay)->toDateString() : null;

            Event::create([
                'name' => $name,
                'department' => $department,
                'venue_id' => Venue::where('name', $venue)->value('id'),
                'venue_details' => $details,
                'event_date' => $date,
                'end_date' => $endDate,
                'event_time' => $start,
                'end_time' => $end,
                'control_number' => $control,
                'remarks' => $remarks,
                'needs_preparation' => false,
                'status' => $cancelled ? 'cancelled' : Event::statusForDate($date, $endDate),
                'created_by' => $adminId,
            ]);
        }
    }
}
