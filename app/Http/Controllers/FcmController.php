<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Database\QueryException;

class FcmController extends Controller
{
    public function updateDeviceToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        try {
            auth()->user()->update(['fcm_token' => $request->fcm_token]);
            return response()->json(['message' => 'Device token updated successfully']);
        } catch (QueryException $e) {
            return response()->json(['message' => 'Error updating device token'], 500);
        }
    }

    public function sendFcmNotification(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string',
            'body' => 'required|string',
        ]);

        try {
            $user = User::findOrFail($request->user_id);
            $fcm = $user->fcm_token;

            if (!$fcm) {
                return response()->json(['message' => 'User does not have a device token'], 400);
            }

            $title = $request->title;
            $description = $request->body;
            $projectId = config('services.fcm.project_id');

            $credentialsFilePath = Storage::path('app/json/file.json');
            $client = new GoogleClient();
            $client->setAuthConfig($credentialsFilePath);
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
            $client->refreshTokenWithAssertion();
            $token = $client->getAccessToken();

            $access_token = $token['access_token'];

            $headers = [
                "Authorization: Bearer $access_token",
                'Content-Type: application/json'
            ];

            $data = [
                "message" => [
                    "token" => $fcm,
                    "notification" => [
                        "title" => $title,
                        "body" => $description,
                    ],
                ]
            ];
            $payload = json_encode($data);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            $response = curl_exec($ch);
            $err = curl_error($ch);
            curl_close($ch);

            if ($err) {
                return response()->json([
                    'message' => 'Curl Error: ' . $err
                ], 500);
            } else {
                // Store the notification in the database
                Notification::create([
                    'user_id' => $user->id,
                    'title' => $title,
                    'body' => $description,
                    'read' => false,
                ]);

                return response()->json([
                    'message' => 'Notification has been sent',
                    'response' => json_decode($response, true)
                ]);
            }
        } catch (QueryException $e) {
            return response()->json(['message' => 'Error sending notification'], 500);
        }
    }

    public function getNotificationCount()
    {
        try {
            $count = auth()->user()->notifications()->where('read', false)->count();
        } catch (QueryException $e) {
            $count = 0;
        }

        return response()->json([
            'unread_count' => $count
        ]);
    }

    public function index()
    {
        try {
            $notifications = auth()->user()->notifications()->orderBy('created_at', 'desc')->paginate(10);
            $unreadCount = auth()->user()->notifications()->where('read', false)->count();
        } catch (QueryException $e) {
            $notifications = collect([]);
            $unreadCount = 0;
        }
        
        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    public function markNotificationAsRead($notificationId)
    {
        try {
            $notification = auth()->user()->notifications()->findOrFail($notificationId);
            $notification->update(['read' => true]);
            return redirect()->back()->with('success', 'Notification marked as read.');
        } catch (QueryException $e) {
            return redirect()->back()->with('error', 'Error marking notification as read.');
        }
    }

    public function markAllNotificationsAsRead()
    {
        try {
            auth()->user()->notifications()->where('read', false)->update(['read' => true]);
            return redirect()->back()->with('success', 'All notifications marked as read.');
        } catch (QueryException $e) {
            return redirect()->back()->with('error', 'Error marking all notifications as read.');
        }
    }

    public function deleteNotification($notificationId)
    {
        try {
            $notification = auth()->user()->notifications()->findOrFail($notificationId);
            $notification->delete();
            return redirect()->back()->with('success', 'Notification deleted.');
        } catch (QueryException $e) {
            return redirect()->back()->with('error', 'Error deleting notification.');
        }
    }

    public function getNotifications()
    {
        try {
            $notifications = auth()->user()->notifications()->orderBy('created_at', 'desc')->get();
            return response()->json($notifications);
        } catch (QueryException $e) {
            return response()->json(['message' => 'Error retrieving notifications'], 500);
        }
    }
}