<?php

declare(strict_types=1);

namespace Garantio\Model;

defined('ABSPATH') || exit;

/**
 * Phrases linked to Annex I, points 4a and 4c, of Directive 2005/29/EC (inserted by Directive (EU) 2024/825).
 *
 * Patterns are stems without a trailing word boundary so inflected forms match
 * ("umweltfreundliche", "przyjaznego dla środowiska").
 */
final class GreenClaimsRules
{
    /**
     * @return array<int, array<string, string>>
     */
    public static function bannedPhrases(): array
    {
        return [
            [
                'term' => 'climate neutral',
                'pattern' => '/\b(climate[- ]neutral|carbon[- ]neutral|co2[- ]neutral|neutraln\w* (klimatycznie|pod względem emisji)|klimaneutral|klimatneutral|neutro en carbono|clim[aá]ticamente neutr)/iu',
                'legal_basis' => 'Directive 2005/29/EC Annex I point 4c (Directive (EU) 2024/825)',
                'reason' => __('Banned when the neutral, reduced or positive greenhouse gas impact is based on offsetting emissions. Point 4c does not cover a claim based on the product\'s actual lifecycle impact (recital 12 of Directive (EU) 2024/825).', 'garantio'),
            ],
            [
                'term' => 'eco-friendly / ecological',
                'pattern' => '/\b(eco[- ]friendly|environmentally friendly|przyjazn\w* dla [śs]rodowiska|ekologiczn|umweltfreundlich|ecol[óo]gic)/iu',
                'legal_basis' => 'Directive 2005/29/EC Annex I point 4a (Directive (EU) 2024/825)',
                'reason' => __('A generic environmental claim is banned unless you can show recognised excellent environmental performance relevant to it, or the claim is specified in clear and prominent terms on the same medium (recital 9 of Directive (EU) 2024/825).', 'garantio'),
            ],
            [
                'term' => 'biodegradable',
                'pattern' => '/\b(biodegradab|biodegradowaln|biologisch abbaubar)/iu',
                'legal_basis' => 'Directive 2005/29/EC Annex I point 4a (Directive (EU) 2024/825)',
                'reason' => __('A generic biodegradability claim is banned unless you can show recognised excellent environmental performance relevant to it, or the claim is specified in clear and prominent terms on the same medium (recital 9 of Directive (EU) 2024/825).', 'garantio'),
            ],
            [
                'term' => 'climate positive / net zero',
                'pattern' => '/\b(climate[- ]positive|carbon[- ]positive|net[- ]zero|pozytywn\w* klimatycznie|klimapositiv)/iu',
                'legal_basis' => 'Directive 2005/29/EC Annex I point 4c (Directive (EU) 2024/825)',
                'reason' => __('Banned when the claim is based on offsetting emissions. Point 4c does not cover a claim based on the product\'s actual lifecycle impact (recital 12 of Directive (EU) 2024/825).', 'garantio'),
            ],
        ];
    }
}
