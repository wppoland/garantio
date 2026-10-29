=== Garantio - EU Legal Guarantee Notice for WooCommerce ===
Contributors: motylanogha
Tags: woocommerce, guarantee, legal guarantee, repairability, green claims
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Legal guarantee reminder, commercial durability guarantee note, repairability score badge and a green claims phrase check for WooCommerce product pages.

== Description ==

Directive (EU) 2024/825 (Empowering Consumers for the Green Transition) amends Directive 2011/83/EU. Before a distance contract, the trader must remind the consumer of the legal guarantee of conformity, including its minimum duration of two years (Article 6(1)(l)), and, where the producer offers a commercial guarantee of durability at no additional cost covering the entire good for more than two years and tells the trader, say that the good benefits from it and for how long (Article 6(1)(la)). Member States apply these rules from 27 September 2026 (Article 4(1) of Directive (EU) 2024/825); in Poland the transposing act (Dz.U. 2026 poz. 1353) applies from 27 September 2027.

Directive (EU) 2024/825 also adds to Annex I of Directive 2005/29/EC a ban on generic environmental claims the trader cannot back with recognised excellent environmental performance (point 4a; recital 9 gives "eco-friendly", "green" and "biodegradable" as examples) and on claims that a product has a neutral, reduced or positive greenhouse gas impact based on offsetting (point 4c).

Garantio adds the following to WooCommerce:

* **Legal guarantee reminder**: Shows a text reminder of the legal guarantee of conformity (at least two years) on product pages. It is plain text, not the harmonised notice whose design and content the Commission sets by implementing act (Article 22a of Directive 2011/83/EU).
* **Durability guarantee note**: Shows a text note when you enter a producer commercial durability guarantee longer than two years. It is not the harmonised label of Article 22a.
* **Repairability score**: Shows a repairability score you enter (1.0 to 10.0) as a colour-coded badge.
* **Green claims check**: Scans product titles, descriptions and short descriptions in the admin and flags phrases such as "climate neutral" or "eco-friendly" for you to review.
* **Flexible Display Options**: Position notices under the single product summary (near the price and add-to-cart button) or inside a dedicated "Guarantee & Durability" product tab.
* **Digital Goods Exemption**: Easily exempt digital downloads, virtual items, and services from statutory goods guarantee notices.
* **Full WooCommerce Compatibility**: Compatible with WooCommerce High-Performance Order Storage (HPOS) and Cart and Checkout Blocks. Zero external server calls, zero tracking, and zero monthly fees.

= Documentation and links =

* **Documentation** - [plogins.com/garantio/docs/](https://plogins.com/garantio/docs/)
* **Plugin page** - [plogins.com/garantio/](https://plogins.com/garantio/)
* **Source code** - [github.com/wppoland/garantio](https://github.com/wppoland/garantio)
* **Bug reports and feature requests** - [GitHub issues](https://github.com/wppoland/garantio/issues)

Reporting a security issue: email hello@wppoland.com under our coordinated disclosure policy. We confirm within two business days, assess within five, and patch a critical issue within seven days of confirming it.

== Translations ==

Garantio is fully translatable and ships the `garantio.pot` template. Translations are delivered by WordPress.org language packs from translate.wordpress.org.

== Installation ==

1. Ensure WooCommerce 8.0 or higher is installed and active.
2. Install Garantio and activate it.
3. Open WooCommerce > EU Legal Guarantee to configure notice placement and statutory defaults.
4. Edit products to add producer durability guarantee years (e.g. 3, 5, 10 years), repairability scores, or review green claims auditor alerts.

== Frequently Asked Questions ==

= When does Directive (EU) 2024/825 take effect? =
Member States had to adopt and publish the transposing measures by 27 March 2026 and apply them from 27 September 2026 (Article 4(1)). The rules bind shops through each country's transposing law: in Poland that act (Dz.U. 2026 poz. 1353) applies from 27 September 2027.

= What is the harmonised notice? =
Article 6(1)(l) of Directive 2011/83/EU (as amended by Directive (EU) 2024/825) requires a reminder of the legal guarantee of conformity, including its minimum duration of two years, "using the harmonised notice referred to in Article 22a". Article 22a leaves the design and content of that notice to a Commission implementing act. Garantio shows a text reminder, not that notice.

= What is the harmonised label? =
Article 6(1)(la) of Directive 2011/83/EU requires the harmonised label of Article 22a where the producer offers a commercial guarantee of durability at no additional cost, covering the entire good and lasting more than two years, and makes that known to the trader. Its design and content are also set by a Commission implementing act. Garantio shows a text note, not that label.

= Does Garantio protect against greenwashing penalties? =
No. The green claims check matches product text against a short list of phrases linked to points 4a and 4c of Annex I to Directive 2005/29/EC (as amended by Directive (EU) 2024/825) and warns you in the product editor. Whether a claim is allowed depends on what you can show for it.

== Changelog ==

= 1.0.2 =
* Changed: display name is now "Garantio - EU Legal Guarantee Notice for WooCommerce". The old name suggested the plugin shows the harmonised notice of Article 22a of Directive 2011/83/EU; it shows its own text reminder.

= 1.0.1 =
* Fixed: legal references. The guarantee reminder is Article 6(1)(l) of Directive 2011/83/EU and the durability note Article 6(1)(la), not Article 6(1)(ea) or 22a(2); offsetting claims are point 4c of Annex I to Directive 2005/29/EC, not 4b. The readme now says the 2024/825 rules apply from 27 September 2026 in Member States and from 27 September 2027 in Poland, and that the plugin shows text, not the harmonised notice or label.

= 1.0.0 =
* Initial release: Statutory EU Harmonised Guarantee Notice, commercial durability labels, repairability score display, and green claims compliance auditor.
