<?php

namespace App\Filament\Resources;

use App\Filament\Actions\ResetUserPasswordAction;
use App\Filament\Concerns\ConfiguresTableToolbar;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Support\UserAccess;
use App\Support\UserNotifier;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    use ConfiguresTableToolbar;

    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make()->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('email')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    Forms\Components\Select::make('role')
                        ->required()
                        ->options(fn (): array => UserAccess::assignableRoleOptions(auth()->user())),
                    Forms\Components\TextInput::make('password')
                        ->password()
                        ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                        ->dehydrated(fn ($state) => filled($state))
                        ->required(fn (string $operation) => $operation === 'create')
                        ->maxLength(255),
                    Forms\Components\ColorPicker::make('color')
                        ->default('#0891b2'),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        $roleOptions = auth()->user()?->isAdmin()
            ? UserAccess::roleLabels()
            : collect(UserAccess::roleLabels())->except('admin')->all();

        return static::configureTableToolbar($table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => UserAccess::roleLabels()[$state] ?? ucwords(str_replace('_', ' ', $state)))
                    ->color(fn (string $state) => match ($state) {
                        'admin' => 'danger',
                        'program_manager' => 'purple',
                        'project_manager' => 'warning',
                        'project_admin' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\ColorColumn::make('color'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options($roleOptions),
            ])
            ->actions([
                ResetUserPasswordAction::make()
                    ->visible(fn (User $record) => static::canEdit($record)),
                Action::make('sendActivation')
                    ->label('Send activation email')
                    ->icon('heroicon-o-envelope')
                    ->requiresConfirmation()
                    ->visible(fn (User $record) => static::canEdit($record))
                    ->action(fn (User $record) => UserNotifier::sendActivation($record, null)),
                EditAction::make()
                    ->visible(fn (User $record) => static::canEdit($record)),
                DeleteAction::make()
                    ->visible(fn (User $record) => static::canDelete($record)),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->isAdmin() ?? false),
                ]),
            ]));
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user) {
            UserAccess::scopeVisibleUsers($query, $user);
        }

        return $query;
    }

    public static function canViewAny(): bool
    {
        return UserAccess::canManageUsers(auth()->user());
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return $record instanceof User
            && UserAccess::canEditUser(auth()->user(), $record);
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof User
            && UserAccess::canDeleteUser(auth()->user(), $record);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
