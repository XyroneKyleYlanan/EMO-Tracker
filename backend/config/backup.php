<?php

return [

    /*
    | Where backups are saved. Point this at a USB drive or another disk
    | (e.g. BACKUP_PATH=E:\emo-backups or /Volumes/USB/emo-backups) so a
    | broken laptop doesn't take the backups with it.
    */
    'path' => env('BACKUP_PATH', storage_path('app/private/backups')),

    // How many backups to keep; older ones are deleted automatically.
    'keep' => (int) env('BACKUP_KEEP', 14),

    // Make one backup a day, the first time the app is used that day.
    'daily' => (bool) env('BACKUP_DAILY', true),

];
