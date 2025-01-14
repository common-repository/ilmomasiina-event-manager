=== Ilmomasiina ===

Contributors: tysss
Tags: doodle, enrollment, event, subscription, event registration
Requires at least: 3.8.0
Tested up to: 4.4.2
Stable tag: 0.5.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Simple event manager with editable forms to register participiants via frontend UI

== Description ==

**Finnish only** (I18n in development)

Simple plugin to create and manage events with registration possibility. Ideal for organizational evenys etc.

Currently the plugin is only in Finnish.



== Installation ==


1. Upload `plugin-name.php` to the `/wp-content/plugins/` directory

1. Activate the plugin through the 'Plugins' menu in WordPress

1. Add shortcode [ilmomasiina] to a page. This page is the frontend event UI. Not necessary, but without it there wont be any list of events, only single pages that you have to know URL to.



== Changelog ==

= 0.5.1 =
* Bug fixes

= 0.5 =
* Now you can see your place while filing your form
* Cancelling registrations and editing them made more secure
* New options
* Lots of small fixes and improvments

= 0.4.2 =

* The admin can now decide if editing or canceling is possible.
* Added "email" field with email validation (browswer side).

= 0.4 =

* Updates the database structure - now every registration is own post meta 
* Prevent overwrite on simultaneous updates
* Lots of bug fixes

= 0.3.2 =

* Small fixes

= 0.3.1 =

* Small fixes
* Theme layout changes

= 0.3 =

* New method of creating event forms
* Ability to make an event private.
* Added ability to download participants as a .csv
* The events are now registered in the order of the page load - in case of a rush it's fair to those with lots of forms to fill (e.g. allergics) 
* New layout in almost everything
* Many, many smaller fixes

= 0.2 =

* Added possibility to edit and delete previous responses
* Created backup system for accidental removals of data
* Many, many smaller fixes

= 0.1.1 =

* Changed the name of the plugin
* Added info about the plugin to the plugin file

= 0.1 =

* Initial release. 
* First version only in Finnish. 
* Still in development, but plugin is usable and stable, and is already in use in a few organizations.
