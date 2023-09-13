<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\UserMoneyLog
 *
 * @property int $id
 * @property int $uid 用户id
 * @property int $order_id 关联的订单id
 * @property string|null $ptype 1资金账户 2币币账户 3合约账户 4期权账户
 * @property float $ymoney 原账户金额
 * @property float $money 增加或减少的金额
 * @property float $nmoney 最新金额
 * @property int $type 1为后台充值6手续费7提币8充币11资金账户到币币12资金账户到合约13资金账户到期权21币币到资金22币币到合约23币币到期权31合约到资金32合约到币币33合约到期权41期权到资金42期权到币币43期权到合约
 * @property string|null $mark 类型说明 如直推收入
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $wt 1 可用 2 冻结
 * @property int $pid
 * @property string|null $pname
 * @property string $en_mark
 * @property-read \App\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserMoneyLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserMoneyLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserMoneyLog query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserMoneyLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserMoneyLog whereEnMark($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserMoneyLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserMoneyLog whereMark($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserMoneyLog whereMoney($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserMoneyLog whereNmoney($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserMoneyLog whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserMoneyLog wherePid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserMoneyLog wherePname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserMoneyLog wherePtype($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserMoneyLog whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserMoneyLog whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserMoneyLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserMoneyLog whereWt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserMoneyLog whereYmoney($value)
 * @mixin \Eloquent
 */
class UserMoneyLog extends Model
{
    protected $enArr = [
        '后台充值'        => 'back recharge',
        '创建池子'        => 'create pool',
        '质押'          => 'pledge',
        '退出质押'        => 'exit pledge',
        '点亮池'         => 'open pool',
        '购买节点'        => 'become node',
        '购买节点增加算力'    => 'become node gift computer power',
        '购买节点赠送票'     => 'become node gift cft',
        'AI生成图片'      => 'AI make image',
        'AI聊天'        => 'AI chat',
        '链上充值'        => 'chain recharge',
        '用户提币'        => 'user withdraw',
        '提币手续费'       => 'withdraw fee',
        '转入'          => 'transfer in',
        '转出'          => 'transfer out',
        '划转'          => 'transfer',
        '点亮5号池池主收益'   => 'open fifth pool pooler balance',
        '点亮5号池4号池员收益' => 'open fifth pool user balance',
        '点亮6号池池主收益'   => 'open sixth pool pooler balance',
        '点亮6号池5号池员收益' => 'open sixth pool user balance',
        '点亮CFA池池主收益'  => 'open cfa pool pooler balance',
        '点亮CFA池池员收益'  => 'open cfa pool user balance',
        'V4分红'        => 'V4 balance',
        'V5分红'        => 'V5 balance',
        'V6分红'        => 'V6 balance',
        '高级节点分红'      => 'high node balance',
        '燃料消耗'        => 'fuel consumption',
        '燃料消耗收益'      => 'fuel consumption balance',
        '池主推荐'        => 'pooler recommed',
        '释放CFA'       => 'release cfa',
        '奖励'          => 'balance',
        '平级奖励'        => 'equal level',
        '下'           => 'under',
        '层'           => 'storey',
        '上'           => 'up',
        '代'           => 'times',
        '算力前21'       => 'Before computing power 21 release cfa'
    ];

    protected $twArr = [
        '后台充值'        => '後台充值',
        '创建池子'        => '創建池子',
        '质押'          => '質押',
        '退出质押'        => '退出質押',
        '点亮池'         => '點亮池',
        '购买节点'        => '購買節點',
        '购买节点增加算力'    => '購買節點增加算力',
        '购买节点赠送票'     => '購買節點贈送票',
        'AI生成图片'      => 'AI生成圖片',
        'AI聊天'        => 'AI聊天',
        '链上充值'        => '鏈上聊天',
        '用户提币'        => '用戶提幣',
        '提币手续费'       => '提幣手續費',
        '转入'          => '轉入',
        '转出'          => '轉出',
        '划转'          => '劃轉',
        '点亮5号池池主收益'   => '點亮5號池池主收益',
        '点亮5号池4号池员收益' => '點亮5號池4號池員收益',
        '点亮6号池池主收益'   => '點亮6號池池主收益',
        '点亮6号池5号池员收益' => '點亮6號池5號池員收益',
        '点亮CFA池池主收益'  => '點亮CFA池池主收益',
        '点亮CFA池池员收益'  => '點亮CFA池池員收益',
        'V4分红'        => 'V4分紅',
        'V5分红'        => 'V5分紅',
        'V6分红'        => 'V6分紅',
        '高级节点分红'      => '高級節點分紅',
        '燃料消耗'        => '燃料消耗',
        '燃料消耗收益'      => '燃料消耗收益',
        '池主推荐'        => '池主推薦',
        '释放CFA'       => '釋放CFA',
        '奖励'          => '獎勵',
        '平级奖励'        => '平級獎勵',
        '下'           => '下',
        '层'           => '層',
        '上'           => '上',
        '代'           => '代',
        '算力前21'       => '算力前21'
    ];

    protected $guarded = ['id'];
    protected $table = 'user_money_log';

    public function user()
    {
        return $this->belongsTo(User::class, 'uid', 'id');
    }
    //账户类型 lang cn中文 en英文
    public static function logTypeLang($lang = 'cn', $key = '')
    {
        //中文简体
        $cnArr = [
            1  => '后台充值',
            2  => '链上充值',
            3  => '转账',
            4  => '创建池子',
            5  => '点亮池子',
            6  => '手续费',
            7  => '提币',
            8  => '充币',
            9  => '提币金额退回',
            10 => '质押',
            11 => '退出质押',
            12 => '购买节点',
            13 => '释放cfa',
            14 => '算力收益',
            15 => 'VIP收益',
            16 => '直推收益',
            17 => '划转',
            18 => '池主池员收益',
            19 => '燃料消耗',
            20 => '分红',
            21 => '释放CFA(算力前21)',
            22 => '池主推荐池主',
            23 => 'AI生成图片',
            24 => '高级节点分红',
            25 => '燃料消耗收益',
            26 => '手续费分配',
            27 => '合成CFA池',
            28 => '加入CFA池',
            29 => '退出CFA池',
            30 => '高级节点收益',
            31 => '中低节点收益',
            32 => 'OTC售出',
            33 => 'OTC购买',
            34 => '预约拍卖',
            35 => '参与拍卖',
            36 => '拍卖退还'
        ];

        //中文繁体
        $twArr = [
            1  => '後臺充值',
            2  => '鏈上充值',
            3  => '轉帳',
            4  => '創建池子',
            5  => '點亮池子',
            6  => '手續費',
            7  => '提幣',
            8  => '充幣',
            9  => '提幣金額退回',
            10 => '質押',
            11 => '退出質押',
            12 => '購買節點',
            13 => '釋放cfa',
            14 => '算力收益',
            15 => 'VIP收益',
            16 => '直推收益',
            17 => '劃轉',
            18 => '池主池員收益',
            19 => '燃料消耗',
            20 => '分紅',
            21 => '釋放CFA(算力前21)',
            22 => '池主推薦池主',
            23 => 'AI生成圖片',
            24 => '高級節點分紅',
            25 => '燃料消耗收益',
            26 => '手續費分配',
            27 => '合成CFA池',
            28 => '加入CFA池',
            29 => '退出CFA池',
            30 => '高级節點收益',
            31 => '中低節點收益'
        ];

        //英文
        $enArr = [
            1  => 'Background recharge',
            2  => 'chain recharge',
            3  => 'transfer',
            4  => 'create pool',
            5  => 'open child pool',
            6  => 'handling fees',
            7  => 'withdrawal of currency',
            8  => 'recharge',
            9  => 'withdrawal amount returned',
            10 => 'pledge',
            11 => 'exit pledge',
            12 => 'purchase node',
            13 => 'release CFA',
            14 => 'compute balance',
            15 => 'vip balance',
            16 => 'recommend balance',
            17 => 'transfer',
            18 => 'pool master pool member income',
            19 => 'fuel consumption',
            20 => 'bonus',
            21 => 'release CFA(Before computing power 21)',
            22 => 'pooler recommend pooler',
            23 => 'AI generate image',
            24 => 'high node bonus',
            25 => 'fuel consumption balance',
            26 => 'withdraw fee balance',
            27 => 'generate CFA pool',
            28 => 'join CFA pool',
            29 => 'exit CFA pool',
            30 => 'high node balance',
            31 => 'middle/small node balance'
        ];
        if ($key == '') {
            if ($lang == 'cn') {
                return $cnArr;
            } else {
                return $enArr;
            }
        }
        if ($lang == 'zh-CN') {
            $msg = isset($cnArr[$key]) ? $cnArr[$key] : $key;
        } else if ($lang == 'en') {
            $msg = isset($enArr[$key]) ? $enArr[$key] : $key;
        } else if ($lang == 'zh-TW') {
            $msg = isset($twArr[$key]) ? $twArr[$key] : $key;
        }
        return $msg;
    }
}