<!DOCTYPE html>
<html>
<head>
    <title>Leave Request</title>
</head>
<body>
@if ($type == 'request')
        <p>Dear Admin,</p>
        <p>{{ $user->first_name }} has requested a leave.</p>
    @else
        <p>Dear {{ $user->first_name }},</p>
        <p>Your leave request has been updated.</p>
    @endif
    
    <p><strong>Title:</strong> {{ $leave->title }}</p>
    <p><strong>Category:</strong> {{ $leave->category }}</p>
    <p><strong>Start Date:</strong> {{ $leave->start_date }}</p>
    <p><strong>End Date:</strong> {{ $leave->end_date }}</p>
    @if ($leave->category == 'complimentary')
        <p><strong>Complimentary Date:</strong> {{ $leave->complimentary_date }}</p>
    @endif
    <p><strong>Description:</strong> {{ $leave->description }}</p>
    <p><strong>Approval Status:</strong> {{ $leave->approval_status }}</p>
    @if ($type == 'request')
        <p>Please review and approve or reject the leave request.</p>
    @endif
</body>
</html> 