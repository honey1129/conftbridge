<?php

namespace App\Jobs;

use App\Models\EmailLog;
use Exception;
use App\Mail\VerifyCode;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;
use Log;

class EmailVerifyCode implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $email;
    protected $ip;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($email,$ip)
    {
        $this->email = $email;
        $this->ip = $ip;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        try{
            $sign = config('system.VerifyCodeSign');
            $email = $this->email;
            $ip = $this->ip;
            $code = mt_rand(100000, 999999);
            Mail::to($email)->send(new VerifyCode($sign,$code));
            $email_log = new EmailLog;
            $email_log->email = $email;
            $email_log->code = $code;
            $email_log->ip = $ip;
            $email_log->save();
            return Log::info('成功');
        } catch (Exception $exception){
            Log::info('EmailVerifyCode ERROR'.$exception->getMessage());
        }

    }

    public function failed(Exception $exception)
    {
        Log::info('EmailVerifyCode Failed'.$exception->getMessage());
    }
}
