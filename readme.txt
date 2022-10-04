=== FedaPay Gateway for WooCommerce ===
Contributors: fedapay
Tags: credit card, fedapay, mobile money, woocommerce
Requires at least: 4.4
Tested up to: 5.8
Requires PHP: 5.6
Stable tag: 0.3.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Take credit card and mobile money payments on your store using FedaPay.

== Description ==

Accept Visa, MasterCard, Mobile Money directly on your store with the FedaPay payment gateway for WooCommerce.

= Take Credit card and Mobile Money payments easily and directly on your store =

The FedaPay plugin extends WooCommerce allowing you to take payments directly on your store via FedaPay’s API.

FedaPay is available in:

* Benin
* Ivory Coast
* Togo
* Senegal

= Why choose FedaPay? =

FedaPay has no setup fees, no monthly fees, no hidden costs: you only get charged when you earn money! Earnings are transferred to your bank or mobile money account on a 7-day rolling basis.

== Installation ==

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don’t need to leave your web browser. To
do an automatic install of, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type "FedaPay" and click Search Plugins. Once you’ve found our plugin you can view details about it such as the point release, rating and description. Most importantly of course, you can install it by simply clicking "Install Now".

= Manual installation =

The manual installation method involves downloading our plugin and uploading it to your web server via your favorite FTP application. The WordPress codex contains [instructions on how to do this here](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

= Updating =

Automatic updates should work like a charm; as always though, ensure you backup your site just in case.

== Frequently Asked Questions ==

= Does this support recurring payments, like for subscriptions? =

Not yet!

= Does this require an SSL certificate? =

Yes! In live mode, an SSL certificate must be installed on your site to use FedaPay. In addition to SSL encryption.

= Does this support both production mode and sandbox mode for testing? =

Yes it does - production and sandbox mode is driven by the API keys you use.

= Where can I find documentation? =

For help setting up and configuring, please refer to our [user guide](https://docs.fedapay.com/)

= Where can I get support or talk to other users? =

If you get stuck, you can ask for help in the Plugin Forum.

== Screenshots ==

1. The settings panel used to activate the gateway.
2. The settings panel used to configure the gateway.
3. Checkout with FedaPay.
5. Choose payment mode.

== Changelog ==

= 0.3.4 2021-08-26 =
* Enable GNF and EUR currencies

= 0.3.3 2021-08-26 =
* Update icones

= 0.3.2 2021-08-26 =
* Bump version 0.3.2

= 0.3.1 2021-08-26 =
* Security fix

= 0.3.0 2021-07-23 =
* Fix to support recent WordPress and WooCommerce versions

= 0.2.9 2020-12-15 =
* Fix - Include article id and first production name in the translation description

= 0.2.8 2020-12-07 =
* Fix - Test with WC up to 4.7.1
* Fix - Test with WordPress up to 5.5.3
* Fix - Fix WC checkbox issue

= 0.2.4 2020-07-10 =
* Fix - Test with WC up to 4.3.0
* Fix - Test with WordPress up to 5.4.2
* Fix - Prefix callback URL queries to avoid conflicts

= 0.2.3 2020-01-24 =
* Fix - Test with WC 3.9.0

= 0.2.2 2019-12-28 =
* Fix - Fix FedaPay labels
* Add - Add FedaPay Checkout modal feature

= 0.1.7 2019-08-17 =
* Fix - Add version to icon URL
* Fix - Test with Woocommerce 3.7.0

= 0.1.6 2019-08-06 =
* Fix - Add setting and documentation link on plugins page
* Fix - Review french translations
* Fix - Remove ls from travis

= 0.1.5 2019-08-05 =
* Fix - Fix travis deployment script

= 0.1.4 2019-08-05 =
* Fix - Add checkout icon url setting
* Fix - Fix callback security issue
* Fix - Add notice for unsupported currencies
* Fix - Add deployment script

= 0.1.3 2019-02-25 =
* Fix - Update checkout logo

= 0.1.2 2018-10-10 =
* Fix - Implement notice warning dismissal
* Fix - Improve translations
* Fix - Show warning message if dependencies are not satisfied

= 0.1.1 2018-10-04 =
* Fix - Add banners and logos for better branding
* Fix - Improve errors display
* Fix - Update plugin asset files
* Fix - Translate plugin into french
* Fix - Update changelog file

= 0.1.0 =
* Beta release
