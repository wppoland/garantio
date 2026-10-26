<?php

declare(strict_types=1);

namespace Garantio\Service;

use Garantio\Contract\HasHooks;
use Garantio\Model\GuaranteeNotice;

defined('ABSPATH') || exit;

/**
 * Renders statutory Harmonised Notice, Durability Label, and Repairability Score on product pages.
 */
final class GuaranteeService implements HasHooks
{
    public const OPTION = 'garantio_settings';

    public const META_DURABILITY_YEARS  = '_garantio_durability_years';
    public const META_DURABILITY_PRODUCER = '_garantio_durability_producer';
    public const META_REPAIR_SCORE      = '_garantio_repair_score'; // 1.0 to 10.0
    public const META_EXEMPT_GUARANTEE  = '_garantio_exempt_guarantee'; // e.g. digital goods / services

    /**
     * @var array<string, mixed>
     */
    private array $settings;

    public function __construct()
    {
        $raw            = get_option(self::OPTION, []);
        $this->settings = is_array($raw) ? $raw : [];
    }

    public function isEnabled(): bool
    {
        return ! empty($this->settings['enabled']);
    }

    public function registerHooks(): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $position = (string) ($this->settings['notice_position'] ?? 'single_summary');

        if ($position === 'single_summary') {
            add_action('woocommerce_single_product_summary', [$this, 'renderHarmonisedNotice'], 25);
            add_action('woocommerce_single_product_summary', [$this, 'renderDurabilityLabel'], 26);
            add_action('woocommerce_single_product_summary', [$this, 'renderRepairabilityScore'], 27);
        } else {
            add_filter('woocommerce_product_tabs', [$this, 'registerGuaranteeTab']);
        }
    }

    /**
     * Render the statutory EU Harmonised Notice (Directive (EU) 2024/825 art. 22a(1)).
     */
    public function renderHarmonisedNotice(): void
    {
        global $product;
        if (! $product instanceof \WC_Product) {
            return;
        }

        // Exempt digital goods / services if configured
        if ($product->get_meta(self::META_EXEMPT_GUARANTEE, true) === 'yes') {
            return;
        }

        $years    = (int) ($this->settings['statutory_years'] ?? GuaranteeNotice::DEFAULT_STATUTORY_YEARS);
        $locale   = substr(determine_locale(), 0, 2);
        $notices  = GuaranteeNotice::defaultNotices();
        $template = $notices[$locale] ?? $notices['en'];

        $custom = trim((string) ($this->settings['custom_notice'] ?? ''));
        $text   = $custom !== '' ? $custom : sprintf($template, $years);

        echo '<div class="garantio-harmonised-notice" style="margin: 12px 0; padding: 10px 14px; background: #f0fdf4; border-left: 4px solid #16a34a; border-radius: 2px; font-size: 0.9em; color: #166534;">';
        echo '<strong style="display:block; margin-bottom: 2px;">' . esc_html__('EU Statutory Legal Guarantee', 'garantio') . '</strong>';
        echo '<p style="margin:0;">' . esc_html($text) . '</p>';
        echo '</div>';
    }

    /**
     * Render Harmonised Label for commercial durability guarantee exceeding 2 years (art. 22a(2)).
     */
    public function renderDurabilityLabel(): void
    {
        global $product;
        if (! $product instanceof \WC_Product) {
            return;
        }

        $years = (int) $product->get_meta(self::META_DURABILITY_YEARS, true);
        if ($years <= 2) {
            return;
        }

        $producer = trim((string) $product->get_meta(self::META_DURABILITY_PRODUCER, true));
        $producer = $producer !== '' ? $producer : __('The manufacturer', 'garantio');

        $locale    = substr(determine_locale(), 0, 2);
        $labels    = GuaranteeNotice::durabilityLabels();
        $template  = $labels[$locale] ?? $labels['en'];
        $labelText = sprintf($template, $producer, $years);

        echo '<div class="garantio-durability-label" style="margin: 10px 0; padding: 8px 12px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 4px; font-size: 0.88em; color: #1e40af;">';
        echo '<strong>' . esc_html__('Durability Guarantee:', 'garantio') . '</strong> ';
        echo esc_html($labelText);
        echo '</div>';
    }

    /**
     * Render repairability score badge if assigned.
     */
    public function renderRepairabilityScore(): void
    {
        global $product;
        if (! $product instanceof \WC_Product) {
            return;
        }

        $scoreRaw = $product->get_meta(self::META_REPAIR_SCORE, true);
        if ($scoreRaw === '' || ! is_numeric($scoreRaw)) {
            return;
        }

        $score = round((float) $scoreRaw, 1);
        if ($score <= 0.0) {
            return;
        }

        // Color scale: green (>=8.0), yellow (6.0-7.9), orange (4.0-5.9), red (<4.0)
        $badgeColor = match (true) {
            $score >= 8.0 => '#16a34a',
            $score >= 6.0 => '#ca8a04',
            $score >= 4.0 => '#ea580c',
            default => '#dc2626',
        };

        echo '<div class="garantio-repair-score" style="margin: 8px 0; font-size: 0.88em;">';
        echo '<span style="display:inline-block; padding: 2px 8px; border-radius: 3px; font-weight: bold; color: #fff; background: ' . esc_attr($badgeColor) . ';">';
        /* translators: %s: numeric score out of 10 */
        printf(esc_html__('Repairability Index: %s / 10', 'garantio'), esc_html((string) $score));
        echo '</span>';
        echo '</div>';
    }

    /**
     * @param array<string, mixed> $tabs
     * @return array<string, mixed>
     */
    public function registerGuaranteeTab(array $tabs): array
    {
        $tabs['garantio_guarantee'] = [
            'title' => __('Guarantee & Durability', 'garantio'),
            'priority' => 35,
            'callback' => [$this, 'renderTabContent'],
        ];

        return $tabs;
    }

    public function renderTabContent(): void
    {
        $this->renderHarmonisedNotice();
        $this->renderDurabilityLabel();
        $this->renderRepairabilityScore();
    }
}
