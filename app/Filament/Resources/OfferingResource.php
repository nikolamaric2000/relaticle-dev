<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\OfferingType;
use App\Filament\Resources\OfferingResource\Pages\ListOfferings;
use App\Models\Offering;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

final class OfferingResource extends Resource
{
    protected static ?string $model = Offering::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static ?int $navigationSort = 5;

    protected static string|\UnitEnum|null $navigationGroup = null;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('filament/resources/offering.fields.name.label'))
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->label(__('filament/resources/offering.fields.description.label'))
                    ->maxLength(65535)
                    ->columnSpanFull(),
                TextInput::make('price')
                    ->label(__('filament/resources/offering.fields.price.label'))
                    ->required()
                    ->numeric()
                    ->minValue(0),
                Select::make('type')
                    ->label(__('filament/resources/offering.fields.type.label'))
                    ->options(OfferingType::class)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('filament/resources/offering.fields.name.label'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('type')
                    ->label(__('filament/resources/offering.fields.type.label'))
                    ->badge()
                    ->color(fn (OfferingType $state): string => match ($state) {
                        OfferingType::Product => 'success',
                        OfferingType::Service => 'info',
                    })
                    ->sortable(),
                TextColumn::make('price')
                    ->label(__('filament/resources/offering.fields.price.label'))
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('creator.name')
                    ->label(__('filament/resources/offering.fields.creator.label'))
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->getStateUsing(fn (Offering $record): string => $record->creator_id ? $record->creator->name : ''),
                TextColumn::make('created_at')
                    ->label(__('filament/resources/offering.fields.created_at.label'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    RestoreAction::make(),
                    DeleteAction::make(),
                    ForceDeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOfferings::route('/'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('filament/resources/offering.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament/resources/offering.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament/resources/offering.navigation_label');
    }

    public static function getNavigationGroup(): string
    {
        return __('filament/navigation.groups.sales');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['team'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}