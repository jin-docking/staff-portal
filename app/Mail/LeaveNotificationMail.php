<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Leave;
use App\Models\User;

class LeaveNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $leave;
    public $user;
    public $type;
    public $ccEmails;

    /**
     * Create a new message instance.
     *
     * @param Leave $leave
     * @param User $user
     * @param string $type
     * @param array $ccEmails
     */
    public function __construct(Leave $leave, User $user, $type, $ccEmails = [])
    {
        $this->leave = $leave;
        $this->user = $user;
        $this->type = $type;
        $this->ccEmails = $ccEmails;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = $this->type == 'request' 
            ? $this->user->name . ' requested a leave' 
            : 'Your leave request status has been updated';

        $email = $this->from($this->user->email, $this->user->name)
                    ->subject($subject)
                    ->view('emails.leave_notification')
                    ->with([
                        'leave' => $this->leave,
                        'user' => $this->user,
                        'type' => $this->type,
                    ]);

        if (!empty($this->ccEmails)) {
            $email->cc($this->ccEmails);
        }

        return $email;
    }
}