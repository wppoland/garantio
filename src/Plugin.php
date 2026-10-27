<?php

declare(strict_types=1);

namespace Garantio;

use Garantio\Admin\ProductMetaBox;
use Garantio\Admin\SettingsPage;
use Garantio\Service\GuaranteeService;

defined('ABSPATH') || exit;

/**
 * Main plugin coordinator for Garantio.
 */
final class Plugin
{
    private static ?self $instance = null;

    private GuaranteeService $guaranteeService;
    private ProductMetaBox $productMetaBox;
    private SettingsPage $settingsPage;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        $this->guaranteeService = new GuaranteeService();
        $this->productMetaBox   = new ProductMetaBox($this->guaranteeService);
        $this->settingsPage     = new SettingsPage();
    }

    public function boot(): void
    {
        $this->guaranteeService->registerHooks();
        $this->productMetaBox->registerHooks();
        $this->settingsPage->registerHooks();
    }

    public function getGuaranteeService(): GuaranteeService
    {
        return $this->guaranteeService;
    }
}
