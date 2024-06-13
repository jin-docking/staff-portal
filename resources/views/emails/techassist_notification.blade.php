<!DOCTYPE html>
<html>
<head>
    <title>Leave Request</title>
</head>
<body>
@if ($type == 'request')
        <p>Dear Admin,</p>
        <p>{{ $user->first_name }} has requested for a technical assitance.</p>
    @else
        <p>Dear {{ $user->first_name }},</p>
        <p>Your techinical assistance request status has been updated</p>
    @endif
    
    <p><strong>Title:</strong> {{ $mailData->title }}</p>
    <p><strong>Status:</strong> {{ $mailData->status }}</p>
    <p><strong>Description:</strong> {{ $mailData->description }}</p>
    @if ($type == 'request')
        <p>Please review and take appropriate action.</p>
    @endif
</body>
</html> 