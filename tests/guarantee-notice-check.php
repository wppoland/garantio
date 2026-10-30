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
function _n(string $single, string $plural, int $number, string $domain = 'default'): string
{
    return $number === 1 ? $single : $plural;
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
function woocommerce_wp_select(array $args): void
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
    public string $type = 'simple';
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
    public function is_type($type): bool
    {
        return in_array($this->type, (array) $type, true);
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
    $parent->type = 'variable';
    $child  = new WC_Product(11);
    $child->description = 'An eco-friendly variant.';
    $parent->children   = [11];
    return count($auditor->auditProduct($parent)) === 1;
});
check('green claims: a grouped product does not scan the products it groups', static function () use ($auditor): bool {
    reset_state();
    $parent = new WC_Product(13);
    $parent->type = 'grouped';
    $child = new WC_Product(14);
    $child->description = 'An eco-friendly bottle sold separately.';
    $parent->children = [14];
    return $auditor->auditProduct($parent) === [];
});
$variable = static function (int $flagged, int $count = 150): WC_Product {
    reset_state();
    $parent = new WC_Product(1000);
    $parent->type = 'variable';
    for ($i = 1; $i <= $count; $i++) {
        $child = new WC_Product(1000 + $i);
        if ($i === $flagged) {
            $child->description = 'An eco-friendly variant.';
        }
        $parent->children[] = 1000 + $i;
    }
    return $parent;
};
check('green claims: variation 50 of 150 is checked', static fn (): bool => count($auditor->auditProduct($variable(50))) === 1);
check('green claims: variations past the first 100 are not loaded', static fn (): bool => $auditor->auditProduct($variable(150)) === []);
check('green claims box: says when only the first 100 variations were checked', static function () use ($variable): bool {
    $variable(0);
    $post     = new WP_Post();
    $post->ID = 1000;
    $out      = render(static fn () => (new ProductMetaBox(new GuaranteeService()))->renderGreenClaimsBox($post));
    return str_contains($out, 'Only the first 100 of 150 variations were checked.');
});
check('green claims box: no cap note under 100 variations', static function () use ($variable): bool {
    $variable(0, 20);
    $post     = new WP_Post();
    $post->ID = 1000;
    $out      = render(static fn () => (new ProductMetaBox(new GuaranteeService()))->renderGreenClaimsBox($post));
    return ! str_contains($out, 'variations were checked');
});
check('recital 9: every paraphrase says "clear and prominent terms on the same medium"', static function (): bool {
    foreach (['src/Admin/ProductMetaBox.php', 'src/Model/GreenClaimsRules.php', 'readme.txt'] as $f) {
        $src = (string) file_get_contents(__DIR__ . '/../' . $f);
        if (! str_contains($src, 'clear and prominent terms on the same medium') || preg_match('/clear terms|same page/', $src)) {
            return false;
        }
    }
    return true;
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
check('save: repairability class "b" is stored as "B"', static fn (): bool => $save(['_garantio_repair_score' => 'b'])->meta['_garantio_repair_score'] === 'B');
check('save: a number such as "8.2" is not stored as a repairability class', static fn (): bool => $save(['_garantio_repair_score' => '8.2'])->meta['_garantio_repair_score'] === '');
check('save: "F" is not a repairability class', static fn (): bool => $save(['_garantio_repair_score' => 'F'])->meta['_garantio_repair_score'] === '');
check('fields: repairability is a select of classes A to E; durability minimum is 3', static function (): bool {
    reset_state();
    render(static fn () => (new ProductMetaBox(new GuaranteeService()))->renderProductFields());
    $options = $GLOBALS['fields']['_garantio_repair_score']['options'] ?? [];
    return array_keys($options) === ['', 'A', 'B', 'C', 'D', 'E']
        && ! isset($GLOBALS['fields']['_garantio_repair_score']['type'])
        && ($GLOBALS['fields']['_garantio_durability_years']['custom_attributes']['min'] ?? '') === '3';
});
check('storefront: class B renders as a repairability class, not a score out of 10', static function (): bool {
    reset_state();
    $p = new WC_Product();
    $p->meta['_garantio_repair_score'] = 'B';
    $GLOBALS['product'] = $p;
    $out = render(static fn () => (new GuaranteeService())->renderRepairabilityScore());
    return str_contains($out, 'Repairability class: B') && str_contains($out, '2023/1669') && ! str_contains($out, '/ 10');
});
check('storefront: a number stored by 1.0.x is not shown', static function (): bool {
    reset_state();
    $p = new WC_Product();
    $p->meta['_garantio_repair_score'] = '8.2';
    $GLOBALS['product'] = $p;
    return render(static fn () => (new GuaranteeService())->renderRepairabilityScore()) === '';
});
check('changelog: no decimal comma claim for a type=number field', static function (): bool {
    return ! str_contains((string) file_get_contents(__DIR__ . '/../readme.txt'), 'decimal comma');
});
check('repairability: field, heading and FAQ do not tie the 2023/1669 class to Article 6(1)(u); they name the Art. 1 exclusions', static function (): bool {
    reset_state();
    $html = render(static fn () => (new ProductMetaBox(new GuaranteeService()))->renderProductFields());
    $desc = (string) ($GLOBALS['fields']['_garantio_repair_score']['description'] ?? '');
    $readme = (string) file_get_contents(__DIR__ . '/../readme.txt');
    $faq = substr($readme, (int) strpos($readme, '= Which repairability value'), 900);
    foreach ([$desc, $faq] as $text) {
        if (str_contains($text, '6(1)(u)') || ! str_contains($text, 'high security') || ! str_contains($text, 'roll')) {
            return false;
        }
    }
    return ! str_contains($html, '(u)');
});
check('description: plugin header and composer.json say "repairability class", not "score"', static function (): bool {
    $header = (string) file_get_contents(__DIR__ . '/../garantio.php');
    $composer = (string) file_get_contents(__DIR__ . '/../composer.json');
    return str_contains($header, 'repairability class badge') && str_contains($composer, 'repairability class badge')
        && ! str_contains($header . $composer, 'repairability score');
});
check('blueprint: the demo product with a repairability class is a smartphone or slate tablet', static function (): bool {
    $bp = (string) file_get_contents(__DIR__ . '/../.wordpress-org/blueprints/blueprint.json');
    return ! str_contains($bp, '_garantio_repair_score') || (bool) preg_match('/set_name\(\'[^\']*(Smartphone|Tablet)/i', $bp);
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

echo $failed === 0 ? "\nALL GARANTIO CHECKS PASSED\n" : "\n{$failed} CHECK(S) FAILED\n";
exit($failed === 0 ? 0 : 1);
