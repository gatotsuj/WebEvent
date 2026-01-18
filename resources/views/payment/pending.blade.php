<x-app-layout>
    <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                <div class="text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100">
                        <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>

                    <h2 class="mt-6 text-3xl font-extrabold text-gray-900">Payment Pending</h2>

                    <p class="mt-2 text-sm text-gray-600">
                        Your payment is being processed. Please wait for confirmation.
                    </p>

                    <div class="mt-6 bg-gray-50 px-4 py-5 sm:p-6 rounded-md">
                        <dl class="grid grid-cols-1 gap-x-4 gap-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Order Number</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $order->order_number }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Total Amount</dt>
                                <dd class="mt-1 text-sm text-gray-900">Rp
                                    {{ number_format($order->final_amount, 0, ',', '.') }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="mt-6 space-y-3">
                        <button onclick="checkPaymentStatus()"
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                            Check Payment Status
                        </button>

                        <a href="{{ route('home') }}"
                            class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            Back to Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function checkPaymentStatus() {
            fetch('{{ route('payment.check-status', $order) }}')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.status === 'settlement' || data.status === 'capture') {
                            window.location.reload();
                        } else {
                            alert('Payment status: ' + data.status);
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        // Auto check status every 30 seconds
        setInterval(checkPaymentStatus, 30000);
    </script>
</x-app-layout>
