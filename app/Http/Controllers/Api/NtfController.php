<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NftInvite;
use App\User;
use Illuminate\Http\Request;
use function __return;
class NtfController extends Controller
{
    /**
     * @param Request $request
     * @return void
     */
    public function getNtfInviteList(Request $request){
        $brand_side_id = $request->brand_side_id;
        $data = NftInvite::query()->where([
            'brand_side_id'=>$brand_side_id
        ])->select(['id','country','commercial_labels','due_time','leave_a_message'])->get();
        return __return($this->successStatus, '成功',$data);
    }

}
