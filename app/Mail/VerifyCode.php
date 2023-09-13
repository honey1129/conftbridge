<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class VerifyCode extends Mailable
{
    use Queueable, SerializesModels;

    protected $sign;
    protected $code;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($sign, $code)
    {
        $this->sign = $sign;
        $this->code = $code;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $times = rand(1111, 9999) . '' . time();
        return $this->view('emails.verify', [
            'code' => $this->code,
            'sign' => $this->sign,
            'anti' => $times
        ]);
    }
}
