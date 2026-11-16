<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Role;
use App\Models\User;
use App\Services\SettingService;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ExpenseCategorySeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsSettingsActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, CategorySeeder::class, ExpenseCategorySeeder::class]);
    }

    public function test_admin_can_view_a_period_report_and_download_csv(): void
    {
        $admin = $this->userWithRole(Role::ADMIN);
        $this->actingAs($admin)->get(route('admin.reports.index'))->assertOk()->assertSee('Rapport des ventes')->assertSee('Mouvements de stock');
        $this->actingAs($admin)->get(route('admin.reports.export', ['type' => 'sales', 'period' => 'this_month']))->assertDownload();
    }

    public function test_employee_cannot_access_reports_or_settings(): void
    {
        $employee = $this->userWithRole(Role::EMPLOYEE);
        $this->actingAs($employee)->get(route('admin.reports.index'))->assertForbidden();
        $this->actingAs($employee)->get(route('admin.settings'))->assertForbidden();
    }

    public function test_admin_can_update_commerce_settings_and_currency_is_available_to_views(): void
    {
        $admin = $this->userWithRole(Role::ADMIN);
        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'business_name' => 'Boutique Démo', 'business_phone' => '01020304', 'business_email' => 'demo@example.test',
            'business_address' => 'Centre-ville', 'business_country' => 'Sénégal', 'currency' => 'EUR',
            'date_format' => 'Y-m-d', 'default_minimum_stock' => '2.500',
        ])->assertRedirect(route('admin.settings'));

        $settings = app(SettingService::class);
        $this->assertSame('EUR', $settings->get('currency'));
        $this->assertSame('2.500', $settings->get('default_minimum_stock'));
        $this->assertDatabaseHas('settings', ['key' => 'business_name', 'value' => 'Boutique Démo']);
    }

    public function test_activity_log_records_old_and_new_values_for_changes(): void
    {
        $admin = $this->userWithRole(Role::ADMIN);
        $category = Category::factory()->create(['name' => 'Avant']);
        $this->actingAs($admin);
        $category->update(['name' => 'Après']);

        $log = ActivityLog::query()->where('subject_type', Category::class)->where('subject_id', $category->id)->where('action', 'updated')->latest('id')->firstOrFail();
        $this->assertSame('Avant', $log->old_values['name']);
        $this->assertSame('Après', $log->new_values['name']);
        $this->assertSame($admin->id, $log->user_id);
    }

    private function userWithRole(string $role): User
    {
        return User::factory()->create(['role_id' => Role::where('name', $role)->value('id')]);
    }
}
