<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CfaPool extends Model
{
    use SoftDeletes;
    protected $title = 'CFA合成池';
    public $timestamps = true;
    protected $table = 'cfa_pool';
    protected $guarded = ['id'];
}