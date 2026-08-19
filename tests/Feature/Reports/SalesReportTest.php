<?php

namespace Tests\Feature\Reports;

use App\Models\SalesOrder;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class SalesReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function actingAsRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user);

        return $user;
    }

    public function test_sales_report_lists_orders_in_period_and_excludes_cancelled(): void
    {
        $this->actingAsRole('staff_finance');

        // Order dalam periode: 1 paid + 1 confirmed (dua-duanya dihitung).
        SalesOrder::factory()->create([
            'status' => SalesOrder::STATUS_PAID,
            'order_date' => today(),
            'subtotal' => 100_000,
            'tax' => 11_000,
            'shipping' => 5_000,
            'grand_total' => 116_000,
        ]);
        SalesOrder::factory()->create([
            'order_date' => today(),
            'subtotal' => 50_000,
            'grand_total' => 50_000,
        ]);

        // Dikecualikan: cancelled + order di luar periode.
        SalesOrder::factory()->create(['status' => SalesOrder::STATUS_CANCELLED, 'order_date' => today()]);
        SalesOrder::factory()->create(['order_date' => today()->subMonth()]);

        $this->get(route('reports.sales', [
            'from' => today()->startOfMonth()->toDateString(),
            'to' => today()->endOfMonth()->toDateString(),
        ]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('reports/sales')
                ->has('report.orders', 2)
                ->where('report.total_orders', 2)
                ->where('report.total_subtotal', 150_000)
                ->where('report.total_tax', 11_000)
                ->where('report.total_shipping', 5_000)
                ->where('report.total_grand_total', 166_000)
                ->where('report.orders.0.grand_total', 116_000));
    }

    public function test_sales_report_pdf_downloads(): void
    {
        $this->actingAsRole('admin');

        SalesOrder::factory()->create(['order_date' => today()]);

        $this->get(route('reports.sales.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_sales_report_excel_downloads(): void
    {
        $this->actingAsRole('admin');

        SalesOrder::factory()->create(['order_date' => today()]);

        $this->get(route('reports.sales.excel'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_staff_gudang_is_forbidden(): void
    {
        $this->actingAsRole('staff_gudang');

        $this->get(route('reports.sales'))->assertForbidden();
        $this->get(route('reports.sales.pdf'))->assertForbidden();
        $this->get(route('reports.sales.excel'))->assertForbidden();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('reports.sales'))->assertRedirect(route('login'));
    }
}
