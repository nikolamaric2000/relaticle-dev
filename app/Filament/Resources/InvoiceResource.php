<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\InvoiceResource\Pages\CreateInvoice;
use App\Filament\Resources\InvoiceResource\Pages\EditInvoice;
use App\Filament\Resources\InvoiceResource\Pages\ListInvoices;
use App\Filament\Resources\InvoiceResource\Pages\ViewInvoice;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\People;
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
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

final class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $recordTitleAttribute = 'invoice_number';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 4;

    protected static string|\UnitEnum|null $navigationGroup = null;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('filament/resources/invoice.sections.general'))
                    ->columns(12)
                    ->schema([
                        Hidden::make('team_id')
                            ->default(auth()->user()?->currentTeam?->getKey()),
                        TextInput::make('invoice_number')
                            ->label(__('filament/resources/invoice.fields.invoice_number.label'))
                            ->required()
                            ->maxLength(50)
                            ->columnSpan(4),
                        Select::make('invoiceable_type')
                            ->label(__('filament/resources/invoice.fields.client_type.label'))
                            ->options([
                                People::class => __('filament/resources/invoice.fields.client_type.people'),
                                Company::class => __('filament/resources/invoice.fields.client_type.company'),
                            ])
                            ->reactive()
                            ->afterStateUpdated(fn (Set $set) => $set('invoiceable_id', null))
                            ->columnSpan(4),
                        Select::make('invoiceable_id')
                            ->label(__('filament/resources/invoice.fields.client.label'))
                            ->options(fn (callable $get): array => match ($get('invoiceable_type')) {
                                People::class => People::query()->pluck('name', 'id')->toArray(),
                                Company::class => Company::query()->pluck('name', 'id')->toArray(),
                                default => [],
                            })
                            ->searchable()
                            ->preload()
                            ->columnSpan(4),
                        DatePicker::make('date')
                            ->label(__('filament/resources/invoice.fields.date.label'))
                            ->required()
                            ->default(now())
                            ->columnSpan(2),
                        DatePicker::make('due_date')
                            ->label(__('filament/resources/invoice.fields.due_date.label'))
                            ->required()
                            ->default(now()->addDays(30))
                            ->columnSpan(2),
                ]),
                Section::make(__('filament/resources/invoice.sections.items'))
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->schema([
                                TextInput::make('description')
                                    ->label(__('filament/resources/invoice.fields.item_description.label'))
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(6),
                                TextInput::make('quantity')
                                    ->label(__('filament/resources/invoice.fields.quantity.label'))
                                    ->required()
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->columnSpan(3),
                                TextInput::make('unit_price')
                                    ->label(__('filament/resources/invoice.fields.unit_price.label'))
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->columnSpan(3),
                            ])
                            ->columns(12)
                            ->defaultItems(1)
                            ->addActionLabel(__('filament/resources/invoice.actions.add_item')),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->label(__('filament/resources/invoice.fields.invoice_number.label'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('client')
                    ->label(__('filament/resources/invoice.fields.client.label'))
                    ->getStateUsing(function (Invoice $record): string {
                        $invoiceable = $record->invoiceable;

                        return $invoiceable?->name ?? '—';
                    })
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('filament/resources/invoice.fields.status.label'))
                    ->badge()
                    ->color(fn (InvoiceStatus $state): string => match ($state) {
                        InvoiceStatus::Draft => 'gray',
                        InvoiceStatus::Sent => 'warning',
                        InvoiceStatus::Viewed => 'info',
                        InvoiceStatus::PartialPaid => 'warning',
                        InvoiceStatus::Paid => 'success',
                        InvoiceStatus::Overdue => 'danger',
                        InvoiceStatus::Cancelled => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('date')
                    ->label(__('filament/resources/invoice.fields.date.label'))
                    ->date()
                    ->sortable(),
                TextColumn::make('due_date')
                    ->label(__('filament/resources/invoice.fields.due_date.label'))
                    ->date()
                    ->sortable(),
                TextColumn::make('total')
                    ->label(__('filament/resources/invoice.fields.total.label'))
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('creator.name')
                    ->label(__('filament/resources/invoice.fields.creator.label'))
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->getStateUsing(fn (Invoice $record): string => $record->created_by),
                TextColumn::make('created_at')
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
                SelectFilter::make('status')
                    ->label(__('filament/resources/invoice.fields.status.label'))
                    ->options(InvoiceStatus::class)
                    ->multiple(),
                Filter::make('date_range')
                    ->label(__('filament/resources/invoice.filters.date_range.label'))
                    ->form([
                        DatePicker::make('from')
                            ->label(__('filament/resources/invoice.filters.date_range.from')),
                        DatePicker::make('until')
                            ->label(__('filament/resources/invoice.filters.date_range.until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $query, $date): Builder => $query->where('date', '>=', $date))
                            ->when($data['until'], fn (Builder $query, $date): Builder => $query->where('date', '<=', $date));
                    }),
                SelectFilter::make('team')
                    ->label(__('filament/resources/invoice.fields.team.label'))
                    ->relationship('team', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),
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

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoices::route('/'),
            'create' => CreateInvoice::route('/create'),
            'edit' => EditInvoice::route('/{record}/edit'),
            'view' => ViewInvoice::route('/{record}'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('filament/resources/invoice.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament/resources/invoice.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament/resources/invoice.navigation_label');
    }

    public static function getNavigationGroup(): string
    {
        return __('filament/navigation.groups.sales');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['team', 'invoiceable'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
