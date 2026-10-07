<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;

class Document extends Model
{
    use HasFactory;

    // The largest file the app accepts, in kilobytes (10 MB).
    public const MAX_UPLOAD_KB = 10240;

    protected $fillable = [
        'event_id',
        'uploaded_by',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
    ];

    protected $hidden = ['file_path'];

    /**
     * "This file is larger than 10 MB." The limit is lower if PHP on this
     * computer allows less (it starts at 2 MB unless the app raised it).
     */
    public static function tooLargeMessage(): string
    {
        $bytes = min(self::MAX_UPLOAD_KB * 1024, UploadedFile::getMaxFilesize());

        return 'This file is larger than '.max(1, (int) floor($bytes / 1048576)).' MB.';
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
