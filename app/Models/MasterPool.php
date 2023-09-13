<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterPool extends Model
{
    use SoftDeletes;
    protected $title = '主池';
    public $timestamps = true;
    protected $table = 'master_pool';
    protected $guarded = ['id'];


}