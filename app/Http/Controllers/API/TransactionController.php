<?php

namespace App\Http\Controllers\API;

use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\TransactionItem;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function all(Request $request)
    {
        $id = $request->input('id');
        $limit = $request->input('limit', 6);
        $status = $request->input('status'); 

        if ($id) {
            $transaction = Transaction::with('items.product')->find($id);

            if ($transaction) {
                return ResponseFormatter::success(
                    $transaction,
                    'Data Transaksi Berhasil Diambil'
                );
        }else{
            return ResponseFormatter::error(
                null,
                'Data Transaksi Tidak Ada ',
                404 
             );
           }
        }

        $transaction = Transaction::with(['items.product'])->where('users_id', Auth::user()->id);

        if ($status) {
            $transaction->where('status', $status);
        }
        
        return ResponseFormatter::success(
            $transaction->paginate($limit),
            'Data Transaksi Berhasil Diambil'
        );
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'items' => 'required'|'array',
            'items.*.id' => 'exist:product,id',
            'total_price' => 'required',
            'shipping_price' => 'required',
            'status' => 'required|PENDING,SUCCESS,CANCELED,FAILED,SHIPPING,SHIPPED',
        ]);

        $transaction = Transaction::create([
            'users_id' => Auth::user()->id,
            'address' => $request->input('address'),
            'total_price' => $request->input('total_price'),
            'shipping_price' => $request->input('shipping_price'),
            'status' => $request->input('status'),
        ]);

        foreach ($request-> items as $product) {
            TransactionItem::create([
                'users_id' => Auth::user()->id,
                'product_id' => $product['id'],
                'transaction_id' => $transaction->id,
                'quantity' => $product['quantity']
            ]);
        }
        return ResponseFormatter::success($transaction->load('items.product') ,'Transaksi berhasil');
    }
}
