<?php

use App\Http\Controllers\Api\V1\DeviceApiController;
use App\Http\Controllers\Api\V1\SmsApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Device API (MobiControl Android app)
|--------------------------------------------------------------------------
| Every route authenticates the phone with its hashed device token via the
| `device.token` middleware. HTTPS is enforced in production.
*/

Route::prefix('v1')->middleware('device.token')->group(function () {
    Route::post('/devices/bootstrap', [DeviceApiController::class, 'bootstrap']);
    Route::get('/devices/me', [DeviceApiController::class, 'me']);
    Route::get('/sms/senders', [SmsApiController::class, 'senders']);

    Route::post('/sms/ingest', [SmsApiController::class, 'ingest']);

    Route::post('/heartbeat', [SmsApiController::class, 'heartbeat']);
});
