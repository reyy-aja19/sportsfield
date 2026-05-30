<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
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
        $user = Auth::user();

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

    // 1. Mengambil list reward aktif untuk aplikasi mobile
    public function apiIndex()
    {
        try {
            // Mengambil reward yang statusnya aktif dan stoknya masih ada
            $rewards = Reward::where('status', 'Aktif')->where('stock', '>', 0)->get();

            // Memetakan URL Gambar agar bisa dibaca dari Flutter
            $rewards->transform(function($item) {
                if ($item->image) {
                    $item->image_url = asset('storage/' . $item->image); 
                } else {
                    $item->image_url = null;
                }
                return $item;
            });

            return response()->json([
                'status' => true,
                'data' => $rewards
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memuat data: ' . $e->getMessage()
            ], 500);
        }
    }

    // 2. Memproses penukaran poin lewat aplikasi mobile
    public function apiRedeem(Request $request)
    {
        $request->validate([
            'reward_id' => 'required|exists:rewards,id'
        ]);

        $user = Auth::user();
        $reward = Reward::find($request->reward_id);

        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Sesi habis, silakan login ulang'], 401);
        }

        if ($reward->stock <= 0) {
            return response()->json(['status' => false, 'message' => 'Stok reward sudah habis'], 400);
        }

        if ($user->points < $reward->points_required) {
            return response()->json(['status' => false, 'message' => 'Poin kamu tidak cukup'], 400);
        }

        $code = 'RDM-' . strtoupper(Str::random(8));
        $fileName = time() . '.png';
        $qrPath = 'uploads/qrcodes/' . $fileName;

        try {
            DB::transaction(function() use ($user, $reward, $code, $qrPath) {
                // Potong poin user
                $user->decrement('points', $reward->points_required);
                // Kurangi stok reward
                $reward->decrement('stock');

                // Generate Folder QR Code jika belum ada
                $folder = public_path('uploads/qrcodes');
                if(!File::exists($folder)){
                    File::makeDirectory($folder, 0755, true);
                }

                // Buat file QR Code fisik
                QrCode::format('png')->size(250)->generate($code, public_path($qrPath));

                // Simpan ke data riwayat penukaran
                Redemption::create([
                    'user_id' => $user->id,
                    'reward_id' => $reward->id,
                    'redeemed_at' => now(),
                    'redeem_code' => $code,
                    'qr_code' => $qrPath,
                    'status' => 'Pending'
                ]);
            });

            return response()->json([
                'status' => true,
                'message' => 'Reward berhasil ditukarkan!',
                'current_points' => $user->points, // Mengembalikan sisa poin terbaru ke Flutter
                'kode_redeem' => $code,
                'qr_url' => asset($qrPath) // URL gambar QR agar muncul di pop-up Flutter
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan transaksi: ' . $e->getMessage()
            ], 500);
        }
    }
}