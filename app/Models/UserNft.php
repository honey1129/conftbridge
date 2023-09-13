<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;


class UserNft extends Model
{

    protected $title = '用户NFT';

    protected $table = 'user_nft';

    protected $guarded = ['id'];
}