<?php

namespace App\Http\Controllers\Api;

use App\Models\MasterPool;
use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class BrandSideController extends Controller
{
    public function getBrandSideInfo(Request $request){
        $user = $request->user;
        $info = User::where(['id' => $user->id])->first();
        if (!$info) {
            return __return($this->errStatus, '品牌方不存在');
        }
        return __return($this->successStatus, '品牌方信息', $info);
    }
}
