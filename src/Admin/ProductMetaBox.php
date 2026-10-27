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

    public function __construct(private GuaranteeService $guaranteeService)
    {
        $this->auditor = new GreenClaimsAuditor();
    }

    public function registerHooks(): void
    {
        if (! $this->guaranteeService->isEnabled()) {
            return;
        }

        add_action('woocommerce_product_options_general_product_data', [$this, 'renderProductFields']);
        add_action('woocommerce_process_product_meta', [$this, 'saveProductFields']);
        add_action('add_meta_boxes', [$this, 'registerGreenClaimsBox']);
    }

    public function registerGreenClaimsBox(): void
    {
        add_meta_box(
            'garantio_green_claims',
            __('Green Claims Compliance Check (Directive (EU) 2024/825)', 'garantio'),
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
            echo '<p style="color:#16a34a; margin:0;">';
            echo '&#10004; ' . esc_html__('No prohibited generic green claims detected.', 'garantio');
            echo '</p>';
            return;
        }

        echo '<div style="background:#fef2f2; border-left:4px solid #dc2626; padding:8px 10px; margin-bottom:8px;">';
        echo '<strong style="color:#991b1b;">' . esc_html__('Risky Green Claims Detected:', 'garantio') . '</strong>';
        echo '<ul style="margin:6px 0 0 16px; padding:0; list-style:disc; font-size:12px; color:#7f1d1d;">';
        foreach ($violations as $v) {
            echo '<li>';
            echo '<strong>"' . esc_html($v['matched']) . '"</strong>: ' . esc_html($v['reason']);
            echo ' <em>(' . esc_html($v['legal_basis']) . ')</em>';
            echo '</li>';
        }
        echo '</ul>';
        echo '</div>';
    }

    public function renderProductFields(): void
    {
        wp_nonce_field('garantio_save_product_fields', 'garantio_product_nonce');

        echo '<div class="options_group garantio-options-group">';
        echo '<h4 style="padding-left:12px;">' . esc_html__('EU Legal Guarantee & Durability (Directive 2024/825)', 'garantio') . '</h4>';

        woocommerce_wp_checkbox([
            'id' => GuaranteeService::META_EXEMPT_GUARANTEE,
            'label' => __('Exempt Product', 'garantio'),
            'description' => __('Exempt digital downloads, services, or non-goods from statutory guarantee notice.', 'garantio'),
        ]);

        woocommerce_wp_text_input([
            'id' => GuaranteeService::META_DURABILITY_YEARS,
            'label' => __('Commercial Durability (Years)', 'garantio'),
            'description' => __('If the producer provides a commercial durability guarantee at no extra cost exceeding 2 years (e.g. 3, 5, 10).', 'garantio'),
            'desc_tip' => true,
            'type' => 'number',
            'custom_attributes' => ['min' => '0', 'step' => '1'],
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
            'description' => __('Official repairability index or score where available under EU or national rules.', 'garantio'),
            'desc_tip' => true,
            'type' => 'number',
            'custom_attributes' => ['min' => '0.0', 'max' => '10.0', 'step' => '0.1'],
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
        $years    = isset($_POST[GuaranteeService::META_DURABILITY_YEARS]) ? max(0, absint(wp_unslash($_POST[GuaranteeService::META_DURABILITY_YEARS]))) : 0;
        $producer = isset($_POST[GuaranteeService::META_DURABILITY_PRODUCER]) ? sanitize_text_field(wp_unslash($_POST[GuaranteeService::META_DURABILITY_PRODUCER])) : '';
        $repair   = isset($_POST[GuaranteeService::META_REPAIR_SCORE]) && '' !== $_POST[GuaranteeService::META_REPAIR_SCORE]
            ? min(10.0, max(0.0, (float) sanitize_text_field(wp_unslash($_POST[GuaranteeService::META_REPAIR_SCORE]))))
            : '';

        $product->update_meta_data(GuaranteeService::META_EXEMPT_GUARANTEE, $exempt);
        $product->update_meta_data(GuaranteeService::META_DURABILITY_YEARS, $years > 0 ? $years : '');
        $product->update_meta_data(GuaranteeService::META_DURABILITY_PRODUCER, $producer);
        $product->update_meta_data(GuaranteeService::META_REPAIR_SCORE, $repair !== '' ? (string) $repair : '');
        $product->save_meta_data();
    }
}
