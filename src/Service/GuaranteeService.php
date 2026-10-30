<?php

declare(strict_types=1);

namespace Garantio\Service;

use Garantio\Contract\HasHooks;
use Garantio\Model\GuaranteeNotice;

defined('ABSPATH') || exit;

/**
 * Renders the legal guarantee reminder, durability guarantee note and repairability score on product pages.
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
        $this->settings = self::settings();
    }

    /**
     * Defaults apply until the merchant first saves the settings page.
     *
     * @return array<string, string>
     */
    public static function defaults(): array
    {
        return [
            'enabled'         => '1',
            'statutory_years' => (string) GuaranteeNotice::DEFAULT_STATUTORY_YEARS,
            'notice_position' => 'single_summary',
            'custom_notice'   => '',
        ];
    }

    /**
     * Stored settings over the defaults.
     *
     * @return array<string, mixed>
     */
    public static function settings(): array
    {
        $raw = get_option(self::OPTION, []);

        return array_merge(self::defaults(), is_array($raw) ? $raw : []);
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
            add_action('woocommerce_single_product_summary', [$this, 'renderGuaranteeReminder'], 25);
            add_action('woocommerce_single_product_summary', [$this, 'renderDurabilityLabel'], 26);
            add_action('woocommerce_single_product_summary', [$this, 'renderRepairabilityScore'], 27);
        } else {
            add_filter('woocommerce_product_tabs', [$this, 'registerGuaranteeTab']);
        }
    }

    /**
     * Render the legal guarantee reminder (Directive 2011/83/EU art. 6(1)(l)); plain text, not the harmonised notice of Implementing Regulation (EU) 2025/1960.
     */
    public function renderGuaranteeReminder(): void
    {
        global $product;
        if (! $product instanceof \WC_Product || ! $this->showsReminder($product)) {
            return;
        }

        $custom = trim((string) $this->settings['custom_notice']);
        $text   = $custom !== '' ? $custom : GuaranteeNotice::reminder((int) $this->settings['statutory_years']);

        echo '<div class="garantio-guarantee-reminder" style="margin: 12px 0; padding: 10px 14px; background: #f0fdf4; border-left: 4px solid #16a34a; border-radius: 2px; font-size: 0.9em; color: #166534;">';
        echo '<strong style="display:block; margin-bottom: 2px;">' . esc_html__('EU Statutory Legal Guarantee', 'garantio') . '</strong>';
        echo '<p style="margin:0;">' . esc_html($text) . '</p>';
        echo '</div>';
    }

    /**
     * Render the commercial durability guarantee note over 2 years (Directive 2011/83/EU art. 6(1)(la)); plain text, not the art. 22a harmonised label.
     */
    public function renderDurabilityLabel(): void
    {
        global $product;
        if (! $product instanceof \WC_Product) {
            return;
        }

        $years = self::durabilityYears($product);
        if ($years === 0) {
            return;
        }

        $producer = trim((string) $product->get_meta(self::META_DURABILITY_PRODUCER, true));
        $producer = $producer !== '' ? $producer : __('The manufacturer', 'garantio');

        $labelText = GuaranteeNotice::durabilityNote($producer, $years);

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

        $score = self::repairScore($product);
        if ($score === null) {
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
        global $product;
        if (! $product instanceof \WC_Product || ! $this->hasContent($product)) {
            return $tabs;
        }

        $tabs['garantio_guarantee'] = [
            'title' => __('Guarantee & Durability', 'garantio'),
            'priority' => 35,
            'callback' => [$this, 'renderTabContent'],
        ];

        return $tabs;
    }

    public function renderTabContent(): void
    {
        $this->renderGuaranteeReminder();
        $this->renderDurabilityLabel();
        $this->renderRepairabilityScore();
    }

    private function showsReminder(\WC_Product $product): bool
    {
        return $product->get_meta(self::META_EXEMPT_GUARANTEE, true) !== 'yes';
    }

    /**
     * Durability guarantee years worth showing: more than 2 (art. 6(1)(la)), else 0.
     */
    private static function durabilityYears(\WC_Product $product): int
    {
        $years = (int) $product->get_meta(self::META_DURABILITY_YEARS, true);

        return $years > 2 ? $years : 0;
    }

    private static function repairScore(\WC_Product $product): ?float
    {
        $raw = $product->get_meta(self::META_REPAIR_SCORE, true);
        if ($raw === '' || ! is_numeric($raw)) {
            return null;
        }

        $score = round((float) $raw, 1);

        return $score > 0.0 ? $score : null;
    }

    private function hasContent(\WC_Product $product): bool
    {
        return $this->showsReminder($product)
            || self::durabilityYears($product) > 0
            || self::repairScore($product) !== null;
    }
}
