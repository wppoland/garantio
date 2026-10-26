<?php
/**
 * Self-check for Garantio statutory guarantee notices and green claims auditor.
 * Run: php tests/guarantee-notice-check.php
 *
 * @package Garantio
 */

declare(strict_types=1);

define('ABSPATH', __DIR__);
define('GARANTIO_DIR', __DIR__ . '/../');

require_once __DIR__ . '/../autoload.php';

// Minimal stubs for WordPress and WooCommerce functions used in testing.
function __(string $text, string $domain = 'default'): string
{
    return $text;
}

function esc_html__(string $text, string $domain = 'default'): string
{
    return $text;
}

function determine_locale(): string
{
    return 'en_US';
}

function add_action(string $tag, $callback, int $priority = 10, int $accepted_args = 1): void
{
}

function add_filter(string $tag, $callback, int $priority = 10, int $accepted_args = 1): void
{
}

$GLOBALS['options'] = [];

function get_option(string $option, $default = false)
{
    return $GLOBALS['options'][$option] ?? $default;
}

// ------------------- TEST SUITE -------------------
echo "Running Garantio Guarantee & Green Claims Tests...\n";

// 1. Test Harmonised Notice Templates
$notices = \Garantio\Model\GuaranteeNotice::defaultNotices();
assert(isset($notices['en']), 'English notice exists');
assert(isset($notices['pl']), 'Polish notice exists');
assert(isset($notices['de']), 'German notice exists');
assert(isset($notices['es']), 'Spanish notice exists');
assert(isset($notices['ro']), 'Romanian notice exists');

$enNotice = sprintf($notices['en'], 2);
assert(str_contains($enNotice, 'minimum 2-year legal guarantee of conformity'), 'English notice text correct');

echo "  ok  Harmonised Notice multilingual templates verified\n";

// 2. Test Commercial Durability Label
$durabilityLabels = \Garantio\Model\GuaranteeNotice::durabilityLabels();
$label = sprintf($durabilityLabels['en'], 'Acme Corp', 5);
assert(str_contains($label, 'Acme Corp offers a 5-year commercial guarantee of durability'), 'Durability label text correct');

echo "  ok  Durability label template verified\n";

// 3. Test Green Claims Auditor
$auditor = new \Garantio\Service\GreenClaimsAuditor();

// Risky texts
$risky1 = 'This product is 100% climate-neutral thanks to our carbon offsetting program.';
$violations1 = $auditor->auditText($risky1);
assert(count($violations1) === 1, 'Auditor catches climate-neutral claim');
assert($violations1[0]['matched'] === 'climate-neutral', 'Matched string is climate-neutral');

$risky2 = 'An eco-friendly solution for everyday life, perfectly biodegradable.';
$violations2 = $auditor->auditText($risky2);
assert(count($violations2) === 2, 'Auditor catches multiple green claims (eco-friendly and biodegradable)');

// Clean text
$clean = 'High durability stainless steel bottle with silicone seal. Hand wash recommended.';
$violationsClean = $auditor->auditText($clean);
assert(count($violationsClean) === 0, 'Clean text has zero violations');

echo "  ok  Green Claims Auditor successfully detects banned generic and offset claims\n";

echo "\nALL GARANTIO CHECKS PASSED!\n";
exit(0);
