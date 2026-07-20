<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_redirects_anonymous_visitors_to_the_access_screen(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }
}
