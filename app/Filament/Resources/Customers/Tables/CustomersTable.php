<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Models\Customer;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Email Copied')
                    ->icon('heroicon-s-envelope')
                    ->searchable(),
                TextColumn::make('phone')
                    ->icon('heroicon-m-phone')
                    ->searchable(),
                TextColumn::make('city')
                    ->searchable(),

                TextColumn::make('orders_count')
                ->counts('orders')
                ->label('Total Orders')
                ->badge()
                ->color('success'),

                IconColumn::make('is_active')
                ->boolean()
                ->label('Active'),

                TextColumn::make('last_login_at')
                ->dateTime()
                ->sortable()
                ->toggleable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
                TernaryFilter::make('is_active')
                    ->label('Active Status'),

                TernaryFilter::make('email_verified_at')
                    ->label('Email Verified')
                    ->nullable(),

                SelectFilter::make('city')
                ->options(fn (): array =>
                Customer::whereNotNull('city')
                    ->distinct()
                    ->pluck('city', 'city')
                    ->toArray()
                ),
            ])
            ->recordActions([
                EditAction::make(),
                ViewAction::make(),
                DeleteAction::make(),
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
            'index' => ListCustomers::route('/'),
            'create' => CreateCustomer::route('/create'),
            'view' => ViewCustomer::route('/{record}'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }
}
