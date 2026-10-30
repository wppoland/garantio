<?php

declare(strict_types=1);

namespace Garantio\Admin;

use Garantio\Contract\HasHooks;
use Garantio\Service\GreenClaimsAuditor;
use Garantio\Service\GuaranteeService;

defined('ABSPATH') || exit;

/**
 * Product edit screen fields and Green Claims Auditor notifications.
 */
final class ProductMetaBox implements HasHooks
{
    private GreenClaimsAuditor $auditor;

    public function __construct()
    {
        $this->auditor = new GreenClaimsAuditor();
    }

    public function registerHooks(): void
    {
        add_action('woocommerce_product_options_general_product_data', [$this, 'renderProductFields']);
        add_action('woocommerce_process_product_meta', [$this, 'saveProductFields']);
        add_action('add_meta_boxes', [$this, 'registerGreenClaimsBox']);
    }

    public function registerGreenClaimsBox(): void
    {
        add_meta_box(
            'garantio_green_claims',
            __('Green Claims Phrase Check', 'garantio'),
            [$this, 'renderGreenClaimsBox'],
            'product',
            'side',
            'high'
        );
    }

    public function renderGreenClaimsBox(\WP_Post $post): void
    {
        $product = wc_get_product($post->ID);
        if (! $product instanceof \WC_Product) {
            return;
        }

        $violations = $this->auditor->auditProduct($product);

        if (empty($violations)) {
            echo '<p style="margin:0;">';
            echo esc_html__('None of the checked phrases found. This check looks for a short list of phrases only and does not assess other environmental claims.', 'garantio');
            echo '</p>';
            return;
        }

        echo '<div style="background:#fef2f2; border-left:4px solid #dc2626; padding:8px 10px; margin-bottom:8px;">';
        echo '<strong style="color:#991b1b;">' . esc_html__('Phrases to review:', 'garantio') . '</strong>';
        echo '<ul style="margin:6px 0 0 16px; padding:0; list-style:disc; font-size:12px; color:#7f1d1d;">';
        foreach ($violations as $v) {
            echo '<li>';
            echo '<strong>"' . esc_html($v['matched']) . '"</strong>: ' . esc_html($v['reason']);
            echo ' <em>(' . esc_html($v['legal_basis']) . ')</em>';
            echo '</li>';
        }
        echo '</ul>';
        echo '<p style="margin:6px 0 0; font-size:12px;">' . esc_html__('A phrase is not banned in itself. It is allowed when you can show recognised excellent environmental performance, or when the claim is specified in clear terms on the same page (recital 9 of Directive (EU) 2024/825). Climate claims are banned when based on offsetting (point 4c).', 'garantio') . '</p>';
        echo '</div>';
    }

    public function renderProductFields(): void
    {
        wp_nonce_field('garantio_save_product_fields', 'garantio_product_nonce');

        echo '<div class="options_group garantio-options-group">';
        echo '<h4 style="padding-left:12px;">' . esc_html__('EU Legal Guarantee & Durability (Article 6(1)(l), (la) and (u) of Directive 2011/83/EU)', 'garantio') . '</h4>';

        woocommerce_wp_checkbox([
            'id' => GuaranteeService::META_EXEMPT_GUARANTEE,
            'label' => __('Hide guarantee reminder', 'garantio'),
            'description' => __('Hide the goods legal guarantee reminder on this product, for example a digital download or a service. Article 6(1)(lb) of Directive 2011/83/EU requires a separate reminder for digital content and digital services, which Garantio does not show.', 'garantio'),
        ]);

        woocommerce_wp_text_input([
            'id' => GuaranteeService::META_DURABILITY_YEARS,
            'label' => __('Commercial Durability (Years)', 'garantio'),
            'description' => __('Only if the producer offers a commercial guarantee of durability at no extra cost for more than 2 years (e.g. 3, 5, 10). Values of 2 or less are not saved.', 'garantio'),
            'desc_tip' => true,
            'type' => 'number',
            'custom_attributes' => ['min' => '3', 'step' => '1'],
        ]);

        woocommerce_wp_text_input([
            'id' => GuaranteeService::META_DURABILITY_PRODUCER,
            'label' => __('Producer / Brand Name', 'garantio'),
            'description' => __('Name of the manufacturer offering the commercial durability guarantee.', 'garantio'),
            'desc_tip' => true,
        ]);

        woocommerce_wp_text_input([
            'id' => GuaranteeService::META_REPAIR_SCORE,
            'label' => __('Repairability Score (1.0 - 10.0)', 'garantio'),
            'description' => __('The reparability score as provided by the producer, where one is established at Union level (Article 6(1)(u) of Directive 2011/83/EU). Garantio shows the number as a badge, not in any official label format.', 'garantio'),
            'desc_tip' => true,
            'type' => 'number',
            'custom_attributes' => ['min' => '1.0', 'max' => '10.0', 'step' => '0.1'],
        ]);

        echo '</div>';
    }

    public function saveProductFields(int $productId): void
    {
        if (
            ! isset($_POST['garantio_product_nonce']) ||
            ! wp_verify_nonce(sanitize_key(wp_unslash($_POST['garantio_product_nonce'])), 'garantio_save_product_fields')
        ) {
            return;
        }

        if (! current_user_can('edit_post', $productId)) {
            return;
        }

        $product = wc_get_product($productId);
        if (! $product instanceof \WC_Product) {
            return;
        }

        $exempt   = isset($_POST[GuaranteeService::META_EXEMPT_GUARANTEE]) ? 'yes' : 'no';
        $years    = isset($_POST[GuaranteeService::META_DURABILITY_YEARS]) ? absint(wp_unslash($_POST[GuaranteeService::META_DURABILITY_YEARS])) : 0;
        $producer = isset($_POST[GuaranteeService::META_DURABILITY_PRODUCER]) ? sanitize_text_field(wp_unslash($_POST[GuaranteeService::META_DURABILITY_PRODUCER])) : '';
        $repair   = isset($_POST[GuaranteeService::META_REPAIR_SCORE]) ? self::parseScore(sanitize_text_field(wp_unslash($_POST[GuaranteeService::META_REPAIR_SCORE]))) : '';

        $product->update_meta_data(GuaranteeService::META_EXEMPT_GUARANTEE, $exempt);
        $product->update_meta_data(GuaranteeService::META_DURABILITY_YEARS, $years > 2 ? $years : '');
        $product->update_meta_data(GuaranteeService::META_DURABILITY_PRODUCER, $producer);
        $product->update_meta_data(GuaranteeService::META_REPAIR_SCORE, $repair);
        $product->save_meta_data();
    }

    /**
     * Accepts a decimal comma ("7,5"); blank or out of the 1 to 10 range stores nothing.
     */
    public static function parseScore(string $raw): string
    {
        $raw = trim(str_replace(',', '.', $raw));
        if (! is_numeric($raw)) {
            return '';
        }

        $score = round((float) $raw, 1);

        return ($score >= 1.0 && $score <= 10.0) ? (string) $score : '';
    }
}
