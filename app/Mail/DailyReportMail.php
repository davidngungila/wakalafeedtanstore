<?php

namespace App\Mail;

use App\Models\DailyOpening;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class DailyReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public readonly array $summary;

    public function __construct(public int $agentId, public Carbon $date)
    {
        $this->summary = $this->buildSummary($agentId, $date);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Daily report — '.$this->date->format('D, j M Y'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.daily-report',
        );
    }

    private function buildSummary(int $agentId, Carbon $date): array
    {
        $day = $date->copy()->startOfDay();

        $opening = DailyOpening::forAgentAndDate($agentId, $date)->first();

        $completed = Transaction::where('agent_id', $agentId)
            ->whereDate('created_at', $date)
            ->where('status', 'completed')
            ->get();

        $deposits = (float) $completed->whereIn('type', ['deposit', 'float_deposit', 'float_topup', 'bank_to_wallet'])->sum('amount');
        $withdrawals = (float) $completed->whereIn('type', ['withdrawal', 'wallet_to_bank', 'cash_to_float'])->sum('amount');

        $volume = (float) ($opening?->total_volume ?? $completed->sum('amount'));
        $commission = (float) ($opening?->total_commission ?? $completed->sum('commission'));
        $count = (int) ($opening?->total_transactions ?? $completed->count());
        $fees = (float) $completed->sum('fee');

        return [
            'date' => $date->format('D, j M Y'),
            'opening_cash' => (float) ($opening?->cash_opening ?? 0),
            'opening_float' => (float) ($opening?->totalFloatOpening() ?? 0),
            'closing_cash' => $opening?->cash_closing !== null ? (float) $opening->cash_closing : null,
            'closing_float' => $opening?->totalFloatClosing() !== null ? (float) $opening->totalFloatClosing() : null,
            'deposits' => $deposits,
            'withdrawals' => $withdrawals,
            'volume' => $volume,
            'commission' => $commission,
            'fees' => $fees,
            'count' => $count,
            'net_revenue' => $commission - $fees,
            'cash_available' => (float) (cash_point()?->cash_balance ?? 0),
            'float_available' => agent_total_float(cash_point()),
        ];
    }
}
