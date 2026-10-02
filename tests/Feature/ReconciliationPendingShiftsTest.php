<?php

namespace Tests\Feature;

use App\Models\Network;
use App\Models\Reconciliation;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Shift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Reconciliation is per (date, shift). These cover the pending-shift list on
 * the reconciliation index, which previously bucketed activity by calendar date
 * and sent the *current* shift for every row.
 */
class ReconciliationPendingShiftsTest extends TestCase
{
    use RefreshDatabase;

    private function supervisor(): User
    {
        return User::factory()->create([
            'email' => 'shifts@moneyagent.local',
            'role' => 'supervisor',
            'is_active' => true,
        ]);
    }

    /**
     * A completed transaction at a specific moment on a specific business date.
     */
    private function transactionAt(Carbon $at, float $amount = 10_000): Transaction
    {
        return Transaction::factory()->create([
            'agent_id' => $this->cashPoint()->id,
            'network_id' => Network::factory(),
            'type' => 'deposit',
            'status' => 'completed',
            'amount' => $amount,
            'fee' => 0,
            'commission' => 0,
            'created_at' => $at,
        ]);
    }

    private function reconcile(string $date, string $shift): Reconciliation
    {
        return Reconciliation::create([
            'agent_id' => $this->cashPoint()->id,
            'reconciliation_date' => $date,
            'shift' => $shift,
            'status' => 'reconciled',
        ]);
    }

    public function test_activity_is_listed_per_shift_not_merged_into_calendar_days(): void
    {
        // Pick a date that is safely in the past so it is not the open shift.
        $day = today()->subDays(3);

        $this->transactionAt($day->copy()->setTime(9, 0));    // morning
        $this->transactionAt($day->copy()->setTime(15, 0));   // morning
        $this->transactionAt($day->copy()->setTime(21, 0));   // night, same calendar date
        $this->transactionAt($day->copy()->addDay()->setTime(2, 0)); // night, after midnight

        $this->actingAs($this->supervisor())
            ->get(route('reconciliation.index'))
            ->assertOk()
            ->assertSee('Shifts with transactions not reconciled')
            // Both shifts of that business date are pending, not one merged row.
            ->assertSee('Morning', false)
            ->assertSee('Night', false);
    }

    /**
     * The core bug: reconciling the morning shift must not hide the night shift
     * of the same date, whose money would otherwise never be reconciled.
     */
    public function test_reconciling_the_morning_shift_leaves_the_night_shift_pending(): void
    {
        $day = today()->subDays(3)->toDateString();

        $this->transactionAt(Carbon::parse($day)->setTime(9, 0));
        $this->transactionAt(Carbon::parse($day)->setTime(21, 0));

        $this->reconcile($day, Shift::MORNING);

        $this->actingAs($this->supervisor())
            ->get(route('reconciliation.index'))
            ->assertOk()
            ->assertSee('Shifts with transactions not reconciled')
            ->assertSee('Night', false);
    }

    public function test_a_full_day_reconciliation_covers_every_shift_of_that_date(): void
    {
        $day = today()->subDays(3)->toDateString();

        $this->transactionAt(Carbon::parse($day)->setTime(9, 0));
        $this->transactionAt(Carbon::parse($day)->setTime(21, 0));

        $this->reconcile($day, Shift::FULL);

        $this->actingAs($this->supervisor())
            ->get(route('reconciliation.index'))
            ->assertOk()
            ->assertDontSee('Shifts with transactions not reconciled');
    }

    /**
     * A night shift runs 20:00 to 07:59, so activity after midnight belongs to
     * the previous business date. Bucketing by DATE(created_at) put it on the
     * wrong day.
     */
    public function test_after_midnight_activity_is_attributed_to_the_previous_business_date(): void
    {
        $day = today()->subDays(3);

        $this->transactionAt($day->copy()->setTime(22, 0));   // night of $day
        $this->transactionAt($day->copy()->addDay()->setTime(3, 0)); // night of $day, not the next day

        $this->reconcile($day->copy()->addDay()->toDateString(), Shift::FULL);

        // Reconciling the *next* calendar day must not cover the night shift that
        // started the evening before.
        $this->actingAs($this->supervisor())
            ->get(route('reconciliation.index'))
            ->assertOk()
            ->assertSee('Shifts with transactions not reconciled')
            ->assertSee('Night', false);
    }

    /**
     * The till is still open, so the running shift must not be offered.
     */
    public function test_the_currently_running_shift_is_not_offered(): void
    {
        $current = Shift::current();

        $this->transactionAt(Carbon::parse($current['date'])->setTime(
            $current['shift'] === Shift::NIGHT ? 22 : 10,
            0
        ));

        $this->actingAs($this->supervisor())
            ->get(route('reconciliation.index'))
            ->assertOk()
            ->assertDontSee('Shifts with transactions not reconciled');
    }

    /**
     * The reconcile link must carry the shift being listed, not whatever shift
     * happens to be running now.
     */
    public function test_the_reconcile_link_carries_the_listed_shift(): void
    {
        $day = today()->subDays(3)->toDateString();

        $this->transactionAt(Carbon::parse($day)->setTime(9, 0));
        $this->transactionAt(Carbon::parse($day)->setTime(21, 0));

        // Each pending row must link to its own shift, not to whatever shift is
        // running right now.
        $links = $this->pendingLinks();

        $this->assertContains($day.'|'.Shift::MORNING, $links);
        $this->assertContains($day.'|'.Shift::NIGHT, $links);

        $current = Shift::current();

        if ($current['shift'] !== Shift::NIGHT) {
            $this->assertNotContains(
                $day.'|'.$current['shift'],
                array_values(array_filter($links, fn (string $l): bool => str_starts_with($l, $day.'|'.Shift::NIGHT))),
                'A night row must link to the night shift, not the current shift.'
            );
        }
    }

    /**
     * Every "reconciliation/create" link on the index as "date|shift".
     *
     * @return array<int, string>
     */
    private function pendingLinks(): array
    {
        $html = $this->actingAs($this->supervisor())
            ->get(route('reconciliation.index'))
            ->getContent();

        preg_match_all('/href="([^"]*reconciliation\/create[^"]*)"/', $html, $matches);

        $links = [];

        foreach ($matches[1] as $href) {
            parse_str((string) parse_url(html_entity_decode($href), PHP_URL_QUERY), $query);

            if (isset($query['date'], $query['shift'])) {
                $links[] = $query['date'].'|'.$query['shift'];
            }
        }

        return $links;
    }
}
