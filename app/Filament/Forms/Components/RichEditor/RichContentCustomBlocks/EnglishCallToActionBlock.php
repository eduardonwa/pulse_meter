<?php

namespace App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks;

class EnglishCallToActionBlock extends CallToActionBlock
{
    public static function getId(): string
    {
        return 'call-to-action-en';
    }

    protected static function getLocale(): string
    {
        return 'en';
    }
}