<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Penjualan;

class PaymentCallbackController extends Controller
{
    public function receive(Request $request)
    {
        // 1. Ambil Server Key dari .env untuk verifikasi keamanan
        $serverKey = config('services.midtrans.serverKey');
        
        // 2. Buat ulang kunci rahasia berdasarkan data yang dikirim
        $hashed = hash("sha512", $request->order_id . $request->status_code . $request->gross_amount . $serverKey);

        // 3. Cocokkan kunci. Jika sama, berarti ini valid dari Midtrans!
        if ($hashed == $request->signature_key) {
            
            // Cari data transaksi berdasarkan Nomor Faktur (Order ID)
            $penjualan = Penjualan::where('no_faktur', $request->order_id)->first();

            if ($penjualan) {
                // Jika pembayaran berhasil (settlement = uang masuk, capture = kartu kredit sukses)
                if ($request->transaction_status == 'capture' || $request->transaction_status == 'settlement') {
                    $penjualan->update(['status_pembayaran' => 'success']);
                } 
                // Jika pembayaran kedaluwarsa, dibatalkan, atau ditolak
                elseif (in_array($request->transaction_status, ['deny', 'expire', 'cancel'])) {
                    $penjualan->update(['status_pembayaran' => 'failed']);
                }
            }
        }

        // 4. Balas Midtrans dengan status 200 OK agar mereka berhenti mengirim notifikasi
        return response()->json(['message' => 'Callback received successfully']);
    }
}