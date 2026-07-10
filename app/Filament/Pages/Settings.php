<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class Settings extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static bool $shouldRegisterNavigation = false;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $title = 'Settings';

    protected string $view = 'filament.pages.settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'date_format_style' => Auth::user()->date_format_style ?? 'mx',
            'timezone' => Auth::user()->timezone ?? 'America/Hermosillo',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('date_format_style')
                    ->label('Date style')
                    ->options([
                        'mx' => 'Day / month / year',
                        'us' => 'Month / day / year',
                    ])
                    ->required(),

                Select::make('timezone')
                    ->label('Timezone')
                    ->options([
                        'America/Hermosillo' => 'Sonora / Obregón / Hermosillo (GMT-07:00)',
                        'America/Mexico_City' => 'Ciudad de México',
                        'UTC' => 'UTC',
                    ])
                    ->required(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Auth::user()->update([
            'date_format_style' => $data['date_format_style'],
            'timezone' => $data['timezone'],
        ]);

        Notification::make()
            ->title('Settings saved')
            ->success()
            ->send();
    }
}