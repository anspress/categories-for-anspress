=== Categories for AnsPress ===
Contributors: nerdaryan
Donate link: http://paypal.me/nerdaryan
Tags: anspress, question, answer, q&a, forum, stackoverflow, quora
Requires at least: 4.1.1
Tested up to: 4.6
Stable tag: 3.0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Add category support in AnsPress.

== Description ==

Support forum: http://anspress.io/questions/

Categories for AnsPress is an extension for AnsPress, which add category (taxonomy) support for questions. This extensions will add two pages in AnsPress:

* Categories page (list all categories of AnsPress)
* Category page (Single category page, list questions of a specfic category)

This extension will also add categories widget, which can be used to to show questions categories anywhere in WordPress.

== Installation ==

Simply go to WordPress plugin installer and search for categories for anspress and click on install button and then activate it.

Or if you want to install it manually simple follow this:

    * Download the extension zip file, uncompress it.
    * Upload categories-for-anspress folder to the /wp-content/plugins/ directory
    * Activate the plugin through the 'Plugins' menu in WordPress

== Changelog ==

= 3.0.2 =

* Show current category in breadcrumbs

= 3.0.1 =

* Added category feed link

= 3.0 =

* Fixed theme related bug.
* Made compatible with AnsPress 3.0.0

= 2.3 =

* Added category canonical url
* Select current category in ask form
* Hide category filter is single category page
* Fix: Subscribe Widget fatal error
* Improved category filter
* Improved list filters
* Added category hover card
* Added featured image for category
* Fixed tests to use AnsPress form github
* Added icon thumb size in widget
* Fixed warnings and use ap_option_groups to register options

= 2.0.1 =

* Fixed translation issue.

= 2.0.0 =

* Improved loading order
* Added tests and travis config

= 1.4.2 =

* Minor bug fixes

= 1.4.1 =

* Minor bug fixes

= 1.4 =

* Removed subscription page tab
* Fix: query var
* Update language pot
* Added de_DE
* Fixed subscribe button
* Set subscribe button type
* Added option for categories page order and orderby
* Add warning message if AnsPress version is lower then 2.4-RC
* Added option to change category and categories slug
* Move “Categories title” from “layout” to ”pages”.
* Updated fr mo
* Support utf8 in permalink and show 404 if category not found
* Added trkish translation by nsaral


= 1.3.9 =

* Added turkish translation and fixed textdomain
* Improved category.php
* added widget wrapper div
* Removed Question category from title
* Fixed wrong callback
