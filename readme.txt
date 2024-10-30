=== Connecta - link your Woocommerce store with your ebay account ===
Contributors: mipromotionalsourcing
Tags: ebay, synchronize, inventory management, stock, synchronize inventory, synchronize orders
Requires at least: 5.0.0
Tested up to: 5.0.0
Requires PHP: 7.0.0
Stable tag: 1.0.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.en.html

Connecta links your WooCommerce store with your ebay listings to synchronize inventory and import orders

== Description ==
Connecta is a plugin which allows you to use the Connecta webhook with your Wordpress site. The Connecta webhook allows you to integrate your website store with your ebay store, allowing you to easily manage your stock and orders from one place. Link listings and automatically receive ebay orders in your Wordpress store, automatically manage inventory levels so you never sell an item you don't have in stock and process ebay orders directly on your Wordpress site.

Connecta allows you to:
- Link existing ebay listings with your WooCommerce product listings, simply by using the same SKU on both sites;
- Automatically manage stock levels across linked listings, when inventory quantity changes on your own site, this will be reflected on ebay, and vice-versa;
- View ebay order details directly in WooCommerce;
- Mark ebay orders as dispatched in WooCommerce;
- Automatically create Wordpress users for new Ebay customers;
- Automatically link new orders to the Wordpress user, based on ebay username.
Use of the Connecta plugin requires a Connecta account. Sign up at https://connecta.mipromotionalsourcing.com


== Installation ==
Installing via WordPress
Log-in to your WordPress administrator panel;
Select the Plugins page from the menu and click *Add New*;
Search for *Connecta* in the search bar at the top right of the screen;
Click *Install Now* and after installation has completed click *Activate*.

Installing via FTP
Download the plugin by searching for *Connecta* on wordpress.org/plugins, and clicking *download*;
Login to your hosting space via FTP software, e.g. FileZilla;
Upload the plugin folder into wp-content>wp-plugins;
Login to the WordPress Administrator Panel;
Activate the plugin by going to Plugins and pressing *Activate* next to Connecta.

== Frequently Asked Questions ==
How are ebay orders shown in my site?
Orders pulled from ebay by Connecta are shown in your WooCommerce orders page, alongside your website orders. All the order details for ebay orders are automatically populated in your site. Ebay orders have a badge in the WooCommerce order list, and on the order page, so they can be differentiated from orders received directly on your site.

How do I link an ebay listing with a WooCommerce product?
Just set the SKU in ebay the same as in WooCommerce, and Connecta will automatically link the listings. This will mean orders on ebay are linked with the correct product in WooCommerce, and stock deducted accordingly.

Does Connecta work with variable listings?
Yes. Connecta works with variations in ebay, and variable listings in WooCommerce, in any combination. Just ensure the SKU of your listing on ebay matches the same product in WooCommerce. For example, if your ebay listing uses variations, but each item is a separate product in your WooCommerce store, set the SKU for the ebay variation the same as the WooCommerce product. For full instructions for setting SKUs in both ebay and WooCommerce take a look at mipromotionalsourcing.com/connecta/skus

How do I mark an ebay order as despatched in WooCommerce?
Just set the WooCommerce order status to completed, and it will be automatically marked as despatched in ebay.

How much does Connecta cost?
The Connecta Wordpress plugin is free. In order to synchronize with ebay you will need to have an active Connecta account. Connecta offers 3 month free trials, without requiring any payment details. After the free trial you may choose to continue your subscription for just $60/year. Create your account at mipromotionalsourcing.com/connecta

Will the Connecta plugin slow my site down?
The Connecta service is designed to minimize the amount of processing which takes place on your site, with data only pushed to your site when something changes. This means there is no noticeable impact on site speed or performance.

Can I use Connecta with more than one ebay account?
No. Currently Connecta can only connect with a single ebay account per Connecta account. If you would find this functionality useful, please suggest it via the support forum.


== Screenshots ==
1. Imported orders shown in the WooCommerce orders list
2. Fulfilled Ebay order 
3. Connecta plugin status page

== Changelog ==
1.0.0
Release date: 27th September 2019
* initial release