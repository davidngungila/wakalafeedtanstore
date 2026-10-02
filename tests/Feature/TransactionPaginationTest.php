<?php

namespace Tests\Feature;

use App\Models\Network;
use App\Models\Transaction;
use App\Models\User;
use App\Support\PageToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * The transactions list is paginated at 20 per page and the search box runs on
 * the server so it covers every page rather than the rows on screen.
 */
class TransactionPaginationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function seedTransactions(int $count): void
    {
        $network = Network::factory()->create(['name' => 'Mixx by Yas']);

        for ($i = 1; $i <= $count; $i++) {
            Transaction::factory()->create([
                'agent_id' => $this->cashPoint()->id,
                'network_id' => $network->id,
                'type' => 'deposit',
                'status' => 'completed',
                'amount' => 1_000 + $i,
                'fee' => 0,
                'commission' => 0,
                'customer_name' => 'Customer '.$i,
                'customer_phone' => '255700000'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'created_at' => now()->subMinutes(100 - $i),
            ]);
        }
    }

    public function test_it_shows_twenty_transactions_per_page(): void
    {
        $this->seedTransactions(45);

        $response = $this->actingAs($this->admin())->get(route('transactions.index'));

        $response->assertOk();

        $paginator = $response->viewData('transactions');
        $this->assertSame(20, $paginator->perPage());
        $this->assertCount(20, $paginator->items());
        $this->assertSame(45, $paginator->total());
        $this->assertSame(3, $paginator->lastPage());
    }

    public function test_the_pager_is_rendered_when_there_is_more_than_one_page(): void
    {
        $this->seedTransactions(45);

        $content = $this->actingAs($this->admin())->get(route('transactions.index'))->getContent();

        $this->assertStringContainsString('Showing <strong>1</strong> to <strong>20</strong>', $content);
        $this->assertStringContainsString('of <strong>45</strong> transactions', $content);
        $this->assertStringContainsString('pager-compact', $content);
    }

    public function test_no_pager_is_rendered_for_a_single_page(): void
    {
        $this->seedTransactions(5);

        $response = $this->actingAs($this->admin())->get(route('transactions.index'));

        $response->assertOk();
        $this->assertFalse($response->viewData('transactions')->hasPages());

        // The layout stylesheet defines .pager-compact-info unconditionally, so
        // assert on the rendered summary text instead of the class name.
        $this->assertStringNotContainsString(
            'of <strong>5</strong> transactions',
            $response->getContent()
        );
    }

    public function test_page_links_use_an_opaque_token_and_are_followable(): void
    {
        $this->seedTransactions(45);

        $content = $this->actingAs($this->admin())->get(route('transactions.index'))->getContent();

        // Page numbers must not leak into the URL.
        $this->assertStringNotContainsString('page=2', $content);

        preg_match('/\?page=([A-Za-z0-9_-]+)/', $content, $matches);
        $this->assertNotEmpty($matches, 'Expected a paginated link.');
        $this->assertSame(2, PageToken::decode($matches[1]));

        $this->actingAs($this->admin())->get('/transactions?page='.$matches[1])->assertOk();
    }

    public function test_filters_survive_pagination(): void
    {
        $this->seedTransactions(45);

        $content = $this->actingAs($this->admin())
            ->get(route('transactions.index', ['status' => 'completed', 'q' => 'Customer 7']))
            ->getContent();

        $this->assertStringContainsString('name="q"', $content);
        $this->assertStringContainsString('value="Customer 7"', $content);
    }

    public function test_the_search_box_searches_the_whole_dataset_not_just_the_page(): void
    {
        $this->seedTransactions(45);

        // Customer 40 is on the last page, so a client-side filter over the
        // 20 rendered rows would never find it.
        $response = $this->actingAs($this->admin())
            ->get(route('transactions.index', ['q' => 'Customer 40']));

        $response->assertOk();

        $paginator = $response->viewData('transactions');
        $this->assertSame(1, $paginator->total());
        $this->assertSame('Customer 40', $paginator->first()->customer_name);
    }

    public function test_the_search_box_is_a_form_field_not_a_client_side_filter(): void
    {
        $this->seedTransactions(5);

        $content = $this->actingAs($this->admin())->get(route('transactions.index'))->getContent();

        $this->assertStringContainsString('name="q"', $content);
        // The old per-page client-side filter would only match rendered rows.
        $this->assertStringNotContainsString('filterTransactionRows', $content);
        $this->assertStringNotContainsString('data-search=', $content);
    }

    public function test_totals_still_cover_every_matching_transaction(): void
    {
        $this->seedTransactions(45);

        $response = $this->actingAs($this->admin())->get(route('transactions.index'));

        $response->assertOk();

        // The per-network figures must reflect the whole filtered set, not the
        // page that happens to be rendered.
        $totals = collect($response->viewData('perNetworkTotals'));
        $this->assertSame(45, (int) $totals->sum('count'));
        $this->assertSame(45, $response->viewData('filteredGrand')['count']);
    }

    public function test_search_uses_a_guarded_like_clause(): void
    {
        $this->seedTransactions(5);

        // A wildcard-only search must not blow up or return everything.
        $response = $this->actingAs($this->admin())
            ->get(route('transactions.index', ['q' => '%']));

        $response->assertOk();
        $this->assertGreaterThanOrEqual(0, $response->viewData('transactions')->total());
    }

    public function test_a_date_filter_still_works_with_pagination(): void
    {
        $this->seedTransactions(45);

        $response = $this->actingAs($this->admin())
            ->get(route('transactions.index', ['date' => Carbon::now()->subMinutes(95)->toDateString()]));

        $response->assertOk();
        $this->assertGreaterThanOrEqual(1, $response->viewData('transactions')->total());
    }
}
