<?php

declare(strict_types=1);

namespace Garantio\Model;

defined('ABSPATH') || exit;

/**
 * Legal guarantee reminder and durability note texts (Directive 2011/83/EU art. 6(1)(l) and (la)).
 *
 * One English source string each; other languages come from translation files.
 */
final class GuaranteeNotice
{
    public const DEFAULT_STATUTORY_YEARS = 2;

    /**
     * Text of the legal guarantee reminder (not the harmonised notice of Implementing Regulation (EU) 2025/1960).
     */
    public static function reminder(int $years): string
    {
        return sprintf(
            /* translators: %d: legal guarantee duration in years (default 2) */
            __('You are entitled by law to a legal guarantee of conformity from the seller, free of charge, for at least %d years. National law may provide a longer period.', 'garantio'),
            $years
        );
    }

    /**
     * Text of the commercial durability guarantee note (not the harmonised label of Implementing Regulation (EU) 2025/1960).
     */
    public static function durabilityNote(string $producer, int $years): string
    {
        return sprintf(
            /* translators: 1: producer name, 2: duration in years */
            __('%1$s offers a %2$d-year commercial guarantee of durability at no extra cost.', 'garantio'),
            $producer,
            $years
        );
    }
}
