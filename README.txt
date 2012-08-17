=== WordPress or Die ===
Requires at least: 3.4
Tested up to: 3.4.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Enables voting on posts similar to the functionality on Funny or Die.

== Description ==

This plugin inserts a polling area at the bottom of every post, and allows users to vote for or against the post. The name is a play off the website http://funnyordie.com which offers this functionality on most of its posts.

== Installation ==

1. Upload the folder `/wordpress-or-die/` to the `wp-content/plugins` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. That's it! The poll will automatically be inserted after every post.

== Frequently Asked Questions ==

= How do I edit the look of the polling area? =

You can edit the CSS in `/wordpress-or-die/css/vote.css` or comment it out and make your own theme in your theme's stylesheet.

= Can I edit the labels to be different from `Good` and `Bad` =

Right now you can edit them by editing the variables on lines 64 and 66 of the main plugin file. An options page is planned for later versions.

= Can I add the poll to other content types like pages? =

Not right now, no. This is another option that is planned for the options page at a later release.

== Changelog ==

= 0.1 =
* Alpha release. Basic functionality added.