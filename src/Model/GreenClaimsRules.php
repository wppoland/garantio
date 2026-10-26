<?php

declare(strict_types=1);

namespace Garantio\Model;

defined('ABSPATH') || exit;

/**
 * Green claims compliance rules based on Directive (EU) 2024/825 Annex I.
 */
final class GreenClaimsRules
{
    /**
     * Banned generic environmental terms that require recognized official certification.
     *
     * @return array<int, array<string, string>>
     */
    public static function bannedPhrases(): array
    {
        return [
            [
                'term' => 'climate neutral',
                'pattern' => '/\b(climate[- ]neutral|carbon[- ]neutral|co2[- ]neutral|neutraln[ye] klimatycznie|klimaneutral)\b/i',
                'legal_basis' => 'Directive (EU) 2024/825 Annex I point 4b',
                'reason' => __('Claims that a product has a neutral, reduced or positive greenhouse gas emission impact based on carbon offsetting are banned.', 'garantio'),
            ],
            [
                'term' => 'eco-friendly / green',
                'pattern' => '/\b(eco[- ]friendly|environmentally friendly|przyjazn[ye] dla srodowiska|ekologiczn[ye]|umweltfreundlich|ecologico)\b/i',
                'legal_basis' => 'Directive (EU) 2024/825 Annex I point 4a',
                'reason' => __('Generic environmental claims without demonstrated recognized excellent environmental performance (e.g. EU Ecolabel) are prohibited.', 'garantio'),
            ],
            [
                'term' => '100% biodegradable',
                'pattern' => '/\b(100% biodegradable|biodegradable|biodegradowalny|biodegradabil)\b/i',
                'legal_basis' => 'Directive (EU) 2024/825 Annex I point 4a',
                'reason' => __('Unsubstantiated generic biodegradability claims without recognized certification are prohibited.', 'garantio'),
            ],
            [
                'term' => 'climate positive / net zero',
                'pattern' => '/\b(climate positive|net zero|pozytywny klimatycznie)\b/i',
                'legal_basis' => 'Directive (EU) 2024/825 Annex I point 4b',
                'reason' => __('Net zero or climate positive product claims based on carbon offset credits are banned.', 'garantio'),
            ],
        ];
    }
}
