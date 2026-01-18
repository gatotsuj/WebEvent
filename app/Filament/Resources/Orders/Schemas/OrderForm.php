<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Order;
use App\Models\Product;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Order Information')
                    ->schema([
                        TextInput::make('order_number')
                            ->required()
                            ->unique(Order::class, 'order_number', ignoreRecord: true)
                            ->default(fn () => 'ORD-'.date('Ymd').'-'.str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT)),

                        Select::make('customer_id')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                TextInput::make('name')->required(),
                                TextInput::make('email')->email()->required(),
                                TextInput::make('phone')->required(),
                            ]),

                        Select::make('product_id')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $product = Product::find($state);
                                    if ($product) {
                                        $set('unit_price', $product->current_price);
                                    }
                                }
                            }),

                        TextInput::make('quantity')
                            ->required()
                            ->numeric()
                            ->default(1)
                            ->reactive()
                            ->afterStateUpdated((function ($state, callable $get, callable $set) {
                                $unitPrice = $get('unit_price');
                                if ($unitPrice && $state) {
                                    $totalPrice = $unitPrice * $state;
                                    $discountAmount = $get('discount_amount') ?? 0;
                                    $set('total_price', $totalPrice);
                                    $set('final_amount', $totalPrice - $discountAmount);
                                }
                            })),

                    ])->columns(2),

                Section::make('Pricing')
                    ->schema([
                        TextInput::make('unit_price')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                $quantity = $get('quantity') ?? 1;
                                if ($state) {
                                    $totalPrice = $state * $quantity;
                                    $discountAmount = $get('discount_amount') ?? 0;
                                    $set('total_price', $totalPrice);
                                    $set('final_amount', $totalPrice - $discountAmount);
                                }
                            }),

                        TextInput::make('discount_amount')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                $totalPrice = $get('total_price') ?? 0;
                                $set('final_amount', $totalPrice - ($state ?? 0));
                            }),

                        TextInput::make('total_price')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled(),

                        TextInput::make('final_amount')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled(),
                    ])->columns(2),

                Section::make('Status & Payment')
                    ->schema([
                        Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'paid' => 'Paid',
                                'cancelled' => 'Cancelled',
                                'refunded' => 'Refunded',
                            ])
                            ->default('pending')
                            ->required(),

                        Select::make('payment_status')
                            ->options([
                                'unpaid' => 'Unpaid',
                                'paid' => 'Paid',
                                'partial' => 'Partial',
                                'refunded' => 'Refunded',
                            ])
                            ->default('unpaid')
                            ->required(),

                        TextInput::make('payment_method')
                            ->maxLength(255),

                        TextInput::make('payment_reference')
                            ->maxLength(255),

                        DateTimePicker::make('payment_date')
                            ->native(false),
                    ])
                    ->columns(2),

                Section::make('Additional Information')
                    ->schema([
                        KeyValue::make('customer_details')
                            ->keyLabel('Field')
                            ->valueLabel('Value'),

                        KeyValue::make('payment_details')
                            ->keyLabel('Field')
                            ->valueLabel('Value'),

                        Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

            ]);
    }
}
