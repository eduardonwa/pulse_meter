<?php

namespace App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks;

use Filament\Actions\Action;
use App\Models\RoutineTemplateTranslation;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Storage;

abstract class CallToActionBlock extends RichContentCustomBlock
{
    abstract protected static function getLocale(): string;

    protected static function translate(string $key): string
    {
        return trans(
            "blog.cta.{$key}",
            locale: static::getLocale(),
        );
    }

    public static function getId(): string
    {
        return 'call-to-action';
    }

    public static function getLabel(): string
    {
        return 'Call to action';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalHeading('Configurar call to action')
            ->modalWidth(Width::ThreeExtraLarge)
            ->schema([
                Select::make('type')
                    ->label('Tipo de CTA')
                    ->options([
                        'subscription' => 'Suscripción a Dorelog',
                        'resource' => 'Recurso del artículo',
                    ])
                    ->default('subscription')
                    ->live()
                    ->afterStateUpdated(function (string $state, Set $set): void {
                        $set(
                            'heading',
                            static::translate("{$state}.heading"),
                        );
                        $set(
                            'button_label',
                            static::translate("{$state}.button_label"),
                        );
                        $set(
                            'button_url',
                            $state === 'subscription' ? '/register' : '/routines',
                        );
                        $set(
                            'button_color',
                            $state === 'subscription'
                                ? 'dark'
                                : 'secondary',
                        );
                    })
                    ->required(),

                Select::make('resource_type')
                    ->label('Destino del recurso')
                    ->options([
                        'custom' => 'URL personalizada',
                        'routine' => 'Rutina',
                    ])
                    ->default('custom')
                    ->live()
                    ->visible(
                        fn (Get $get): bool => $get('type') === 'resource'
                    )
                    ->required(
                        fn (Get $get): bool => $get('type') === 'resource'
                    ),

                Select::make('routine_template_id')
                    ->label('Rutina')
                    ->options(
                        fn (): array => RoutineTemplateTranslation::query()
                            ->where('locale', static::getLocale())
                            ->published()
                            ->orderBy('title')
                            ->pluck('title', 'routine_template_id')
                            ->all()
                    )
                    ->searchable()
                    ->visible(
                        fn (Get $get): bool =>
                            $get('type') === 'resource'
                            && $get('resource_type') === 'routine'
                    )
                    ->required(
                        fn (Get $get): bool =>
                            $get('type') === 'resource'
                            && $get('resource_type') === 'routine'
                    ),

                TextInput::make('heading')
                    ->label('Título')
                    ->default(
                        fn (): string => static::translate(
                            'subscription.heading'
                        )
                    )
                    ->required(),

                RichEditor::make('benefits')
                    ->label('Beneficios')
                    ->default(
                        fn (): string => static::translate(
                            'subscription.benefits'
                        )
                    )
                    ->toolbarButtons([
                        ['bold', 'italic'],
                        ['bulletList', 'orderedList'],
                        ['undo', 'redo'],
                    ])
                    ->visible(
                        fn (Get $get): bool => ($get('type') ?? 'subscription')
                            === 'subscription'
                    )
                    ->required(
                        fn (Get $get): bool => ($get('type') ?? 'subscription')
                            === 'subscription'
                    ),

                Textarea::make('description')
                    ->label('Descripción')
                    ->default(
                        fn (): string => static::translate(
                            'resource.description'
                        )
                    )
                    ->rows(3)
                    ->visible(
                        fn (Get $get): bool => $get('type') === 'resource'
                    )
                    ->required(
                        fn (Get $get): bool => $get('type') === 'resource'
                    ),

                TextInput::make('stat_1')
                    ->label('Dato 1')
                    ->placeholder('22 min')
                    ->visible(
                        fn (Get $get): bool => $get('type') === 'resource'
                    ),

                TextInput::make('stat_2')
                    ->label('Dato 2')
                    ->placeholder('5 ejercicios')
                    ->visible(
                        fn (Get $get): bool => $get('type') === 'resource'
                    ),

                TextInput::make('stat_3')
                    ->label('Dato 3')
                    ->placeholder('5 días')
                    ->visible(
                        fn (Get $get): bool => $get('type') === 'resource'
                    ),

                TextInput::make('button_label')
                    ->label('Texto del botón')
                    ->default(
                        fn (): string => static::translate(
                            'subscription.button_label'
                        )
                    )
                    ->required(),

                TextInput::make('button_url')
                    ->label('URL del botón')
                    ->default('/register')
                    ->visible(
                        fn (Get $get): bool =>
                            $get('type') !== 'resource'
                            || ($get('resource_type') ?? 'custom') === 'custom'
                    )
                    ->required(
                        fn (Get $get): bool =>
                            $get('type') !== 'resource'
                            || ($get('resource_type') ?? 'custom') === 'custom'
                    ),

                Select::make('button_color')
                    ->label('Color del botón')
                    ->options([
                        'dark' => 'Oscuro',
                        'accent' => 'Acento',
                        'secondary' => 'Secondary degradado',
                        'red' => 'Rojo',
                        'yellow' => 'Amarillo',
                        'blue' => 'Azul',
                        'green' => 'Verde',
                    ])
                    ->default('dark')
                    ->required(),
            ]);
    }

    public static function toPreviewHtml(array $config): string
    {
        return view('filament.forms.components.rich-editor.rich-content-custom-blocks.call-to-action.index', [
            ...$config,
            'buttonUrl' => static::resolveButtonUrl($config),
            'isPreview' => true,
        ])->render();
    }

    public static function toHtml(array $config, array $data): string
    {
        return view('filament.forms.components.rich-editor.rich-content-custom-blocks.call-to-action.index', [
            ...$config,
            'buttonUrl' => static::resolveButtonUrl($config),
            'isPreview' => false,
        ])->render();
    }

    private static function resolveButtonUrl(array $config): string
    {
        $isRoutine = ($config['type'] ?? null) === 'resource'
            && ($config['resource_type'] ?? null) === 'routine';

        if (! $isRoutine) {
            return $config['button_url'] ?? '#';
        }

        $routineTemplateId = $config['routine_template_id'] ?? null;

        if (blank($routineTemplateId)) {
            return '#';
        }

        $locale = static::getLocale();

        $translation = RoutineTemplateTranslation::query()
            ->where('routine_template_id', $routineTemplateId)
            ->where('locale', $locale)
            ->published()
            ->first();

        if (! $translation) {
            return '#';
        }

        return route('routines.show', [
            'locale' => $locale,
            'slug' => $translation->slug,
        ], absolute: false);
    }
}
