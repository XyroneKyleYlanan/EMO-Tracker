<?php

namespace App\Console\Commands;

use Illuminate\Foundation\Console\ServeCommand as LaravelServeCommand;

/**
 * "php artisan serve", with room for the 10 MB documents the app accepts.
 * PHP allows only 2 MB uploads unless told otherwise, and the server that
 * "serve" starts doesn't see settings given to artisan itself, so the larger
 * limits are passed to it directly. This works the same on Windows and Mac.
 */
class ServeCommand extends LaravelServeCommand
{
    #[\Override]
    protected function serverCommand()
    {
        // The command starts with the PHP program; its settings go right after it.
        $command = parent::serverCommand();
        array_splice($command, 1, 0, ['-d', 'upload_max_filesize=12M', '-d', 'post_max_size=16M']);

        return $command;
    }
}
