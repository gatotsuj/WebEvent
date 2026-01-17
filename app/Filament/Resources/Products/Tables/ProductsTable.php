<?php

namespace App\Filament\Resources\Products\Tables;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                ImageColumn::make('featured_image')
                ->circular(),
                TextColumn::make('name')
                ->searchable()
                ->sortable()
                ->limit(30),
                TextColumn::make('category.name')
                ->badge()
                ->color('primary'),
                TextColumn::make('price')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('discount_price')
                    ->money()
                    ->sortable(),
                TextColumn::make('stock')
                    ->numeric()
                    ->sortable()
                    ->color(fn( string $state) : string => match (true) {
                        $state > 100 => 'success',
                        $state > 10 => 'warning',
                        default  => 'danger',
                    }),
                TextColumn::make('event_date')
                    ->date()
                    ->sortable(),
                SelectColumn::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                        'sold_out' => 'Sold Out',
                        'cancelled' => 'Cancelled',
                    ]),
                IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean(),
                TextColumn::make('orders_count')
                ->counts('orders')
                ->label('Orders')
                ->badge()
                ->color('success'),
                TextColumn::make('views')
                    ->numeric()
                    ->sortable(),

            ])
            ->filters([
                //
                SelectFilter::make('category')
                ->relationship('category', 'name'),

                SelectFilter::make('status')
                ->options([
                    'draft' => 'Draft',
                    'published' => 'Published',
                    'sold_out' => 'Sold Out',
                    'cancelled' => 'Cancelled',
                ]),

                TernaryFilter::make('is_featured')
                    ->label('Featured'),
                Filter::make('event_date')
                    ->form([
                        DatePicker::make('event_from'),
                        DatePicker::make('event_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['event_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('event_date', '>=', $date),
                            )
                            ->when(
                                $data['event_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('event_date', '<=', $date),
                            );
                    }),

            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])->defaultSort('created_at','desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'view' => ViewProduct::route('/{record}'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
