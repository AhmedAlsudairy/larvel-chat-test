@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">
        Notifications
        <span class="badge bg-danger" id="notificationBadge">{{ $unreadCount ?? 0 }}</span>
    </h1>

    <div class="row">
        <div class="col-md-6">
            <h2>Send Notification</h2>
            <form id="sendNotificationForm">
                @csrf
                <div class="mb-3">
                    <label for="user_id" class="form-label">User ID</label>
                    <input type="number" class="form-control" id="user_id" name="user_id" required>
                </div>
                <div class="mb-3">
                    <label for="title" class="form-label">Title</label>
                    <input type="text" class="form-control" id="title" name="title" required>
                </div>
                <div class="mb-3">
                    <label for="body" class="form-label">Body</label>
                    <textarea class="form-control" id="body" name="body" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Send Notification</button>
            </form>
        </div>

        <div class="col-md-6">
            <h2>Notifications List</h2>
            @if(isset($notifications) && $notifications->count() > 0)
                <ul class="list-group" id="notificationsList">
                    @foreach($notifications as $notification)
                        <li class="list-group-item {{ $notification->read ? '' : 'list-group-item-light' }}">
                            <h5>{{ $notification->title }}</h5>
                            <p>{{ $notification->body }}</p>
                            <small>{{ $notification->created_at->diffForHumans() }}</small>
                            @if(!$notification->read)
                                <form action="{{ route('notifications.markAsRead', $notification->id) }}" method="POST" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary">Mark as Read</button>
                                </form>
                            @endif
                            <form action="{{ route('notifications.delete', $notification->id) }}" method="POST" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
                {{ $notifications->links() }}
            @else
                <p>No notifications found. The notifications table might not exist.</p>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    function updateNotificationBadge() {
        $.get('{{ route('notifications.count') }}', function(data) {
            $('#notificationBadge').text(data.unread_count);
            if (data.unread_count > 0) {
                $('#notificationBadge').show();
            } else {
                $('#notificationBadge').hide();
            }
        });
    }

    $('#sendNotificationForm').submit(function(e) {
        e.preventDefault();
        $.post('{{ route('fcm.sendNotification') }}', $(this).serialize(), function(response) {
            alert('Notification sent successfully');
            location.reload();
        }).fail(function(xhr) {
            alert('Error sending notification: ' + xhr.responseJSON.message);
        });
    });

    setInterval(updateNotificationBadge, 60000); // Update badge every minute
});
</script>
@endsection