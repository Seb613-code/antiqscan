<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserLibraryAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitor_can_register_then_reach_the_email_verification_notice(): void
    {
        $this->get(route('register.create'))->assertOk();

        $this->post(route('register.store'), [
            'name' => 'Sébastien',
            'email' => 'sebastien@example.test',
            'password' => 'un-mot-de-passe-solide',
            'password_confirmation' => 'un-mot-de-passe-solide',
        ])->assertRedirect(route('verification.notice'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'sebastien@example.test']);
    }

    public function test_authenticated_header_shows_the_users_name_and_logout_action(): void
    {
        $user = User::factory()->create(['name' => 'Sébastien Vatinel']);

        $this->actingAs($user)
            ->get(route('books.index'))
            ->assertOk()
            ->assertSee('Sébastien Vatinel')
            ->assertSee('Se déconnecter')
            ->assertSee(route('logout'), false);
    }

    public function test_header_displays_the_antiqscan_ai_ligature_brand(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('books.index'))
            ->assertOk()
            ->assertSee('<title>AntIqscan</title>', false)
            ->assertSee('AntIqscan')
            ->assertSee('page-brand__logo', false)
            ->assertSee('aria-label="Monogramme AI"', false);
    }

    public function test_user_cannot_see_or_delete_another_users_book(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $book = Book::factory()->for($owner)->create();

        $this->actingAs($viewer)
            ->get(route('books.show', $book))
            ->assertNotFound();

        $this->actingAs($viewer)
            ->delete(route('books.destroy', $book))
            ->assertNotFound();

        $this->assertDatabaseHas('books', ['id' => $book->id, 'user_id' => $owner->id]);
    }
}
