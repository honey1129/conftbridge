<?php

namespace App\Listeners;

use DB;
use Log;
use Carbon\Carbon;
use Laravel\Passport\Events\AccessTokenCreated;

class RevokeOldTokens
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  Event  $event
     * @return void
     */
    public function handle(AccessTokenCreated $event)
    {
        DB::table('oauth_access_tokens')
        ->where('id','<>',$event->tokenId)
        ->where('user_id',$event->userId)
        ->where('client_id',$event->clientId)
        ->where('revoked',0)
        ->delete();
    }
}
