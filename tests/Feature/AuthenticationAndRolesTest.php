<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationAndRolesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_guests_are_redirected_to_login_and_can_authenticate(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $role = Role::where('name', Role::ADMIN)->firstOrFail();
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect('/login');
    }

    public function test_only_administrators_can_open_user_management(): void
    {
        $employeeRole = Role::where('name', Role::EMPLOYEE)->firstOrFail();
        $employee = User::factory()->create(['role_id' => $employeeRole->id]);
        $this->actingAs($employee)->get('/admin/users')->assertForbidden();

        $adminRole = Role::where('name', Role::ADMIN)->firstOrFail();
        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        $this->actingAs($admin)->get('/admin/users')->assertOk();
    }

    public function test_invalid_login_is_rejected_with_a_french_message(): void
    {
        $this->from('/login')->post('/login', ['email' => 'inconnu@example.com', 'password' => 'incorrect'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');
    }

    public function test_administrator_can_create_an_employee_account(): void
    {
        $adminRole = Role::where('name', Role::ADMIN)->firstOrFail();
        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        $employeeRole = Role::where('name', Role::EMPLOYEE)->firstOrFail();

        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Awa Diop',
            'email' => 'awa@example.com',
            'role_id' => $employeeRole->id,
            'password' => 'motdepasse-solide',
            'password_confirmation' => 'motdepasse-solide',
        ])->assertRedirect('/admin/users');

        $employee = User::where('email', 'awa@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('motdepasse-solide', $employee->password));
        $this->assertTrue($employee->hasRole(Role::EMPLOYEE));
    }
}
