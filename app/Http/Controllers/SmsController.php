<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Network;
use App\Models\SmsMessage;
use App\Models\Transaction;
use App\Services\Sms\SmsTransactionExtractor;
use App\Services\SmsParser;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SmsController extends Controller
{
    public function index(Request $request): View
    {
        $query = SmsMessage::with(['device', 'network', 'transaction', 'deviceLine.network']);

        $status = $request->input('status', 'all');
        $this->applyStatusFilter($query, $status);

        if ($request->filled('network') && $request->input('network') !== 'all') {
            $query->where('network_id', $request->input('network'));
        }

        if ($request->filled('device') && $request->input('device') !== 'all') {
            $query->where('device_id', $request->input('device'));
        }

        if ($request->filled('q')) {
            $needle = $request->input('q');
            $query->where(function ($sub) use ($needle) {
                $sub->where('message_body', 'like', "%{$needle}%")
                    ->orWhere('sender', 'like', "%{$needle}%")
                    ->orWhere('transaction_reference', 'like', "%{$needle}%")
                    ->orWhere('customer_phone', 'like', "%{$needle}%");
            });
        }

        $messages = $query->latest('server_received_at')->limit(200)->get();

        $todayBase = SmsMessage::whereDate('server_received_at', today());

        $today = [
            'received' => (clone $todayBase)->count(),
            'processed' => (clone $todayBase)->where('processing_status', 'RECORDED')->count(),
            'pending' => (clone $todayBase)->whereIn('processing_status', ['RECEIVED', 'PARSED'])->count(),
            'stored' => (clone $todayBase)->whereIn('processing_status', ['NEEDS_REVIEW', 'FAILED'])
                ->where('processing_error', 'like', '%financial template%')->count(),
            'failed' => (clone $todayBase)->where('processing_status', 'FAILED')->where(function ($q) {
                $q->whereNull('processing_error')
                    ->orWhere('processing_error', 'not like', '%financial template%');
            })->count(),
            'duplicates' => (clone $todayBase)->where(fn ($q) => $q->where('is_duplicate', true)->orWhere('processing_status', 'DUPLICATE'))->count(),
        ];

        $counts = [
            'all' => SmsMessage::count(),
            'processed' => SmsMessage::where('processing_status', 'RECORDED')->count(),
            'pending' => SmsMessage::whereIn('processing_status', ['RECEIVED', 'PARSED'])->count(),
            'stored' => SmsMessage::whereIn('processing_status', ['NEEDS_REVIEW', 'FAILED'])
                ->where('processing_error', 'like', '%financial template%')->count(),
            'failed' => SmsMessage::where('processing_status', 'FAILED')->where(function ($q) {
                $q->whereNull('processing_error')
                    ->orWhere('processing_error', 'not like', '%financial template%');
            })->count(),
            'duplicate' => SmsMessage::where(fn ($q) => $q->where('is_duplicate', true)->orWhere('processing_status', 'DUPLICATE'))->count(),
        ];

        return view('sms.index', [
            'messages' => $messages,
            'today' => $today,
            'counts' => $counts,
            'devices' => Device::orderBy('name')->get(['id', 'name']),
            'networks' => Network::orderBy('name')->get(['id', 'name', 'color']),
            'filters' => $request->only(['status', 'network', 'device', 'q']),
        ]);
    }

    /**
     * Apply a tab status filter to the SMS query. Tabs use display labels
     * (pending, stored, duplicate) instead of raw processing_status values.
     */
    private function applyStatusFilter($query, string $status): void
    {
        switch ($status) {
            case 'processed':
                $query->where('processing_status', 'RECORDED');
                break;
            case 'pending':
                $query->whereIn('processing_status', ['RECEIVED', 'PARSED']);
                break;
            case 'stored':
                $query->whereIn('processing_status', ['NEEDS_REVIEW', 'FAILED'])
                    ->where('processing_error', 'like', '%financial template%');
                break;
            case 'failed':
                $query->where('processing_status', 'FAILED')
                    ->where(function ($q) {
                        $q->whereNull('processing_error')
                            ->orWhere('processing_error', 'not like', '%financial template%');
                    });
                break;
            case 'duplicate':
                $query->where(fn ($q) => $q->where('is_duplicate', true)->orWhere('processing_status', 'DUPLICATE'));
                break;
            default:
                break;
        }
    }

    /**
     * Server-Sent Events feed for every page that listens to SMS:
     *
     *   ?since=<max sms id the client has>&updated=<last update watermark>
     *
     * Emits both freshly ingested messages (id > since) and messages whose
     * processing state changed since the watermark (e.g. RECEIVED -> RECORDED
     * after parsing). Clients upsert by id, so replayed rows are harmless.
     */
    public function stream(Request $request): StreamedResponse
    {
        $since = $request->integer('since', 0);

        $updated = $request->input('updated');
        $updated = is_string($updated) && $updated !== '' && strtotime($updated) !== false
            ? Carbon::parse($updated)->subSecond()
            : now()->subMinutes(2);

        return response()->stream(function () use ($since, $updated) {
            $lastId = $since;
            $lastUpdated = $updated;

            $with = ['device', 'network', 'transaction', 'deviceLine.network'];

            while (true) {
                $newRows = SmsMessage::with($with)
                    ->where('id', '>', $lastId)
                    ->orderBy('id')
                    ->take(50)
                    ->get();

                $updatedRows = SmsMessage::with($with)
                    ->where('id', '<=', $lastId)
                    ->where('updated_at', '>', $lastUpdated)
                    ->orderBy('id')
                    ->take(100)
                    ->get();

                $rows = $newRows->concat($updatedRows)->keyBy('id');

                foreach ($rows as $row) {
                    $payload = $this->streamPayload($row);

                    echo 'id: '.$row->id."\n";
                    echo 'data: '.json_encode($payload, JSON_UNESCAPED_SLASHES)."\n\n";

                    $lastId = max($lastId, (int) $row->id);

                    $rowUpdated = $row->updated_at?->toIso8601String();
                    if ($rowUpdated !== null && $rowUpdated > $lastUpdated) {
                        $lastUpdated = $rowUpdated;
                    }
                }

                echo ": ping\n\n";
                ob_flush();
                flush();

                if (connection_aborted()) {
                    break;
                }

                sleep(2);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function process(Request $request, SmsMessage $smsMessage): JsonResponse|RedirectResponse
    {
        if ($smsMessage->transaction_id) {
            $message = 'This SMS has already been processed into transaction '.$smsMessage->transaction->reference;

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return back()->withErrors(['sms' => $message]);
        }

        $validated = $request->validate([
            'network_id' => ['required', 'exists:networks,id'],
            'type' => ['required', 'in:deposit,withdrawal,send_money,bill_payment,airtime,data,bank_to_wallet,wallet_to_bank'],
            'amount' => ['required', 'numeric', 'min:1'],
            'customer_name' => ['nullable', 'string', 'max:120'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'reference' => ['nullable', 'string', 'max:40'],
        ]);

        $extractor = new SmsTransactionExtractor;
        $provider = (new SmsParser)->identifyProvider($smsMessage->sender);
        $network = Network::find($validated['network_id']);

        // Try strict extraction first for audit, but allow manual override
        $existingRef = $smsMessage->transaction_reference ?? $validated['reference'] ?? null;

        if ($existingRef && Transaction::where('network_id', $validated['network_id'])->where('provider_reference', $existingRef)->exists()) {
            $message = 'A transaction with reference '.$existingRef.' already exists for this network.';

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return back()->withErrors(['sms' => $message]);
        }

        $transactionService = app(TransactionService::class);
        $agent = $smsMessage->agent ?? cash_point();

        if ($agent === null) {
            $message = 'Cash point not configured.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return back()->withErrors(['sms' => $message]);
        }

        $reference = $validated['reference'] ?? $smsMessage->transaction_reference ?? $existingRef ?? null;

        $transaction = DB::transaction(function () use ($smsMessage, $validated, $agent, $reference, $transactionService) {
            $txn = $transactionService->process(
                [
                    'network_id' => $validated['network_id'],
                    'type' => $validated['type'],
                    'customer_name' => $validated['customer_name'] ?? $smsMessage->customer_name,
                    'customer_phone' => $validated['customer_phone'],
                    'amount' => $validated['amount'],
                ],
                $agent,
                auth()->id(),
                'Via manual SMS process (SMS #'.$smsMessage->id.')',
                $reference,
            );

            $smsMessage->update([
                'processing_status' => 'RECORDED',
                'processing_error' => null,
                'transaction_id' => $txn->id,
                'transaction_reference' => $txn->provider_reference,
                'amount' => $txn->amount,
                'transaction_type' => $txn->type,
                'customer_name' => $txn->customer_name,
                'customer_phone' => $txn->customer_phone,
                'network_id' => $txn->network_id,
            ]);

            return $txn;
        });

        $this->recordAudit('SMS manually processed to transaction', 'SmsMessage', $smsMessage->id, [
            'sms_id' => $smsMessage->id,
            'transaction_id' => $transaction->id,
            'reference' => $transaction->reference,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'SMS processed to transaction '.$transaction->reference, 'transaction_id' => $transaction->id]);
        }

        return back()->with('status', 'SMS processed to transaction '.$transaction->reference);
    }

    /**
     * @return array<string, mixed>
     */
    private function streamPayload(SmsMessage $row): array
    {
        $label = sms_status_label($row->processing_status, $row->is_duplicate, $row->processing_error);

        return [
            'sms_id' => $row->id,
            'device_id' => $row->device_id,
            'device_route' => $row->device ? $row->device->getRouteKey() : null,
            'device' => $row->device?->name,
            'line' => $row->deviceLine?->displayName(),
            'sim_slot' => $row->sim_slot,
            'network_id' => $row->network_id,
            'network' => $row->network?->name,
            'network_color' => $row->network?->color,
            'sender' => $row->sender,
            'type' => $row->transaction_type ? txn_type_label($row->transaction_type) : null,
            'amount' => $row->amount ? money($row->amount) : null,
            'customer' => $row->customer_name,
            'customer_phone' => $row->customer_phone,
            'reference' => $row->transaction_reference,
            'txn_reference' => $row->transaction?->reference,
            'status' => strtolower($label),
            'status_key' => $label,
            'badge' => sms_status_badge($row->processing_status, $row->is_duplicate, $row->processing_error),
            'error' => $row->processing_error,
            'body' => $row->message_body,
            'time' => $row->server_received_at->format('H:i:s'),
            'datetime' => $row->server_received_at->format('D, j M Y · H:i:s'),
            'fulltime' => $row->server_received_at->format('H:i:s · d M Y'),
            'server_received_at' => $row->server_received_at->toIso8601String(),
            'is_today' => $row->server_received_at->isToday(),
            'updated_at' => $row->updated_at?->toIso8601String(),
        ];
    }
}
