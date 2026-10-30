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
        $parts = [$product->get_name(), $product->get_description(), $product->get_short_description()];

        foreach ($product->get_attributes() as $attribute) {
            if ($attribute instanceof \WC_Product_Attribute) {
                $parts[] = $attribute->is_taxonomy()
                    ? implode(' ', wc_get_product_terms($product->get_id(), $attribute->get_name(), ['fields' => 'names']))
                    : implode(' ', $attribute->get_options());
            }
        }

        foreach ($product->get_children() as $childId) {
            $child = wc_get_product($childId);
            if ($child instanceof \WC_Product) {
                $parts[] = $child->get_description();
            }
        }

        return $this->auditText(wp_strip_all_tags(implode(' ', $parts)));
    }
}
