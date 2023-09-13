<?php
return [
    'VerifyCodeSign'       => 'CFA',
    'RSA'                  => [
        'RSA_PRIVATE_KEY'  => '-----BEGIN RSA PRIVATE KEY-----
MIICXQIBAAKBgQDJS6QzyW+HUj6LdVvpjlibeePsONkJnJsQLRZRRMd8qFsJevtx
sMZ4SiIzUf+j5lMj7+CGGCTv/mPfWtz3dsSHazersTwxvaORH2NYsLuEzy4FiuOU
CVbVYWSQ4YmXRpHLGg7dnBb/77dyqjerCGMI1+/oJrkF0nQ0CdAHkCOeIQIDAQAB
AoGBAJLhk/6J1MzMUYEXcKrayIMijRKYZJ5j3K4QCEHiyyGGA2/GgmVyFVA+3/fM
tJoW/cGyToXqZkGMwbmAJs6XpkzSvvYZWd8smZf+12aegWSiA/4ROUnBrCpdCv8i
n0ggeKnczHGuQlhdoUTPxGszzhIDWjMJBe595Q03Mkz5gb+FAkEA5HyddlLOxTrY
aI1JLfiKL6w3TZFY1FYb6g3/Rtn4kyE37WIzPMCIfvlvmLO2fW+YrmGOVhqBIBug
8tI8mGMZ2wJBAOGI0jJTPAnk6NPKP64lCjnKg37i8RMmJNHvd/rkqsYhFP6tZBiO
a9WnYZjD5+hliK77AS8+h+5VBlWRkzV4vrMCQFesBVOQpKyDTuSXSfwswQFX6ISH
//jB7cYahtht7PavqWEZ7CUkj3uBRLPoSV7KQmCvKEmbs+5ZC6IAz6V9aT8CQGM2
qzEUSI8ZsgqpKAZVqP/vRJVnBCImX4Ay1hb6zN1H5FJ8uFHNJUbh0R9A3x3uvIgt
R0IfQfDoWlb+KUIWkd0CQQDPRwtvi4AQjXsTxpplMXrJsq5ECLjOmVbZ2lTKUcnT
A/JwjP1iBX0saINopK55mP1S6emqDi59AVjc6BjvoOP9
-----END RSA PRIVATE KEY-----',
        'RSA_PRIVATE_KEY1' => '-----BEGIN PRIVATE KEY-----
MIICdwIBADANBgkqhkiG9w0BAQEFAASCAmEwggJdAgEAAoGBAMlLpDPJb4dSPot1
W+mOWJt54+w42QmcmxAtFlFEx3yoWwl6+3GwxnhKIjNR/6PmUyPv4IYYJO/+Y99a
3Pd2xIdrN6uxPDG9o5EfY1iwu4TPLgWK45QJVtVhZJDhiZdGkcsaDt2cFv/vt3Kq
N6sIYwjX7+gmuQXSdDQJ0AeQI54hAgMBAAECgYEAkuGT/onUzMxRgRdwqtrIgyKN
EphknmPcrhAIQeLLIYYDb8aCZXIVUD7f98y0mhb9wbJOhepmQYzBuYAmzpemTNK+
9hlZ3yyZl/7XZp6BZKID/hE5ScGsKl0K/yKfSCB4qdzMca5CWF2hRM/EazPOEgNa
MwkF7n3lDTcyTPmBv4UCQQDkfJ12Us7FOthojUkt+IovrDdNkVjUVhvqDf9G2fiT
ITftYjM8wIh++W+Ys7Z9b5iuYY5WGoEgG6Dy0jyYYxnbAkEA4YjSMlM8CeTo08o/
riUKOcqDfuLxEyYk0e93+uSqxiEU/q1kGI5r1adhmMPn6GWIrvsBLz6H7lUGVZGT
NXi+swJAV6wFU5CkrINO5JdJ/CzBAVfohIf/+MHtxhqG2G3s9q+pYRnsJSSPe4FE
s+hJXspCYK8oSZuz7lkLogDPpX1pPwJAYzarMRRIjxmyCqkoBlWo/+9ElWcEIiZf
gDLWFvrM3UfkUny4Uc0lRuHRH0DfHe68iC1HQh9B8OhaVv4pQhaR3QJBAM9HC2+L
gBCNexPGmmUxesmyrkQIuM6ZVtnaVMpRydMD8nCM/WIFfSxog2ikrnmY/VLp6aoO
Ln0BWNzoGO+g4/0=
-----END PRIVATE KEY-----',
        'RSA_PUBLIC_KEY'   => '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDJS6QzyW+HUj6LdVvpjlibeePs
ONkJnJsQLRZRRMd8qFsJevtxsMZ4SiIzUf+j5lMj7+CGGCTv/mPfWtz3dsSHazer
sTwxvaORH2NYsLuEzy4FiuOUCVbVYWSQ4YmXRpHLGg7dnBb/77dyqjerCGMI1+/o
JrkF0nQ0CdAHkCOeIQIDAQAB
-----END PUBLIC KEY-----',
        'MODULUS'          => 'B3E14483615553CB530CFE0BD748BB2EF3D1D3088526DA95F5F55543A84C988BEC9467E29B484D5732D5C0F25BB54CA51A6F55807BFF66D50F56C6138BFEF07ED35A243E085E03D029AD8BDB0FF4E60A138CB9747C3FC1874A584388B7EA0806BEDCF7BAD4BBC0148C3D0F9AC521805DD5D2404F17F308D59B32836DE32A3FEF',
    ],
    //协议类型
    'agree_type'           => [
        1  => '关于我们',
        2  => '免责声明',
        3  => '法律声明',
        4  => '隐私条款',
        5  => '服务协议',
        6  => '注册协议',
        7  => '关于反洗钱',
        8  => '推广邀请规则',
        //        11 => '合约费率',
//        12 => '合约指南',
        13 => '操作帮助',
    ],
    //语言类型
    'locale'               => [
        'zh-CN' => '中文',
        'en'    => '英文',
        'ml'    => '马来西亚文',
    ],
    //终端类型
    'pc_app'               => [
        1 => 'APP',
        2 => 'PC',
    ],
    //代理商等级
    'account_type'         => [
        1 => '经理',
        2 => '代理商',
        3 => '会员单位',
        4 => '运营中心',
    ],
    //格式化保留小数位
    'decimal_places'       => [
        'btc_usdt' => 2,
        'eth_usdt' => 2,
        'xrp_usdt' => 5,
        'ltc_usdt' => 2,
        'bch_usdt' => 2,
        'eos_usdt' => 4,
        'etc_usdt' => 4,
        'trx_usdt' => 6,
        'usdt'     => 8
    ],
    'currpay'              => [
        'appKey'     => '127',
        'appSecret'  => 'fbf839c21d1bd632c5da0894e3f8476f',
        'appBaseUrl' => 'https://open.otc365.com/v1',
        'key'        => '/third/merchant_buy_link',
        'pickupUrl'  => config('app.url') . '/notify/currpayReturnUrl',
        //同步
        'receiveUrl' => config('app.url') . '/notify/currpayNotify',
        //异步
    ],
    'user_money_log_type'  => [
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
        29 => '退出CFA池'
    ],
    'agent_money_log_type' => [
        1 => '代理商手续费返佣',
        2 => '会员单位手续费返佣',
        3 => '运营中心手续费返佣',
        4 => '代理商盈亏返佣',
        5 => '会员单位盈亏返佣',
        6 => '运营中心盈亏返佣',
        7 => '代理商充值',
        8 => '代理商提币',
    ],
    'system_account'       => [
        'phone' => '13525921111',
        'email' => 'jiangxiaokun1666@163.com'
    ]
];