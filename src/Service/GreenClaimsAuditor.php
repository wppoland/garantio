<?php

declare(strict_types=1);

namespace Garantio\Service;

use Garantio\Model\GreenClaimsRules;

defined('ABSPATH') || exit;

/**
 * Static auditor for uncertified green claims in product text.
 */
final class GreenClaimsAuditor
{
    /**
     * Audit product text against Directive (EU) 2024/825 green claims rules.
     *
     * @return array<int, array<string, string>>
     */
    public function auditText(string $text): array
    {
        $violations = [];
        $rules      = GreenClaimsRules::bannedPhrases();

        foreach ($rules as $rule) {
            if (preg_match($rule['pattern'], $text, $matches)) {
                $violations[] = [
                    'matched' => (string) ($matches[0] ?? $rule['term']),
                    'legal_basis' => $rule['legal_basis'],
                    'reason' => $rule['reason'],
                ];
            }
        }

        return $violations;
    }

    /**
     * Audit a WooCommerce product object.
     *
     * @return array<int, array<string, string>>
     */
    public function auditProduct(\WC_Product $product): array
    {
        $content = $product->get_name() . ' ' . $product->get_description() . ' ' . $product->get_short_description();

        return $this->auditText(strip_tags($content));
    }
}
