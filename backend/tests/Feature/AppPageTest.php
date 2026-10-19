<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AppPageTest extends TestCase
{
    public function test_every_page_address_gets_the_built_app_but_the_api_does_not(): void
    {
        $public = sys_get_temp_dir().'/emo-public-'.uniqid();
        $this->app->usePublicPath($public);

        // Not built yet: a plain message instead of a blank page.
        $this->get('/')->assertStatus(503)->assertSee('npm run build');

        File::ensureDirectoryExists("{$public}/app");
        File::put("{$public}/app/index.html", '<div id="root"></div>');

        foreach (['/', '/login', '/admin/schedule', '/officer/events'] as $page) {
            $this->get($page)->assertOk()->assertSee('<div id="root"></div>', false);
        }
        $this->getJson('/api/no-such-thing')->assertNotFound()->assertDontSee('id="root"', false);
        $this->get('/up')->assertOk()->assertDontSee('id="root"', false);

        File::deleteDirectory($public);
    }
}
