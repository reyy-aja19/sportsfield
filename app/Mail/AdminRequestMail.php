<?php

namespace App\Mail;

use App\Models\AdminRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public $adminRequest;

    public function __construct(AdminRequest $adminRequest)
    {
        $this->adminRequest = $adminRequest;
    }

    public function build()
    {
        return $this->subject('Permintaan Menjadi Admin Venue')
            ->view('emails.admin-request');
    }
}