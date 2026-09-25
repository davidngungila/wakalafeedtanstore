<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\SmsSender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class SmsCustomerController extends Controller
{
    public function index(Request $request, SmsSender $sender): JsonResponse
    {
        $search = trim((string) ($request->validate([
            'q' => ['nullable', 'string', 'max:120'],
        ])['q'] ?? ''));

        $transactions = Transaction::query()
            ->select(['customer_name', 'customer_phone'])
            ->whereNotNull('customer_phone')
            ->where('customer_phone', '<>', '')
            ->where('customer_phone', '<>', 'UNKNOWN')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");
            }))
            ->latest('id')
            ->limit(200)
            ->get();

        $customers = [];
        foreach ($transactions as $transaction) {
            try {
                $phone = $sender->normalizeRecipient((string) $transaction->customer_phone);
            } catch (InvalidArgumentException) {
                continue;
            }

            if (! isset($customers[$phone])) {
                $customers[$phone] = [
                    'name' => $transaction->customer_name ?: 'Unnamed customer',
                    'phone' => $phone,
                ];
            }

            if (count($customers) >= 25) {
                break;
            }
        }

        return response()->json(['customers' => array_values($customers)]);
    }
}
