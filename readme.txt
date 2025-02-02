=== Website Carbon Calculator ===

Plugin Name: Website Carbon Calculator
Description: Calculate the CO₂ impact of your web pages in real time, without API delays, directly from your WordPress admin panel.
Contributors: akhelastudio
Donate link: https://buymeacoffee.com/akhela
Author: Akhela
Author URI: https://www.akhela.fr
Tags: carbon, emissions, measure, sustainability, performance
Requires at least: 5.8
Tested up to: 6.7
Stable tag: 1.2.2
Requires PHP: 7.1
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Effortlessly calculate any page’s CO₂ impact in WordPress, with real-time results and no reliance on the Website Carbon API, ensuring instant updates.

== Description ==

The internet consumes a lot of electricity. 1021 TWh per year to be precise. To give you some perspective, that’s more than the entire United Kingdom.

From data centres to transmission networks to the billions of connected devices that we hold in our hands, it is all consuming electricity, and in turn producing carbon emissions equal to or greater than the global aviation industry. Yikes!

The WordPress Carbon Calculator empowers website owners to assess and minimize their carbon footprint. Drawing inspiration from the acclaimed Website Carbon Calculator algorithm 2.0 and leveraging The Green Web Foundation's co2.js, this user-friendly tool allows you to calculate the CO₂ impact of any page on your website directly from your WordPress admin panel.

You can choose to activate it on selected post types and taxonomies, with full support for custom post types and custom taxonomies.

**Key Features:**

* **Precision Carbon Calculations:** Built upon the Website Carbon Calculator algorithm 2.0 and powered by The Green Web Foundation's co2.js, delivering accurate CO₂ impact calculations with the latest environmental data.
* **Intuitive Interface:** Offers a user-friendly interface within your WordPress admin area, simplifying access to environmental data.
* **Front-End Presentation:** Displays the computed CO₂ impact on your website's front end, engaging and educating your visitors.
* **Data from Google Page Speed:** Utilizes Google Page Speed to gather loaded data, providing comprehensive performance metrics for informed decisions.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/website-carbon-calculator` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Navigate to the plugin settings to configure and start analyzing your website's carbon footprint.

== Frequently Asked Questions ==

= How does the plugin calculate the CO₂ impact? =

The plugin uses the Website Carbon Calculator algorithm 2.0 and The Green Web Foundation's co2.js to provide accurate CO₂ impact calculations based on the latest environmental data.

= Can I display the CO₂ impact on my website's front end? =

Yes, the plugin allows you to showcase the computed CO₂ impact prominently on your website's front end to engage and educate your visitors.

You can use the following PHP snippet on any page to display the CO₂ impact:

`<?php if($calculated_carbon = get_calculated_carbon() ): ?>
This page emits <?=round($calculated_carbon,2)?>g eq. CO₂
<?php endif; ?>`

This will output the calculated CO₂ emissions in grams.

== Screenshots ==

1. Minimal Impact – This page has mostly text, resulting in a low CO₂ footprint.
2. Higher Impact – Adding a YouTube video increases the page’s CO₂ emissions.
3. Instant Insights – See a page’s CO₂ impact with green, orange, or red flags.
4. Advanced Tools – Compute emissions for special pages with dedicated tools.
5. Customizable Settings – Fine-tune calculations for accurate CO₂ impact analysis.

== Changelog ==

= 1.2.2 =
* Better readme.txt and screenshots

= 1.2.1 =
* Initial release of the WordPress Carbon Calculator plugin on Wordpress.

= 1.0.0 =
* Initial release of the WordPress Carbon Calculator plugin.

== Roadmap ==

* Translations
* Display Google page speed performance score
* Ecoindex.fr algorithm support
* Better settings/tools interface design
