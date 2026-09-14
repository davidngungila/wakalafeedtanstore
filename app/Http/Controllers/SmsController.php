<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Network;
use App\Models\SmsMessage;
use Illuminate\Http\Request;
use Illuminate\Http\StreamedResponse;
use Illuminate\View\View;

class SmsController extends Controller
{
    public function index(Request $request): View
    {
        $query = SmsMessage::with(['device', 'network', 'transaction']);

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('processing_status', $request->input('status'));
        }

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

        $today = [
            'received' => SmsMessage::whereDate('server_received_at', today())->count(),
            'processed' => SmsMessage::whereDate('server_received_at', today())->where('processing_status', 'processed')->count(),
            'failed' => SmsMessage::whereDate('server_received_at', today())->where('processing_status', 'failed')->count(),
            'duplicates' => SmsMessage::whereDate('server_received_at', today())->where('is_duplicate', true)->count(),
            'pending' => SmsMessage::whereDate('server_received_at', today())->whereIn('processing_status', ['received', 'identified', 'parsed'])->count(),
        ];

        return view('sms.index', [
            'messages' => $messages,
            'today' => $today,
            'devices' => Device::orderBy('name')->get(['id', 'name']),
            'networks' => Network::orderBy('name')->get(['id', 'name', 'color']),
            'filters' => $request->only(['status', 'network', 'device', 'q']),
        ]);
    }

    /**
     * Server-Sent Events feed: pushes newly ingested SMS to the monitor page
     * so the dashboard updates without manual refresh.
     */
    public function stream(Request $request): StreamedResponse
    {
        $since = $request->integer('since', 0);

        return response()->stream(function () use ($since) {
            $lastId = $since;

            while (true) {
                $rows = SmsMessage::with(['device', 'network'])
                    ->where('id', '>', $lastId)
                    ->orderBy('id')
                    ->take(50)
                    ->get();

                foreach ($rows as $row) {
                    echo 'id: '.$row->id."\n";
                    echo 'data: '.json_encode([
                        'sms_id' => $row->id,
                        'sender' => $row->sender,
                        'network' => $row->network?->name,
                        'network_color' => $row->network?->color,
                        'device' => $row->device?->name,
                        'status' => $row->is_duplicate ? 'duplicate' : $row->processing_status,
                        'reference' => $row->transaction_reference,
                        'type' => $row->transaction_type ? txn_type_label($row->transaction_type) : null,
                        'amount' => $row->amount ? money($row->amount) : null,
                        'customer' => $row->customer_name,
                        'customer_phone' => $row->customer_phone,
                        'server_received_at' => $row->server_received_at->toIso8601String(),
                    ], JSON_UNESCAPED_SLASHES)."\n\n";

                    $lastId = $row->id;
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
}
