<?php

namespace App\Mail;

use App\Models\TechAssist;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TechRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public $mailData;
    public $user;
    public $type;

    /**
     * Create a new message instance.
     */
    public function __construct(TechAssist $mailData, User $user,  $type)
    {
        $this->mailData = $mailData;
        $this->user = $user;
        $this->type = $type;
    }

   /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = $this->type == 'request' 
            ? $this->user->first_name . ' has requested for a technical assitance' 
            : 'Your techinical assistance request status has been updated';

        return $this->from($this->user->email, $this->user->first_name)
            ->subject($subject)
            ->view('emails.techassist_notification')
            ->with([
                'request' => $this->mailData,
                'user' => $this->user,
                'type' => $this->type,
            ]);

    }
}
