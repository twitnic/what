<?php

declare(strict_types=1);

namespace App\Enums;

enum PostType: string
{
    case News = 'news';
    case Event = 'event';
    case Warning = 'warning';
    case Traffic = 'traffic';
    case City = 'city';
    case Sport = 'sport';
    case Offer = 'offer';

    public function label(): string
    {
        return match ($this) {
            self::News => 'Nachricht', self::Event => 'Veranstaltung', self::Warning => 'Warnung', self::Traffic => 'Verkehr', self::City => 'Stadtinformation', self::Sport => 'Sport', self::Offer => 'Angebot'
        };
    }
}
