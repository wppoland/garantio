<?php
/**
 * Self-check for Garantio: settings defaults, reminder text, green claims
 * check, product field saving and the product tab.
 * Run: php tests/guarantee-notice-check.php
 *
 * Each check prints PASS or FAIL; the script exits 1 if any check fails.
 *
 * @package Garantio
 */

declare(strict_types=1);

define('ABSPATH', __DIR__);

require_once __DIR__ . '/../autoload.php';

// ------------------- WordPress / WooCommerce stubs -------------------
$GLOBALS['options'] = [];
$GLOBALS['actions'] = [];
$GLOBALS['locale']  = 'en_US';
$GLOBALS['fields']  = [];
$GLOBALS['products'] = [];

function __(string $text, string $domain = 'default'): string
{
    return $text;
}
function esc_html__(string $text, string $domain = 'default'): string
{
    return $text;
}
function esc_html(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES);
}
function esc_attr(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES);
}
function determine_locale(): string
{
    return $GLOBALS['locale'];
}
function add_action(string $tag, $callback, int $priority = 10, int $accepted_args = 1): void
{
    $GLOBALS['actions'][] = $tag;
}
function add_filter(string $tag, $callback, int $priority = 10, int $accepted_args = 1): void
{
    $GLOBALS['actions'][] = $tag;
}
function get_option(string $option, $default = false)
{
    return $GLOBALS['options'][$option] ?? $default;
}
function sanitize_text_field(string $text): string
{
    return trim(strip_tags($text));
}
function sanitize_key(string $key): string
{
    return $key;
}
function wp_unslash($value)
{
    return $value;
}
function absint($value): int
{
    return abs((int) $value);
}
function wp_verify_nonce(string $nonce, string $action): bool
{
    return true;
}
function current_user_can(string $cap, ...$args): bool
{
    return true;
}
function wp_strip_all_tags(string $text): string
{
    return strip_tags($text);
}
function wc_get_product($id)
{
    return $GLOBALS['products'][$id] ?? null;
}
function wc_get_product_terms(int $id, string $taxonomy, array $args = []): array
{
    return [];
}
function wp_nonce_field(string $action, string $name): void
{
}
function woocommerce_wp_checkbox(array $args): void
{
    $GLOBALS['fields'][$args['id']] = $args;
}
function woocommerce_wp_text_input(array $args): void
{
    $GLOBALS['fields'][$args['id']] = $args;
}

class WP_Post
{
    public int $ID = 0;
}

class WC_Product_Attribute
{
    public function __construct(private array $options = [])
    {
    }
    public function is_taxonomy(): bool
    {
        return false;
    }
    public function get_name(): string
    {
        return 'material';
    }
    public function get_options(): array
    {
        return $this->options;
    }
}

class WC_Product
{
    public array $meta = [];
    public array $children = [];
    public array $attributes = [];
    public string $description = '';
    public function __construct(public int $id = 1)
    {
        $GLOBALS['products'][$id] = $this;
    }
    public function get_id(): int
    {
        return $this->id;
    }
    public function get_meta(string $key, bool $single = true)
    {
        return $this->meta[$key] ?? '';
    }
    public function update_meta_data(string $key, $value): void
    {
        $this->meta[$key] = $value;
    }
    public function save_meta_data(): void
    {
    }
    public function get_name(): string
    {
        return 'Steel bottle';
    }
    public function get_description(): string
    {
        return $this->description;
    }
    public function get_short_description(): string
    {
        return '';
    }
    public function get_children(): array
    {
        return $this->children;
    }
    public function get_attributes(): array
    {
        return $this->attributes;
    }
}

use Garantio\Admin\ProductMetaBox;
use Garantio\Admin\SettingsPage;
use Garantio\Service\GreenClaimsAuditor;
use Garantio\Service\GuaranteeService;

// ------------------- harness -------------------
$failed = 0;
function check(string $name, callable $test): void
{
    global $failed;
    try {
        $ok = (bool) $test();
    } catch (\Throwable $e) {
        $ok = false;
        $name .= ' [' . get_class($e) . ': ' . $e->getMessage() . ']';
    }
    echo ($ok ? 'PASS' : 'FAIL') . "  {$name}\n";
    if (! $ok) {
        $failed++;
    }
}

function render(callable $fn): string
{
    ob_start();
    $fn();
    return (string) ob_get_clean();
}

function reset_state(): void
{
    $GLOBALS['options']  = [];
    $GLOBALS['actions']  = [];
    $GLOBALS['locale']   = 'en_US';
    $GLOBALS['fields']   = [];
    $GLOBALS['products'] = [];
    $GLOBALS['product']  = null;
    $_POST               = [];
}

function reminder_method(GuaranteeService $s): string
{
    return method_exists($s, 'renderGuaranteeReminder') ? 'renderGuaranteeReminder' : 'renderHarmonisedNotice';
}

// 1. Fresh install: no option stored, the reminder is on.
check('fresh install: isEnabled() is true before the first settings save', static function (): bool {
    reset_state();
    return (new GuaranteeService())->isEnabled();
});

// 2. Product fields and green claims box do not depend on the reminder switch.
check('reminder switched off: product fields and green claims box still register', static function (): bool {
    reset_state();
    $GLOBALS['options']['garantio_settings'] = ['enabled' => '0'];
    (new ProductMetaBox(new GuaranteeService()))->registerHooks();
    return in_array('woocommerce_product_options_general_product_data', $GLOBALS['actions'], true)
        && in_array('add_meta_boxes', $GLOBALS['actions'], true);
});

// 3 and 4. One English msgid; no hardcoded per-locale source strings.
check('de_DE: reminder goes through one English msgid, no hardcoded German', static function (): bool {
    reset_state();
    $GLOBALS['locale'] = 'de_DE';
    $GLOBALS['options']['garantio_settings'] = ['enabled' => '1', 'statutory_years' => 2];
    $GLOBALS['product'] = new WC_Product();
    $s   = new GuaranteeService();
    $out = render(static fn () => $s->{reminder_method($s)}());
    return str_contains($out, 'You are entitled by law to a legal guarantee of conformity')
        && ! str_contains($out, 'Gewahrleistung');
});

check('source strings: no Polish, German, Spanish or Romanian msgids in the notice model', static function (): bool {
    $src = (string) file_get_contents(__DIR__ . '/../src/Model/GuaranteeNotice.php');
    return ! preg_match("/'(pl|de|es|ro)' =>/", $src);
});

// 12. Art. 22a(3) of Directive 2011/83/EU: general reference to a longer national period.
check('reminder mentions that national law may provide a longer period', static function (): bool {
    reset_state();
    $GLOBALS['options']['garantio_settings'] = ['enabled' => '1', 'statutory_years' => 2];
    $GLOBALS['product'] = new WC_Product();
    $s   = new GuaranteeService();
    $out = render(static fn () => $s->{reminder_method($s)}());
    return str_contains($out, 'National law may provide a longer period');
});

// 5. Green claims: Unicode, diacritics, inflected forms.
$auditor = new GreenClaimsAuditor();
foreach (
    [
        'przyjazny dla środowiska' => 'Opakowanie przyjazny dla środowiska.',
        'umweltfreundliche'        => 'Umweltfreundliche Verpackung.',
        'klimaneutrale'            => 'Eine klimaneutrale Flasche.',
        'ecológico'                => 'Un envase ecológico.',
    ] as $label => $text
) {
    check("green claims: flags \"{$label}\"", static fn (): bool => count($auditor->auditText($text)) === 1);
}
check('green claims: clean text has no matches', static fn (): bool => $auditor->auditText('Stainless steel bottle with silicone seal.') === []);
check('green claims: "climate-neutral" still matched', static fn (): bool => ($auditor->auditText('A climate-neutral bottle.')[0]['matched'] ?? '') === 'climate-neutral');

// 6. Auditor reads variation descriptions and attributes; no green tick.
check('green claims: variation description is checked', static function () use ($auditor): bool {
    reset_state();
    $parent = new WC_Product(10);
    $child  = new WC_Product(11);
    $child->description = 'An eco-friendly variant.';
    $parent->children   = [11];
    return count($auditor->auditProduct($parent)) === 1;
});
check('green claims: custom attribute values are checked', static function () use ($auditor): bool {
    reset_state();
    $p = new WC_Product(12);
    $p->attributes = [new WC_Product_Attribute(['biodegradable film'])];
    return count($auditor->auditProduct($p)) === 1;
});
check('green claims box: no "No prohibited" all-clear claim when nothing matches', static function (): bool {
    reset_state();
    new WC_Product(20);
    $post     = new WP_Post();
    $post->ID = 20;
    $out      = render(static fn () => (new ProductMetaBox(new GuaranteeService()))->renderGreenClaimsBox($post));
    return $out !== '' && ! str_contains($out, 'No prohibited') && ! str_contains($out, '&#10004;');
});
check('green claims auditor: no strip_tags() (Plugin Check)', static function (): bool {
    return ! str_contains((string) file_get_contents(__DIR__ . '/../src/Service/GreenClaimsAuditor.php'), 'strip_tags(');
});

// 7. Edge input.
check('tab mode: exempt product with nothing to show gets no tab', static function (): bool {
    reset_state();
    $p = new WC_Product();
    $p->meta['_garantio_exempt_guarantee'] = 'yes';
    $GLOBALS['product'] = $p;
    return (new GuaranteeService())->registerGuaranteeTab([]) === [];
});
check('tab mode: product with a reminder still gets the tab', static function (): bool {
    reset_state();
    $GLOBALS['product'] = new WC_Product();
    return isset((new GuaranteeService())->registerGuaranteeTab([])['garantio_guarantee']);
});

$save = static function (array $post): WC_Product {
    reset_state();
    $p = new WC_Product(30);
    $_POST = $post + ['garantio_product_nonce' => 'x'];
    (new ProductMetaBox(new GuaranteeService()))->saveProductFields(30);
    return $p;
};
check('save: durability of 2 years is not stored', static fn (): bool => $save(['_garantio_durability_years' => '2'])->meta['_garantio_durability_years'] === '');
check('save: durability of 5 years is stored', static fn (): bool => $save(['_garantio_durability_years' => '5'])->meta['_garantio_durability_years'] === 5);
check('save: repair score "7,5" is stored as 7.5', static fn (): bool => $save(['_garantio_repair_score' => '7,5'])->meta['_garantio_repair_score'] === '7.5');
check('save: repair score 0 is not stored', static fn (): bool => $save(['_garantio_repair_score' => '0'])->meta['_garantio_repair_score'] === '');
check('fields: repair score minimum is 1.0 and durability minimum is 3', static function (): bool {
    reset_state();
    render(static fn () => (new ProductMetaBox(new GuaranteeService()))->renderProductFields());
    return ($GLOBALS['fields']['_garantio_repair_score']['custom_attributes']['min'] ?? '') === '1.0'
        && ($GLOBALS['fields']['_garantio_durability_years']['custom_attributes']['min'] ?? '') === '3';
});

check('settings: a non-array option value does not fatal', static function (): bool {
    reset_state();
    $out = (new SettingsPage())->sanitizeSettings('garbage');
    return ($out['enabled'] ?? null) === '0';
});
check('settings: statutory years capped at 10', static function (): bool {
    reset_state();
    return (new SettingsPage())->sanitizeSettings(['statutory_years' => '99'])['statutory_years'] === 10;
});

// 8. Leftovers from the "harmonised" naming and unused globals.
check('no "harmonised" method or CSS class on the reminder', static function (): bool {
    $src = (string) file_get_contents(__DIR__ . '/../src/Service/GuaranteeService.php');
    return ! method_exists(GuaranteeService::class, 'renderHarmonisedNotice') && ! str_contains($src, 'garantio-harmonised-notice');
});
check('no unused GARANTIO_DIR / GARANTIO_URL constants', static function (): bool {
    return ! str_contains((string) file_get_contents(__DIR__ . '/../garantio.php'), 'GARANTIO_');
});

// 11. Polish translation uses the statutory term, not "gwarancja".
check('pl_PL: reminder uses "odpowiedzialność przedsiębiorcy za zgodność towaru z umową"', static function (): bool {
    $po = (string) @file_get_contents(__DIR__ . '/../languages/garantio-pl_PL.po');
    return str_contains($po, 'przedsiębiorcy za zgodność towaru z umową') && ! str_contains($po, 'ustawowej gwarancji');
});

echo $failed === 0 ? "\nALL GARANTIO CHECKS PASSED\n" : "\n{$failed} CHECK(S) FAILED\n";
exit($failed === 0 ? 0 : 1);
