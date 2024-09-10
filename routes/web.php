<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FcmController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});



Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');


Route::middleware(['auth'])->group(function () {
    Route::get('/notifications', [FcmController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notificationId}/mark-as-read', [FcmController::class, 'markNotificationAsRead'])->name('notifications.markAsRead');
    Route::post('/notifications/mark-all-as-read', [FcmController::class, 'markAllNotificationsAsRead'])->name('notifications.markAllAsRead');
    Route::delete('/notifications/{notificationId}', [FcmController::class, 'deleteNotification'])->name('notifications.delete');
    Route::get('/notification-count', [FcmController::class, 'getNotificationCount'])->name('notifications.count');
    
    // FCM-specific routes
    Route::put('/update-device-token', [FcmController::class, 'updateDeviceToken'])->name('fcm.updateDeviceToken');
    Route::post('/send-fcm-notification', [FcmController::class, 'sendFcmNotification'])->name('fcm.sendNotification');
});