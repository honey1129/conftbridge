<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChildPool extends Model
{
    use SoftDeletes;
    protected $title = '子池';
    public $timestamps = true;
    protected $table = 'child_pool';
    protected $guarded = ['id'];


    public function getBalanceRateAttribute($balanceRate)
    {
        $level = $this->level;
        switch ($level) {
            case 1:
                $rate = config('pool.one_balance');
                break;
            case 2:
                $rate = config('pool.two_balance');
                break;
            case 3:
                $rate = config('pool.three_balance');
                break;
            case 4:
                $rate = config('pool.four_balance');
                break;
            case 5:
                $rate = config('pool.five_balance');
                break;
            case 6:
                $rate = config('pool.six_balance');
                break;
        }
        return $rate;
    }
}