<?php

declare(strict_types=1);

namespace Garantio\Model;

defined('ABSPATH') || exit;

/**
 * Legal guarantee reminder and durability note texts (Directive 2011/83/EU art. 6(1)(l) and (la)).
 */
final class GuaranteeNotice
{
    public const DEFAULT_STATUTORY_YEARS = 2;

    /**
     * Default text of the legal guarantee reminder (not the art. 22a harmonised notice).
     *
     * @return array<string, string>
     */
    public static function defaultNotices(): array
    {
        return [
            /* translators: %d: statutory guarantee duration in years (default 2) */
            'en' => __('Consumers are entitled by law to a minimum %d-year legal guarantee of conformity free of charge with the seller.', 'garantio'),
            /* translators: %d: statutory guarantee duration in years (default 2) */
            'pl' => __('Konsumenci sa uprawnieni na mocy prawa do bezplatnej co najmniej %d-letniej ustawowej gwarancji zgodnosci towaru z umowa.', 'garantio'),
            /* translators: %d: statutory guarantee duration in years (default 2) */
            'de' => __('Verbrauchern steht von Gesetzes wegen eine unentgeltliche gesetzliche Gewahrleistung von mindestens %d Jahren beim Verkaufer zu.', 'garantio'),
            /* translators: %d: statutory guarantee duration in years (default 2) */
            'es' => __('Los consumidores tienen derecho por ley a una garantia legal de conformidad gratuita de al menos %d anos ante el vendedor.', 'garantio'),
            /* translators: %d: statutory guarantee duration in years (default 2) */
            'ro' => __('Consumatorii au dreptul legal la o garantie de conformitate gratuita de cel putin %d ani fata de vanzator.', 'garantio'),
        ];
    }

    /**
     * Default text of the commercial durability guarantee note (not the art. 22a harmonised label).
     *
     * @return array<string, string>
     */
    public static function durabilityLabels(): array
    {
        return [
            /* translators: 1: producer name or label, 2: duration in years */
            'en' => __('%1$s offers a %2$d-year commercial guarantee of durability at no extra cost.', 'garantio'),
            /* translators: 1: producer name or label, 2: duration in years */
            'pl' => __('%1$s oferuje %2$d-letnia dobrowolna gwarancje trwalosci bez dodatkowych oplat.', 'garantio'),
            /* translators: 1: producer name or label, 2: duration in years */
            'de' => __('%1$s gewahrt eine %2$d-jahrige gewerbliche Haltbarkeitsgarantie ohne zusatzliche Kosten.', 'garantio'),
            /* translators: 1: producer name or label, 2: duration in years */
            'es' => __('%1$s ofrece una garantia comercial de durabilidad de %2$d anos sin coste adicional.', 'garantio'),
            /* translators: 1: producer name or label, 2: duration in years */
            'ro' => __('%1$s ofera o garantie comerciala de durabilitate de %2$d ani fara costuri suplimentare.', 'garantio'),
        ];
    }
}
