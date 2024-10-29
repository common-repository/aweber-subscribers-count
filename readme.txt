=== Aweber Subscribers Count ===
Contributors: alexandreb3
Donate link:
Tags: aweber, counter, list
Requires at least: 3.0
Tested up to: 3.4.1
Stable tag: 1.6.0
License: GNU General Public License v3.0
License URI: http://www.opensource.org/licenses/gpl-license.php

Aweber Subscribers Count is a simple Wordpress plugin that displays the subscriber count of a specific aweber list.

== Description ==

Aweber Subscribers Count is a simple Wordpress plugin that displays the subscriber count of a specific aweber list.

The plugin has an option page in order to connect to the aweber account and select the list you want to use.

Paste the shortcode with your list name in any post, page or widget: [displaycount list="your_list_name"]

You can combine lists using this shortcode : [displaycount list="your_list_name_1,your_list_name_2"]

You can display your list count anywhere in your theme by using <?php echo do_shortcode('[displaycount list="your_list_name"]'); ?>

== Installation ==

This section describes how to install the plugin and get it working.

1. Normal Plugin install.
2. Go to settings page & get the authentication secret.
3. Paste shortcode anywhere including any sidebar text widget : [displaycount list="your_list_name"].

== Frequently Asked Questions ==


== Screenshots ==


== Changelog ==

= 1.6.0 =
- The plugin now stores the count in the database in order to increase the page loading
- You can set the count refresh frequency
- Fixed grand total tick in admin

= 1.5.3 =
-CSS Fix

= 1.5.2 =
-Readme update

= 1.5.1 =
-Minor CSS fix

= 1.5 =
- Combination of list count
- You can now choose to use the subscribed count or the grand total count

= 1.2 =
- Release