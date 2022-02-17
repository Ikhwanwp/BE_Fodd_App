<?php

namespace App\Http\Controllers\API;

use Midtrans\Snap;
use Midtrans\Config;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function all(Request $request)
    {
        // menentukan variable yang dibutuhkan
        $id = $request->input('id');
        $limit = $request->input('limit', 6);
        $food_id = $request->input('food_id');
        $status = $request->input('status');


        // pengambilan data sesuai ID
        if ($id) {
            $transaction = Transaction::with(['food', 'user'])->find($id);

            if ($transaction) {
                return ResponseFormatter::success(
                    $transaction,
                    'Data transaction berhasil diambil'
                );
            } else {
                return ResponseFormatter::error(
                    null,
                    'Data transaction tidak ada',
                    404
                );
            }
        }

        $transaction = Transaction::with(['food', 'user'])->where('user_id', Auth::user()->id);
        // Jika kondisi true maka akan menambahkan query ini
        if ($food_id) {
            $transaction->where('food_id', 'like', $food_id);
        }

        if ($status) {
            $transaction->where('status', 'like', $status);
        }



        // Pengembalian data
        return ResponseFormatter::success(
            $transaction->pageinate($limit),
            'Data list transaksi berhasil diambil'
        );
    }

    // Update Transaction boleh digunakan boleh tidak (Takutnya bisa membuat aplikasi menjadi tidak aman karena transaksinya di update)
    // untuk percobaan saja
    public function update(Request $request, $id)
    {
        $transaction =  Transaction::findOrFail($id);

        $transaction->update($request->all());

        return ResponseFormatter::success($transaction, 'Transaksi berhasil diperbarui');
    }

    // panggil midtrans untuk melakukan permbayaran
    public function checkout(Request $request)
    {
        $request->validate([
            'food_id' => 'required|exist:food,id',
            'user_id' => 'required|exist:user,id',
            'quantity' => 'required',
            'total' => 'required',
            'status' => 'required',
        ]);

        $transaction = Transaction::creat([
            'food_id' => $request->food_id,
            'user_id' => $request->user_id,
            'quantity' => $request->quantity,
            'total' => $request->total,
            'status' => $request->status,
            'payment_url' => '',
        ]);

        // Konfigurasi Midtrans, agar bisa dipanggil midtransnya
        Config::$serverKey = config('services.midtrans.serverKey');
        Config::$isProduction = config('services.midtrans.isProduction');
        Config::$isSanitized = config('services.midtrans.isSanitized');
        Config::$is3ds = config('services.midtrans.is3ds');

        // Panggil transaksi yang dibuat
        $transaction = Transaction::with(['food', 'user'])->find($transaction->id);

        // Membuat Transaksi Midtrans
        $midtrans = [
            'transaction_details' => [
                'order_id' => $transaction->id,
                'gross_amount' => (int) $transaction->total,
            ],
            'customer_details' => [
                'first_name' => $transaction->user->name,
                'email' => $transaction->user->email,
            ],
            'enable_payments' => ['gopay', 'bank_transfer'],
            'vtweb' => []
        ];

        // Memanggil Midtrans
        try {
            //Ambil halaman payment midtrans
            $paymentUrl = Snap::createTransaction($midtrans)->redirect_url;
            $transaction->payment_url = $paymentUrl;
            $transaction->save();

            // Mengembalikan data ke API
            return ResponseFormatter::success($transaction, 'Transaksi Berhasil');
        } catch (Exception $e) {
            return ResponseFormatter::error($e->getMessage(), 'Transaksi Gagal');
        }
    }
}
