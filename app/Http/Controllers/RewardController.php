<?php

namespace App\Http\Controllers;

use App\Models\Reward;
use App\Models\Redemption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class RewardController extends Controller
{
    public function redeem(
        Request $request,
        Reward $reward
    )
    {
        $user = auth()->user();

        if (!$user) {
            return back()->with(
                'error',
                'Silakan login'
            );
        }

        if ($user->points < $reward->points_required) {
            return back()->with(
                'error',
                'Poin tidak cukup'
            );
        }

        DB::transaction(function()
        use($user,$reward){

            $user->decrement(
                'points',
                $reward->points_required
            );

            $reward->decrement(
                'stock'
            );

            $code='RDM-'.strtoupper(
                Str::random(8)
            );

            $folder=public_path(
                'uploads/qrcodes'
            );

            if(!File::exists($folder)){
                File::makeDirectory(
                    $folder,
                    0755,
                    true
                );
            }

            $fileName=
                time().
                '.png';

            $qrPath=
                'uploads/qrcodes/'.
                $fileName;

            QrCode::format('png')
                ->size(250)
                ->generate(
                    $code,
                    public_path($qrPath)
                );

            Redemption::create([

                'user_id'=>$user->id,

                'reward_id'=>$reward->id,

                'redeemed_at'=>now(),

                'redeem_code'=>$code,

                'qr_code'=>$qrPath,

                'status'=>'Pending'
            ]);

        });

        return back()->with(
            'success',
            'Reward berhasil ditukar'
        );
    }
}