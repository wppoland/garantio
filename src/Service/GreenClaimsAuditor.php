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
    /** Variation descriptions read per product; a larger catalogue is cut here. */
    public const MAX_VARIATIONS = 100;

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

        foreach (array_slice(self::variationIds($product), 0, self::MAX_VARIATIONS) as $childId) {
            $child = wc_get_product($childId);
            if ($child instanceof \WC_Product) {
                $parts[] = $child->get_description();
            }
        }

        return $this->auditText(wp_strip_all_tags(implode(' ', $parts)));
    }

    /**
     * Variation ids of a variable product; children of a grouped product are other products and are not read.
     *
     * @return array<int, int>
     */
    public static function variationIds(\WC_Product $product): array
    {
        return $product->is_type('variable') ? array_values(array_map('intval', $product->get_children())) : [];
    }
}
