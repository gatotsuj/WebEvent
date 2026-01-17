<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Product;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Psy\Util\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Basic Information')
                ->schema([
                    Select::make('category_id')
                    ->relationship('category', 'name')
                    ->required()
                    ->createOptionForm([
                        TextInput::make('name')
                        ->required(),
                        TextInput::make('slug')
                        ->required(),
                        TextInput::make('description')
                        ->required(),
                    ]),

                    TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated( fn (string $content , $state, callable $set) =>
                    $content === 'create' ? $set('slug', Str::slug($state)): null),

                    TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(Product::class, 'slug', ignoreRecord: true),

                    Select::make('status')
                        ->options([
                            'draft' => 'Draft',
                            'published' => 'Published',
                            'sold_out' => 'Sold Out',
                            'cancelled' => 'Cancelled',
                        ])
                        ->default('draft')
                        ->required(),

                    Toggle::make('is_featured')
                     ->default(false),
                ])->columns(2),


                Section::make('Content')
                ->schema([
                    Textarea::make('short_description')
                    ->maxLength(500)
                    ->rows(3),

                    RichEditor::make('description')
                    ->required()
                    ->columnSpanFull(),
                ]),

                Section::make('Media')
                ->schema([
                    FileUpload::make('featured_image')
                    ->image()
                    ->directory('products')
                    ->visibility('public')
                    ->reorderable(),
                ])->columns(2),


                Section::make('Pricing & Stock')
                ->schema([
                    TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),

                    TextInput::make('discount_price')
                    ->numeric()
                    ->prefix('Rp')
                    ->lt('price'),

                    TextInput::make('views')
                    ->numeric()
                    ->default(0)
                    ->disabled(),
                ])->columns(2),

                Section::make('Evemt Details')
                ->schema([
                    DateTimePicker::make('event_date')
                    ->required()
                    ->native(false),

                    TimePicker::make('event_time')
                    ->required(),

                    TextInput::make('event_location')
                    ->required()
                    ->maxLength(255),

                    KeyValue::make('event_details')
                    ->keyLabel('Detail')
                    ->valueLabel('Information')
                    ->columnSpanFull(),
                ])->columns(2),
            ]);
    }
}
