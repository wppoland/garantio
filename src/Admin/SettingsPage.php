<?php

declare(strict_types=1);

namespace Garantio\Admin;

use Garantio\Contract\HasHooks;
use Garantio\Service\GuaranteeService;

defined('ABSPATH') || exit;

/**
 * Settings admin interface for Garantio.
 */
final class SettingsPage implements HasHooks
{
    public const PAGE_SLUG = 'garantio-settings';

    public function registerHooks(): void
    {
        add_action('admin_menu', [$this, 'registerMenu']);
        add_action('admin_init', [$this, 'registerSettings']);
    }

    public function registerMenu(): void
    {
        add_submenu_page(
            'woocommerce',
            __('Garantio EU Guarantees', 'garantio'),
            __('EU Legal Guarantee', 'garantio'),
            'manage_woocommerce',
            self::PAGE_SLUG,
            [$this, 'renderPage']
        );
    }

    public function registerSettings(): void
    {
        register_setting(self::PAGE_SLUG, GuaranteeService::OPTION, [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitizeSettings'],
            'default' => [
                'enabled' => '1',
                'statutory_years' => '2',
                'notice_position' => 'single_summary',
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function sanitizeSettings(array $input): array
    {
        $sanitized = [];

        $sanitized['enabled'] = ! empty($input['enabled']) ? '1' : '0';
        $sanitized['statutory_years'] = max(2, (int) ($input['statutory_years'] ?? 2));
        $sanitized['notice_position'] = ($input['notice_position'] ?? '') === 'tab' ? 'tab' : 'single_summary';
        $sanitized['custom_notice'] = sanitize_text_field((string) ($input['custom_notice'] ?? ''));

        return $sanitized;
    }

    public function renderPage(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            return;
        }

        $raw      = get_option(GuaranteeService::OPTION, []);
        $settings = is_array($raw) ? $raw : [];
        ?>
        <div class="wrap garantio-settings-wrap">
            <h1><?php echo esc_html__('Garantio - EU Harmonised Guarantee & Durability Notices', 'garantio'); ?></h1>
            <p class="description">
                <?php echo esc_html__('Mandatory consumer information and green claims safety compliance under Directive (EU) 2024/825 and Directive 2011/83/EU.', 'garantio'); ?>
            </p>

            <form method="post" action="options.php">
                <?php settings_fields(self::PAGE_SLUG); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php echo esc_html__('Enable Harmonised Notice', 'garantio'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr(GuaranteeService::OPTION); ?>[enabled]" value="1" <?php checked(! empty($settings['enabled'])); ?> />
                                <?php echo esc_html__('Display the statutory legal guarantee notice on product pages', 'garantio'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Statutory Guarantee Duration', 'garantio'); ?></th>
                        <td>
                            <input type="number" min="2" step="1" name="<?php echo esc_attr(GuaranteeService::OPTION); ?>[statutory_years]" value="<?php echo esc_attr((string) ($settings['statutory_years'] ?? 2)); ?>" />
                            <span><?php echo esc_html__('years (minimum 2 years across EU per Directive (EU) 2019/771 art. 10)', 'garantio'); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Display Location', 'garantio'); ?></th>
                        <td>
                            <label>
                                <input type="radio" name="<?php echo esc_attr(GuaranteeService::OPTION); ?>[notice_position]" value="single_summary" <?php checked(($settings['notice_position'] ?? 'single_summary') === 'single_summary'); ?> />
                                <?php echo esc_html__('Under single product summary (near add to cart)', 'garantio'); ?>
                            </label><br/>
                            <label>
                                <input type="radio" name="<?php echo esc_attr(GuaranteeService::OPTION); ?>[notice_position]" value="tab" <?php checked(($settings['notice_position'] ?? '') === 'tab'); ?> />
                                <?php echo esc_html__('In a dedicated product tab ("Guarantee & Durability")', 'garantio'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Custom Notice Override', 'garantio'); ?></th>
                        <td>
                            <input type="text" class="large-text" name="<?php echo esc_attr(GuaranteeService::OPTION); ?>[custom_notice]" value="<?php echo esc_attr($settings['custom_notice'] ?? ''); ?>" />
                            <p class="description">
                                <?php echo esc_html__('Leave empty to use official localized Harmonised Notice templates.', 'garantio'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
