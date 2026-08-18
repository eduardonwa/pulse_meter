<?php

namespace App\Filament\Resources\RoutineTemplates;

use App\Filament\Resources\RoutineTemplates\Pages\CreateRoutineTemplate;
use App\Filament\Resources\RoutineTemplates\Pages\EditRoutineTemplate;
use App\Filament\Resources\RoutineTemplates\Pages\ListRoutineTemplates;
use App\Filament\Resources\RoutineTemplates\Schemas\RoutineTemplateForm;
use App\Filament\Resources\RoutineTemplates\Tables\RoutineTemplatesTable;
use App\Models\RoutineTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class RoutineTemplateResource extends Resource
{
    protected static ?string $model = RoutineTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFire;
    
    protected static ?string $recordTitleAttribute = 'id';

    public static function getRecordTitle(
        ?Model $record
    ): ?string {
        if (! $record instanceof RoutineTemplate) {
            return null;
        }

        return $record->englishTranslation?->title
            ?? $record->spanishTranslation?->title
            ?? "Routine template #{$record->getKey()}";
    }

    public static function form(Schema $schema): Schema
    {
        return RoutineTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RoutineTemplatesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoutineTemplates::route('/'),
            'create' => CreateRoutineTemplate::route('/create'),
            'edit' => EditRoutineTemplate::route('/{record}/edit'),
        ];
    }
}
