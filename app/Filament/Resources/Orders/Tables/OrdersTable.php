<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\Order;
use App\Services\MidtransService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Midtrans\Notification;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->copyable()
                    ->copyMessage('Order number copied !')
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('customer.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.name')
                    ->searchable()
                    ->limit(30),
                TextColumn::make('quantity')
                    ->numeric()
                    ->alignCenter(),
                TextColumn::make('final_amount')
                    ->money('IDR')
                    ->weight('bold')
                    ->sortable(),
                SelectColumn::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'cancelled' => 'Cancelled',
                        'refunded' => 'Refunded',
                    ])
                    ->selectablePlaceholder(false),
                SelectColumn::make('payment_status')
                    ->options([
                        'unpaid' => 'Unpaid',
                        'paid' => 'Paid',
                        'partial' => 'Partial',
                        'refunded' => 'Refunded',
                    ])
                    ->selectablePlaceholder(false),

                TextColumn::make('payment_date')
                    ->dateTime()
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([
                //
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'cancelled' => 'Cancelled',
                        'refunded' => 'Refunded',
                    ]),

                SelectFilter::make('payment_status')
                    ->options([
                        'unpaid' => 'Unpaid',
                        'paid' => 'Paid',
                        'partial' => 'Partial',
                        'refunded' => 'Refunded',
                    ]),

                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from'),
                        DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),

                Action::make('create_payment')
                    ->label('Create Payment')
                    ->icon('heroicon-o-credit-card')
                    ->color('success')
                    ->visible(fn (Order $record): bool => $record->status === 'pending' &&
                        $record->payment_status === 'unpaid' &&
                        auth()->user()->can('process_payments')
                    )
                    ->action(function (Order $record) {
                        $midtransService = app(MidtransService::class);
                        $result = $midtransService->createTransaction($record);

                        if ($result['success']) {
                            Notification::make()
                                ->title('Payment link created successfully')
                                ->success()
                                ->send();

                            // Open payment page in new tab
                            redirect()->away($result['redirect_url']);
                        } else {
                            Notification::make()
                                ->title('Failed to create payment')
                                ->body($result['message'])
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('check_status')
                    ->label('Check Status')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->visible(fn (Order $record): bool => ! empty($record->payment_reference) &&
                        auth()->user()->can('view_orders')
                    )
                    ->action(function (Order $record) {
                        $midtransService = app(MidtransService::class);
                        $result = $midtransService->getTransactionStatus($record->order_number);

                        if ($result['success']) {
                            $status = $result['data']->transaction_status;
                            Notification::make()
                                ->title('Payment Status: '.ucfirst($status))
                                ->body('Last updated: '.now()->format('Y-m-d H:i:s'))
                                ->info()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Failed to check payment status')
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('refund')
                    ->label('Refund')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->visible(fn (Order $record): bool => $record->payment_status === 'paid' &&
                        auth()->user()->can('refund_orders')
                    )
                    ->requiresConfirmation()
                    ->modalDescription('Are you sure you want to refund this payment? This action cannot be undone.')
                    ->form([
                        TextInput::make('refund_amount')
                            ->label('Refund Amount')
                            ->numeric()
                            ->prefix('Rp')
                            ->helperText('Leave empty for full refund'),
                        Textarea::make('refund_reason')
                            ->label('Refund Reason')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (Order $record, array $data) {
                        $midtransService = app(MidtransService::class);
                        $amount = $data['refund_amount'] ? (int) $data['refund_amount'] : null;

                        $result = $midtransService->refundTransaction(
                            $record->order_number,
                            $amount,
                            $data['refund_reason']
                        );

                        if ($result['success']) {
                            Notification::make()
                                ->title('Refund processed successfully')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Failed to process refund')
                                ->body($result['message'])
                                ->danger()
                                ->send();
                        }
                    }),

                DeleteAction::make()
                    ->visible(fn () => auth()->user()->can('delete_orders')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'create' => CreateOrder::route('/create'),
            'view' => ViewOrder::route('/{record}'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }
}
