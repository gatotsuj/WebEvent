<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Notification;
use Midtrans\Snap;
use Midtrans\Transaction;

class MidtransService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
        Config::$serverKey = config('midtrans.server_key');
        Config::$clientKey = config('midtrans.client_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
    }

    public function createTransaction(Order $order): array
    {
        try {
            $params = $this->buildTransactionParams($order);
            $snapToken = Snap::getSnapToken($params);

            // Update order dengan snap token
            $order->update([
                'payment_details' => [
                    'snap_token' => $snapToken,
                    'created_at' => now(),
                ],
            ]);

            return [
                'success' => true,
                'snap_token' => $snapToken,
                'redirect_url' => "https://app.sandbox.midtrans.com/snap/v2/vtweb/{$snapToken}",
            ];
        } catch (\Exception $e) {
            Log::error('Midtrans transaction creation failed: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to create payment transaction: '.$e->getMessage(),
            ];
        }
    }

    private function buildTransactionParams(Order $order): array
    {
        $customer = $order->customer;
        $product = $order->product;

        return [
            'transaction_details' => [
                'order_id' => $order->order_number,
                'gross_amount' => (int) $order->final_amount,
            ],
            'item_details' => [
                [
                    'id' => $product->id,
                    'price' => (int) $order->unit_price,
                    'quantity' => $order->quantity,
                    'name' => $product->name,
                    'brand' => 'Event Ticket',
                    'category' => $product->category->name ?? 'Event',
                    'merchant_name' => config('app.name'),
                ],
            ],
            'customer_details' => [
                'first_name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone ?? '',
                'billing_address' => [
                    'first_name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone ?? '',
                    'address' => $customer->address ?? '',
                    'city' => $customer->city ?? '',
                    'postal_code' => $customer->postal_code ?? '',
                    'country_code' => 'IDN',
                ],
            ],
            'enabled_payments' => [
                'credit_card', 'mandiri_clickpay', 'cimb_clicks',
                'bca_klikbca', 'bca_klikpay', 'bri_epay', 'echannel',
                'permata_va', 'bca_va', 'bni_va', 'other_va',
                'gopay', 'shopeepay', 'dana', 'linkaja',
                'indomaret', 'alfamart',
            ],
            'credit_card' => [
                'secure' => true,
                'bank' => 'bca',
                'installment' => [
                    'required' => false,
                    'terms' => [
                        'bni' => [3, 6, 12],
                        'mandiri' => [3, 6, 12],
                        'cimb' => [3, 6, 12],
                        'bca' => [3, 6, 12],
                        'maybank' => [3, 6, 12],
                    ],
                ],
            ],
            'callbacks' => [
                'finish' => config('midtrans.finish_url'),
                'unfinish' => config('midtrans.unfinish_url'),
                'error' => config('midtrans.error_url'),
            ],
            'expiry' => [
                'start_time' => date('Y-m-d H:i:s O'),
                'unit' => 'hours',
                'duration' => 24,
            ],
            'custom_field1' => $order->id,
            'custom_field2' => $customer->id,
            'custom_field3' => $product->id,
        ];
    }

    public function handleNotification(): array
    {
        try {
            $notification = new Notification;

            $order = Order::where('order_number', $notification->order_id)->first();

            if (! $order) {
                Log::error('Order not found for notification: '.$notification->order_id);

                return ['success' => false, 'message' => 'Order not found'];
            }

            $transactionStatus = $notification->transaction_status;
            $fraudStatus = $notification->fraud_status ?? null;
            $paymentType = $notification->payment_type;

            Log::info('Midtrans notification received', [
                'order_id' => $notification->order_id,
                'transaction_status' => $transactionStatus,
                'fraud_status' => $fraudStatus,
                'payment_type' => $paymentType,
            ]);

            $this->updateOrderStatus($order, $transactionStatus, $fraudStatus, $notification);

            return ['success' => true, 'message' => 'Notification processed successfully'];
        } catch (\Exception $e) {
            Log::error('Midtrans notification handling failed: '.$e->getMessage());

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function updateOrderStatus(Order $order, string $transactionStatus, ?string $fraudStatus, $notification): void
    {
        $paymentDetails = $order->payment_details ?? [];
        $paymentDetails['notification_data'] = [
            'transaction_status' => $transactionStatus,
            'fraud_status' => $fraudStatus,
            'payment_type' => $notification->payment_type,
            'transaction_id' => $notification->transaction_id,
            'transaction_time' => $notification->transaction_time,
            'settlement_time' => $notification->settlement_time ?? null,
            'received_at' => now(),
        ];

        switch ($transactionStatus) {
            case 'capture':
                if ($fraudStatus == 'challenge') {
                    $order->update([
                        'status' => 'pending',
                        'payment_status' => 'partial',
                        'payment_details' => $paymentDetails,
                        'notes' => 'Payment challenged by fraud detection',
                    ]);
                } elseif ($fraudStatus == 'accept') {
                    $order->update([
                        'status' => 'paid',
                        'payment_status' => 'paid',
                        'payment_date' => now(),
                        'payment_method' => $notification->payment_type,
                        'payment_reference' => $notification->transaction_id,
                        'payment_details' => $paymentDetails,
                    ]);
                }
                break;

            case 'settlement':
                $order->update([
                    'status' => 'paid',
                    'payment_status' => 'paid',
                    'payment_date' => now(),
                    'payment_method' => $notification->payment_type,
                    'payment_reference' => $notification->transaction_id,
                    'payment_details' => $paymentDetails,
                ]);
                break;

            case 'pending':
                $order->update([
                    'status' => 'pending',
                    'payment_status' => 'unpaid',
                    'payment_method' => $notification->payment_type,
                    'payment_reference' => $notification->transaction_id,
                    'payment_details' => $paymentDetails,
                ]);
                break;

            case 'deny':
            case 'cancel':
            case 'expire':
                $order->update([
                    'status' => 'cancelled',
                    'payment_status' => 'unpaid',
                    'payment_details' => $paymentDetails,
                    'notes' => "Payment {$transactionStatus}",
                ]);
                break;

            case 'refund':
            case 'partial_refund':
                $order->update([
                    'status' => 'refunded',
                    'payment_status' => 'refunded',
                    'payment_details' => $paymentDetails,
                ]);
                break;
        }
    }

    public function getTransactionStatus(string $orderId): array
    {
        try {
            $status = Transaction::status($orderId);

            return [
                'success' => true,
                'data' => $status,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get transaction status: '.$e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function cancelTransaction(string $orderId): array
    {
        try {
            $result = Transaction::cancel($orderId);

            return [
                'success' => true,
                'data' => $result,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to cancel transaction: '.$e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function refundTransaction(string $orderId, ?int $amount = null, string $reason = ''): array
    {
        try {
            $params = [
                'refund_key' => uniqid(),
                'reason' => $reason ?: 'Customer refund request',
            ];

            if ($amount) {
                $params['amount'] = $amount;
            }

            $result = Transaction::refund($orderId, $params);

            return [
                'success' => true,
                'data' => $result,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to refund transaction: '.$e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
