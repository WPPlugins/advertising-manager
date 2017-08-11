=== Advertising Manager ===
Contributors: switzer
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=scott%40switzer%2eorg&item_name=Do%20you%20like%20Advertising%20Manager%20for%20Wordpress%3F%20%20Please%20help%20development%20by%20donating%21&currency_code=USD&bn=PP%2dDonationsBF&charset=UTF%2d8
Tags: ad, admin, adsense, adserver, advertisement, advertising, affiliate, banner, google, manager, rotate, widget
Requires at least: 2.5
Tested up to: 4.2.1
Stable tag: 3.5.3
License: GPLv2 or later

Easily place and rotate ads on inside your blog posts, templates, or sidebar widgets. Simple to use, powerful features.  Used by thousands of blogs.

== Description ==

This plugin will manage and rotate your Google Adsense and other ads on your Wordpress blog.  It automatically recognises many ad networks including [Google Adsense](http://www.google.com/adsense) and other popular networks.  Target your ads to different authors, categories, tags, and pages.  View statistics on your ad performance.

Features:

* The most popular advertising plugin for Wordpress, with new features coming every single month
* Widget can be used to place ads in the sidebar
* Put ads in your blog posts with the click of a button
* Ads can be placed in your templates with a single PHP function
* Ad targeting by author, category, tag, page type, and much more
* Auto-recognition for the most popular ad networks, so you can manage these ads in your Wordpress blog rather than going to each website.
* View high level analytics for your ads

Related Links:

* <a href="https://wordpress.org/plugins/advertising-manager" title="Advertising Manager plugin for WordPress">Plugin Homepage</a>
* Follow <a href="http://twitter.com/scottswitzer" title="Track Advertising Manager developments by following author Scott Switzer on Twitter">Scott Switzer</a> on Twitter
* <a href="https://github.com/switzer/advertising-manager/issues" title="Feature requests and bugs">Feature Requests and Bugs</a>
* <a href="http://wordpress.org/tags/advertising-manager">Support Forum</a>

== Installation ==

1. Under the 'Plugins' menu in your Wordpress Admin console, click 'Add New'.
1. Search for 'advertising-manager'.
1. The first item should be 'Advertising Manager' by Scott Switzer.  Click the 'Install Now' button.
1. Click 'Ok' if an alert box appears.
1. After the plugin installs, click 'Activate Plugin'.

== Usage ==

1. Add your ads by clicking 'Create New' under the 'Ads' menu.
1. Place ads in your template by adding '&lt;?php advman_ad(name); ?&gt;' to your template in the place you want to see an ad (where 'name' is the name of your ad).
1. Place ads in your blog posts and pages by adding '[ad#name]' to your blog post (where 'name' is the name of your ad), or by using the 'Insert Ad' dropdown from the edit toolbar.
1. Place ads in your sidebar by dragging the 'Advertisement' widget onto your sidebar, and selecting the ad you want to display

== Screenshots ==

1. Manage Your Advertising in Wordpress 2.7
1. Create ad in Wordpress 2.7
1. Edit ad in Wordpress 2.7
1. Ad settings in Wordpress 2.7
1. Manage Your Advertising in Wordpress 2.5
1. Create ad in Wordpress 2.5
1. Edit ad in Wordpress 2.5
1. Ad settings in Wordpress 2.5


== Frequently Asked Questions ==

= I previously used the Adsense Manager plugin for Wordpress.  Do I need to reconfigure my ads? =
Advertising Manager will automatically import your settings from Adsense Manager.  There is no modification necessary.  In addition, Advertising Manager will accept the Adsense Manager ad calls ('<?php adsensem_ad() ?>') as well.

= I previously used Adsense Deluxe plugin for Wordpress.  Do I need to reconfigure my ads?
Advertising Manager will automatically import your settings from Adsense Deluxe.  There is no modification necessary.  In addition, Advertising Manager will accept the Adsense Manager ad calls ('<!--adsense#name-->') as well.

= Does Advertising Manager support Wordpress MU (multi-user)? =
Yes.

= Can Advertising Manager work in my language? =
Yes.  Advertising Manager is localised.  If your blog is in another language, and Advertising Manager shows in English, then it is most likely that a translation has not been done.
Don't worry - if you are a native speaker in English as well as your local language, you can help out!  Advertising Manager translations can be updated here:

https://www.transifex.com/projects/p/advertising-manager/

= Do I still need Advertising Manager now I can manage ads through Google's system? =

Yes.  Advertising manager allows you to rotate ads, easily turn on/off ads, place them in your blog, and many other features.  It a critical tool to use if you want to make more from your advertising.

= How can I share revenue with my authors? =

1.  Load YOUR ad into Advertising Manager.
1.  Load your AUTHOR's ad into Advertising Manager.
1.  Name both ads the same - this will allow them to rotate.
1.  In your author's ad, be sure to select the authors username in Display Options
1.  Set the weights of the ads according to your revenue share.  The easiest way to do this is to set YOUR ad weight to 36, and then set the weight of your author's ad according to the revenue share deal.  For 10% revenue share, set the author ad weight to 4; 20% = 9; 33% = 18; 40% = 24; 50% = 36; 60% = 54; 66.7% = 72; 70% = 84; 80% = 144; 90% = 324.  For the nerdy wonks out there, the formula is (AUTHOR AD WEIGHT) = (MY AD WEIGHT * REVENUE SHARE) / (1 - REVENUE SHARE)


== To Do ==

* Auto-inserting of ads into posts based on configurable rules (i.e. All Posts, 2nd Paragraph)
* Support for Amazon Affiliates and any other networks I hear about.
* Add an 'about us' page
* Add an example ad upon installation so that it is easy to see what Advertising Manager does
* Add usage information at the bottom of the list screen
* By default, show a placeholder ad (not the real ad) for admin users so that ad quality is higher
* Add ability to show in an email
* Move stats into its own table http://codex.wordpress.org/Creating_Tables_with_Plugins

== Bugs ==

* Setting number of ads to display in ad-list does not work.
* Unable to configure what columns to display in ad-list.

== Upgrade Notice ==

= 3.5.3 =
* Begin to separate out data so that we take up less memory resources

== Change Log ==

= 3.5.3 =
* Bug with new installs of Advertising Manager in 3.5.2

= 3.5.2 =
* Begin to separate out data so that we take up less memory resources

= 3.5.1 =
* Only update 10 posts at a time when upgrading shortcodes

= 3.5 =
* Fixed shortcodes issue introduces with WP 4.0.1
* Fixed messages that printed in debugging mode
* Initial analytics screen

= 3.4.29 =
* Tested for Wordpress 4.0
* Reformatted code to prepare for Analytics - coming soon!
* Fixed bug with previewing ad

= 3.4.28 =
* Fixed bug when using filtering by tag or category
* Removed templates for wordpress versions 2.4 and earlier
* Reformatted code in preparation for ad slots

= 3.4.27 =
* Updated translations to use Transifex (https://www.transifex.com/projects/p/advertising-manager/)
* Updated deployment instructions with new translation methodology

= 3.4.26 =
* Allowed default ad to be toggled from ad list again
* Changed Ad.js URL to be from CDN

= 3.4.25 =
* Added experimental ad quality feature - url verification
* Clean up the way notices work

= 3.4.24 =
* Added search functionality on ad list
* Added sort functionality on ad list
* Added Advertising Manager version to admin page footer for advertising pages
* Added screen level documentation
* Added paging when more than 10 ads
* Added better filtering
* Added link to settings on plugin screen
* Lots of behind the scenes cleanup!

= 3.4.23 =
* Added custom icon to menu and tinymce editor
* Code cleanup
* Added better notices after performing actions
* Fixed bulk actions - copy and delete now work from the list screen
* When there are no ads, you are now redirected to the create screen from the list screen
* Added better Wordpress editor integration with TinyMCE
* Updated developer instructions for people who want to get up and running quickly with Docker

= 3.4.22 =
* Split up Advertising manager page into multiple pages
* Fixed issue with setting network defaults.  Thanks @Gwyneth Llewelyn

= 3.4.21 =
* Added notices when items where being saved, copied, and deleted
* Removed broken ad exchange integration

= 3.4.20 =
* Updated Advertising Manager (after 4 years!) to work with the latest Wordpress version

= 3.4.19 =
* Fixed PHP ads to use proper syntax

= 3.4.18 =
* Fixed PHP Compaitible Mode to work with in-post ads
* Tested with WP 3.0 and made minor changes

= 3.4.17 =
* Fixed compatibility issue with the Tweet Blender plugin (thanks kirilln!)
* Fixed readme file formatting issues

= 3.4.16 =
* Fixed bug with displaying ads in a widget.

= 3.4.15 =
* Fixed issue with serving ads in a post.  Added tag based ad limitations.

= 3.4.14 =
* Removed some notices from the code.  Added PHP ad ability (BETA).  Added collecting statistics on ads (as a start - much more to do).  Added the 'notes' column in the ad list.

= 3.4.13 =
* Added additional checking before including files in the plugin directory.  Removed '@' for defines - if there is an error, things should stop there.

= 3.4.12 =
* Added displaying ads to particular categories.

= 3.4.11 =
* Fixed error when using Advman for Swedish and other languages.

= 3.4.10 =
* Fixed array error when checking for author.

= 3.4.9 =
* Fixed show-author functionality.  Add ability to 'Set Max Ads Per Page' for all ad types.

= 3.4.8 =
* Fixed minor Google Adsense 'type' issue, minor notice, and fixed defaulting

= 3.4.7 =
* Fixed adsense account id importing issue, short tag issue, and openx importing issue

= 3.4.6 =
* Fixed and expanded widget functionality for WP2.8 users

= 3.4.5 =
* Fixed many small bugs, formatting changes, and missing fields from some ad networks

= 3.4.4 =
* Fixed an issue where scripts were being delivered to all admin screens

= 3.4.3 =
* Fixed display bug with WP 2.8

= 3.4.2 =
* Added multiple select for author field, changed the page type field (show *), fixed error in factory method

= 3.4.1 =
* Fixed settings screen, and random bug with printf

= 3.4 =
* New architecture - less space, more efficient code.  Tons of ad network bug fixes.  Added plugin design, and new ad serving engine.

= 3.3.18 =
* Added ability to suppress widget formatting on ads, fixed issue with PHP_INT_MAX on versions of PHP before 4.4.

= 3.3.17 =
* Fixed a bug with widget display, updated all language files, fixed a bug with Ozh plugin

= 3.3.16 =
* Added functionality around reverting to older versions of adsensem, fixed a bug with 0 weight ads, fixed a bug displaying ID rather than name for post ads

= 3.3.15 =
* Fixed small bug in upgrade script, added counter support to widgets

= 3.3.14 =
* Only enable Advertising Manager when Adsense Manager is disabled

= 3.3.13 =
* Fixed a notice error in WP 2.6, added a small script which removes a notice set by adsense manager

= 3.3.12 =
* Fixed error when using Advertising Manager as a widget

= 3.3.11 =
* Added 'advman' to all variables which reside in the wordpress scope, to ensure that they do not stomp on other plugins

= 3.3.10 =
* Added Chitika support, added counter support, fixed regex for ad in posts

= 3.3.9 =
* Public beta - rotating ads, Adify support, much bug fixing and code restructuring

= 3.3.4 =
* First alpha version that is separate from Adsense Manager


== Licence ==

This plugin is released under the GPL - you can use it free of charge on your personal or commercial blog. Make sure to submit back to the project any changes that you make!


== Translations ==

Advertising Manager is translated into several different languages.  If a translation is missing or incomplete, and you want to help, please head over to the [Advertising Manager Transifex](https://www.transifex.com/projects/p/advertising-manager/) project to help with your translation.
