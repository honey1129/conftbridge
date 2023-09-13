<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class GptInfo extends Model
{
    protected $table = 'gpt_info';

    public $guarded = ['id'];
}