<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Models\Customer;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
         ->schema([
             Section::make('Personal Information')
                 ->schema([
                     TextInput::make('name')
                         ->required()
                         ->maxLength(255),

                     TextInput::make('email')
                         ->required()
                         ->email()
                         ->unique(Customer::class, 'email', ignoreRecord: true)
                         ->maxLength(255),

                     TextInput::make('phone')
                         ->tel()
                         ->maxLength(15),

                     DatePicker::make('birth_date')
                         ->native(false),

                     Select::make('gender')
                         ->options([
                             'male' => 'Male',
                             'female' => 'Female',
                         ])
                         ->native(false),

                     Toggle::make('is_active')
                         ->default(true),

                 ])->columns(2),

             Section::make('Address Information')
             ->schema([
                 Textarea::make('address')
                 ->rows(3)
                 ->required(),

                 TextInput::make('city')
                 ->maxLength(255),

                 TextInput::make('province')
                 ->maxLength(255),

                 TextInput::make('postal_code')
                 ->maxLength(255),
             ])->columns(3),


             Section::make('Account Information')
             ->schema([
                 TextInput::make('password')
                 ->password()
                     ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                     ->dehydrated(fn ($state) => filled($state))
                     ->required(fn (string $context): bool => $context === 'create'),
                 DatePicker::make('last_login_at')
                 ->disabled(),

                 DatePicker::make('email_verified_at')
                 ->disabled(),
             ])->columns(2),
         ]);

    }
}
