<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureDailyOpeningSet;
use App\Models\DailyOpening;
use App\Models\Network;
use App\Models\NetworkBalance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CloseDayPageTest extends TestCase
{
    use RefreshDatabase;

    private ?User $user = null;

    private function user(): User
    {
        return $this->user ??= User::factory()->create([
            'email' => 'closer@moneyagent.local',
            'role' => 'cashier',
            'is_active' => true,
        ]);
    }

    private function openDay(array $openingAttributes = []): DailyOpening
    {
        $agent = $this->cashPoint();
        $network = Network::factory()->create(['name' => 'M-Pesa', 'color' => '#00a000']);

        NetworkBalance::create([
            'agent_id' => $agent->id,
            'network_id' => $network->id,
            'opening_balance' => 75000,
            'balance' => 75000,
        ]);

        return DailyOpening::create(array_merge([
            'agent_id' => $agent->id,
            'user_id' => $this->user()->id,
            'opening_date' => today(),
            'shift' => 'full',
            'cash_opening' => 250000,
            'float_openings' => [$network->id => 75000],
            'is_closed' => false,
        ], $openingAttributes));
    }

    public function test_close_day_page_renders_the_counting_form(): void
    {
        $opening = $this->openDay();

        $response = $this->actingAs($this->user())
            ->get(route('daily-opening.close-form', $opening))
            ->assertOk()
            ->assertSee('Close Day')
            ->assertSee('Counted closing cash')
            ->assertSee('Counted float per network')
            ->assertSee('M-Pesa')
            ->assertSee('Not reconciled');

        $html = $response->getContent();
        $formStart = strpos($html, 'id="closeDayForm"');
        $cashField = strpos($html, 'name="cash_closing"');
        $floatField = strpos($html, 'name="float_closings[');

        $this->assertNotFalse($formStart, 'The close-day form is missing.');
        $this->assertNotFalse($cashField, 'The counted cash field is missing.');
        $this->assertNotFalse($floatField, 'The counted float fields are missing.');
        $this->assertLessThan($cashField, $formStart, 'The counted cash field must sit inside the close-day form.');
        $this->assertGreaterThan($cashField, $floatField, 'The counted float fields must sit inside the close-day form.');
    }

    public function test_close_day_page_redirects_when_the_day_is_already_closed(): void
    {
        $opening = $this->openDay(['is_closed' => true, 'cash_closing' => 250000, 'closed_at' => now()]);

        $this->actingAs($this->user())
            ->withoutMiddleware(EnsureDailyOpeningSet::class)
            ->from(route('daily-opening.show', $opening))
            ->get(route('daily-opening.close-form', $opening))
            ->assertRedirect(route('daily-opening.show', $opening));
    }

    public function test_closing_the_day_persists_counted_balances(): void
    {
        $opening = $this->openDay();
        $network = Network::firstWhere('name', 'M-Pesa');

        $this->actingAs($this->user())
            ->from(route('daily-opening.close-form', $opening))
            ->put(route('daily-opening.close', $opening), [
                'cash_closing' => 250000,
                'float_closings' => [$network->id => 74000],
                'notes' => 'Counted twice, confirmed by supervisor.',
            ])
            ->assertRedirect(route('daily-opening.show', $opening));

        $fresh = $opening->fresh();
        $this->assertTrue($fresh->is_closed);
        $this->assertSame('250000.00', $fresh->cash_closing);
        $this->assertSame([$network->id => 74000], $fresh->float_closings);
        $this->assertNotNull($fresh->closed_at);
        $this->assertSame(74000.0, (float) $this->cashPoint()->balances()->first()->balance);
    }

    public function test_closing_rejects_a_day_with_an_uncounted_network(): void
    {
        $opening = $this->openDay();
        $counted = Network::firstWhere('name', 'M-Pesa');
        Network::factory()->create(['name' => 'HaloPesa', 'color' => '#0000ff']);

        $this->actingAs($this->user())
            ->from(route('daily-opening.close-form', $opening))
            ->put(route('daily-opening.close', $opening), [
                'cash_closing' => 250000,
                'float_closings' => [$counted->id => 75000],
            ])
            ->assertSessionHas('error');

        $this->assertFalse($opening->fresh()->is_closed);
    }

    public function test_closing_requires_a_note_when_cash_does_not_match(): void
    {
        $opening = $this->openDay();
        $network = Network::firstWhere('name', 'M-Pesa');

        $this->actingAs($this->user())
            ->from(route('daily-opening.close-form', $opening))
            ->put(route('daily-opening.close', $opening), [
                'cash_closing' => 240000,
                'float_closings' => [$network->id => 75000],
            ])
            ->assertSessionHas('error');

        $this->assertFalse($opening->fresh()->is_closed);

        $this->actingAs($this->user())
            ->from(route('daily-opening.close-form', $opening))
            ->put(route('daily-opening.close', $opening), [
                'cash_closing' => 240000,
                'float_closings' => [$network->id => 75000],
                'notes' => 'T shortfall reported to the supervisor.',
            ])
            ->assertRedirect(route('daily-opening.show', $opening));

        $this->assertTrue($opening->fresh()->is_closed);
    }

    public function test_close_day_page_submits_through_method_spoofing_like_a_browser_form(): void
    {
        $opening = $this->openDay();
        $network = Network::firstWhere('name', 'M-Pesa');

        $response = $this->actingAs($this->user())
            ->from(route('daily-opening.close-form', $opening))
            ->post(route('daily-opening.close', $opening), [
                '_method' => 'PUT',
                '_token' => csrf_token(),
                'cash_closing' => 250000,
                'float_closings' => [$network->id => 75000],
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertTrue($opening->fresh()->is_closed);
    }

    public function test_close_day_page_reports_the_fields_it_needs_when_a_value_is_missing(): void
    {
        $opening = $this->openDay();
        $network = Network::firstWhere('name', 'M-Pesa');

        $this->actingAs($this->user())
            ->from(route('daily-opening.close-form', $opening))
            ->post(route('daily-opening.close', $opening), [
                '_method' => 'PUT',
                'cash_closing' => '',
                'float_closings' => [$network->id => 75000],
            ])
            ->assertRedirect(route('daily-opening.close-form', $opening))
            ->assertSessionHasErrors('cash_closing');

        $this->assertFalse($opening->fresh()->is_closed);
    }

    public function test_day_page_links_to_the_close_day_page(): void
    {
        $opening = $this->openDay();

        $this->actingAs($this->user())
            ->get(route('daily-opening.show', $opening))
            ->assertOk()
            ->assertSee(route('daily-opening.close-form', $opening));
    }
}
