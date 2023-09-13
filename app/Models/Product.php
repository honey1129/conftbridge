<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\User;
use Illuminate\Support\Facades\Redis;

/**
 * * @mixin \Eloquent
 *
 * @property int $pid
 * @property string $pname 产品名称
 * @property string $code 标记
 * @property string|null $image 图标
 * @property string|null $mark_cn 中文描述
 * @property float $spread 价格点差
 * @property float $var_price 最小变动价
 * @property float $sheets_rate 例：填入0.05 即一张代表0.05btc
 * @property string $leverage 杠杆倍数
 * @property string $position_select 下单仓位选择
 * @property int $max_order 最大下单量(张)
 * @property int $min_order 最小下单量(张)
 * @property int $max_chicang 最大持仓量(张)
 * @property int $delay_time 下单后几分钟可以平仓
 * @property int $state 状态 1 显示 0不显示
 * @property int|null $type 币种分类 1主流币 2是平台币
 * @property int $sort 位置排序
 * @property float $min_unit 最小波动价-精度
 * @property int $count 涨跌点数 正值为涨 负值为跌
 * @property int $enabled 风控是否可用 0为否1为是
 * @property string $fxtime
 * @property string|null $fxnum 发行数量
 * @property float|null $fxprice 发行单价
 * @property string|null $fxweb 发行网站
 * @property string|null $fxbook 白皮书地址
 * @property string|null $memo 币种简介
 * @property int $buy_state 买开关  1 开 0关
 * @property int $sell_state 卖开关  1 开 0关
 * @property float|null $cjnum_min 成交最小量
 * @property float|null $cjnum_max 成交最大量
 * @property float|null $mm_min 买卖最小量
 * @property float|null $mm_max 买卖最大量
 * @property int|null $buy_robot 买开关  1 开 0关
 * @property float|null $buy_robot_min 买入最小量
 * @property float|null $buy_robot_max 买入最大量
 * @property int|null $time_robot_min 时间最小量
 * @property int|null $time_robot_max 时间最大量
 * @property string|null $ltnum 流通数量
 * @property float|null $num_min 最小购买数量
 * @property float|null $actprice 发行价格
 * @property float|null $buyprice 买一价
 * @property float|null $sellprice 买一价
 * @property int $is_hot
 * @property int|null $launch_id
 * @property float $beishu 放大缩小倍数
 * @property float $dianwei 涨跌,0不涨不跌
 * @property string $basis 参考币种字符串
 * @property int $is_new 是否为新币
 * @property float $deal 成交倍数
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereActprice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereBasis($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereBeishu($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereBuyRobot($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereBuyRobotMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereBuyRobotMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereBuyState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereBuyprice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereCjnumMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereCjnumMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereDeal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereDelayTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereDianwei($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereFxbook($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereFxnum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereFxprice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereFxtime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereFxweb($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereIsHot($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereIsNew($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereLaunchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereLeverage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereLtnum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereMarkCn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereMaxChicang($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereMaxOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereMemo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereMinOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereMinUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereMmMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereMmMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereNumMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product wherePid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product wherePname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product wherePositionSelect($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereSellState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereSellprice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereSheetsRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereSpread($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereTimeRobotMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereTimeRobotMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereVarPrice($value)
 * @mixin \Eloquent
 */
class Product extends Model
{

    protected $title = '币种表';

    public    $timestamps = false;
    protected $table      = 'products';
    protected $guarded    = ['pid'];
    protected $primaryKey = 'pid';

    const HIDE_TYPE = 0; // state 不显示
    const DIS_TYPE  = 1; // state 显示


    static public function proInfo()
    {
        $data = [];
        $info = self::select('pid', 'pname')->get()->toArray();
        foreach ($info as $v) {
            $data[$v['pid']] = $v['pname'];
        }
        return $data;
    }

    public static function pidGetField($pid, $field)
    {
        $cache_key = 'ModelProductPid:' . $pid;
        $model     = Redis::get($cache_key);
        if (empty($model)) {
            $model = self::where('pid', $pid)->first();
            Redis::setex($cache_key, 60, json_encode($model));
        } else {
            $model = json_decode($model);
        }
        return $model->$field;
    }

}