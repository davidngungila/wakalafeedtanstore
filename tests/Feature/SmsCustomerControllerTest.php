<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmsCustomerControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_normalized_unique_transaction_customers(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Transaction::factory()->create([
            'customer_name' => 'Asha Omari',
            'customer_phone' => '0712345678',
        ]);
        Transaction::factory()->create([
            'customer_name' => 'Asha O.',
            'customer_phone' => '+255712345678',
        ]);
        Transaction::factory()->create([
            'customer_name' => 'Unusable number',
            'customer_phone' => 'UNKNOWN',
        ]);

        $response = $this->actingAs($admin)->getJson(route('settings.sms.customers', ['q' => 'Asha']));

        $response->assertOk()->assertExactJson([
            'customers' => [
                ['name' => 'Asha O.', 'phone' => '255712345678'],
            ],
        ]);
    }

    public function test_forbids_customer_search_for_non_admin(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $this->actingAs($supervisor)
            ->getJson(route('settings.sms.customers'))
            ->assertForbidden();
    }

    public function test_returns_401_when_customer_search_has_no_authentication(): void
    {
        $this->getJson(route('settings.sms.customers'))->assertUnauthorized();
    }

    public function test_returns_422_when_customer_search_is_too_long(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->getJson(route('settings.sms.customers', ['q' => str_repeat('a', 121)]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('q');
    }
}
