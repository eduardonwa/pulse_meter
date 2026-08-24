<?php

namespace App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks;

class SpanishCallToActionBlock extends CallToActionBlock
{
    public static function getId(): string
    {
        return 'call-to-action-es';
    }

    protected static function getLocale(): string
    {
        return 'es';
    }
}