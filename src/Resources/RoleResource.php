<?php

namespace BezhanSalleh\FilamentShield\Resources;

use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use BezhanSalleh\FilamentShield\Forms\ShieldSelectAllToggle;
use BezhanSalleh\FilamentShield\Resources\RoleResource\Pages;
use BezhanSalleh\FilamentShield\Support\Utils;
use BezhanSalleh\FilamentShield\Traits\HasShieldFormComponents;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Panel;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;

class RoleResource extends Resource implements HasShieldPermissions
{
    use HasShieldFormComponents;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Form::make()
                    ->schema([
                        Section::make()
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('name')
                                    ->label(__('filament-shield::filament-shield.field.name'))
                                    ->unique(
                                        ignoreRecord: true, /** @phpstan-ignore-next-line */
                                        modifyRuleUsing: fn (Unique $rule) => Utils::isTenancyEnabled() ? $rule->where(Utils::getTenantModelForeignKey(), app(\Filament\Panel::class)->getTenant()?->id) : $rule
                                    )
                                    ->required()
                                    ->maxLength(255),

                                \Filament\Forms\Components\TextInput::make('guard_name')
                                    ->label(__('filament-shield::filament-shield.field.guard_name'))
                                    ->default(Utils::getFilamentAuthGuard())
                                    ->nullable()
                                    ->maxLength(255),

                                \Filament\Forms\Components\Select::make(config('permission.column_names.team_foreign_key'))
                                    ->label(__('filament-shield::filament-shield.field.team'))
                                    ->placeholder(__('filament-shield::filament-shield.field.team.placeholder'))
                                    /** @phpstan-ignore-next-line */
                                    ->default([app(\Filament\Panel::class)->getTenant()?->id])
                                    ->options(fn (): Arrayable => Utils::isTenancyEnabled() && Utils::getTenantModel() ? Utils::getTenantModel()::pluck('name', 'id') : collect())
                                    ->hidden(fn (): bool => ! (static::shield()->isCentralApp() && Utils::isTenancyEnabled()))
                                    ->dehydrated(fn (): bool => ! (static::shield()->isCentralApp() && Utils::isTenancyEnabled())),
                                ShieldSelectAllToggle::make('select_all')
                                    ->onIcon('heroicon-s-shield-check')
                                    ->offIcon('heroicon-s-shield-exclamation')
                                    ->label(__('filament-shield::filament-shield.field.select_all.name'))
                                    ->helperText(fn (): HtmlString => new HtmlString(__('filament-shield::filament-shield.field.select_all.message')))
                                    ->dehydrated(fn (bool $state): bool => $state),

                            ])
                            ->columns([
                                'sm' => 2,
                                'lg' => 3,
                            ]),
                        static::getShieldFormComponents(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->weight('font-medium')
                    ->label(__('filament-shield::filament-shield.column.name'))
                    ->formatStateUsing(fn ($state): string => Str::headline($state))
                    ->searchable(),
                Tables\Columns\TextColumn::make('guard_name')
                    ->badge()
                    ->color('warning')
                    ->label(__('filament-shield::filament-shield.field.guard_name')),
                Tables\Columns\TextColumn::make('permissions_count')
                    ->counts('permissions')
                    ->label(__('filament-shield::filament-shield.column.permissions_count')),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->label(__('filament-shield::filament-shield.column.updated_at'))
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
            'view' => Pages\ViewRole::route('/{record}'),
        ];
    }

    public static function getNavigationIcon(): ?string
    {
        return config('filament-shield.shield_resource.navigation_icon', 'heroicon-o-shield-check');
    }

    public static function getNavigationLabel(): string
    {
        return config('filament-shield.shield_resource.navigation_label', 'Shield');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('filament-shield.shield_resource.navigation_group') ? 'Shield' : null;
    }

    public static function getNavigationSort(): ?int
    {
        return config('filament-shield.shield_resource.navigation_sort');
    }

    public static function getNavigationBadge(): ?string
    {
        return config('filament-shield.shield_resource.navigation_badge') ? (string) static::getModel()::count() : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return config('filament-shield.shield_resource.navigation_badge') ? 'warning' : null;
    }

    public static function getSubNavigationPosition(): SubNavigationPosition
    {
        return Utils::getSubNavigationPosition() ?? SubNavigationPosition::Top;
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return Utils::getResourceSlug();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return config('filament-shield.shield_resource.should_register_navigation', true);
    }

    public static function isGloballySearchable(): bool
    {
        return config('filament-shield.shield_resource.is_globally_searchable', false);
    }

    public static function getModel(): string
    {
        return config('permission.models.role');
    }

    public static function getNavigationUrl(): string
    {
        return static::getUrl();
    }

    public static function getUrl(?string $name = null, array $parameters = [], bool $isAbsolute = false, ?string $panel = null, $tenant = null, bool $shouldGuessMissingParameters = true): string
    {
        return parent::getUrl($name, $parameters, $isAbsolute, $panel, $tenant, $shouldGuessMissingParameters);
    }

    public static function shield(): FilamentShieldPlugin
    {
        return app(FilamentShieldPlugin::class);
    }
}
