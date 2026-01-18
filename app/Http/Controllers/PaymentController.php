<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    //
    protected $midtransService;

    public function __construct(MidtransService $midtransService)
    {
        $this->midtransService = $midtransService;
    }

    public function createPayment(Request $request, Order $order)
    {
        try {

            if ($order->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Order cannot be paid. Current status: '.$order->status,
                ], 400);
            }

            $result = $this->midtransService->createTransaction($order);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'snap_token' => $result['snap_token'],
                    'redirect_url' => $result['redirect_url'],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 500);

        } catch (\Exception $e) {
            Log::error('Payment creation failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment',
            ], 500);
        }
    }

    public function notification(Request $request)
    {
        try {
            Log::info('Midtrans notification received', $request->all());

            $result = $this->midtransService->handleNotification();

            if ($result['success']) {
                return response()->json(['success' => true]);
            }

            return response()->json(['success' => false], 400);
        } catch (\Exception $e) {
            Log::error('Notification handling failed: '.$e->getMessage());

            return response()->json(['success' => false], 500);
        }
    }

    public function finish(Request $request)
    {
        $orderId = $request->get('order_id');
        $order = Order::where('order_number', $orderId)->first();

        if (! $order) {
            return redirect()->route('home')->with('error', 'Order not found');
        }

        // Get latest transaction status
        $statusResult = $this->midtransService->getTransactionStatus($orderId);

        if ($statusResult['success']) {
            $transactionStatus = $statusResult['data']->transaction_status;

            switch ($transactionStatus) {
                case 'settlement':
                case 'capture':
                    return view('payment.success', compact('order'));
                case 'pending':
                    return view('payment.pending', compact('order'));
                default:
                    return view('payment.failed', compact('order'));
            }
        }

        return view('payment.success', compact('order'));
    }

    public function unfinish(Request $request)
    {
        $orderId = $request->get('order_id');
        $order = Order::where('order_number', $orderId)->first();

        return view('payment.pending', compact('order'));
    }

    public function error(Request $request)
    {
        $orderId = $request->get('order_id');
        $order = Order::where('order_number', $orderId)->first();

        return view('payment.failed', compact('order'));
    }

    public function checkStatus(Order $order)
    {
        $result = $this->midtransService->getTransactionStatus($order->order_number);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'status' => $result['data']->transaction_status,
                'data' => $result['data'],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
        ], 500);
    }
}
