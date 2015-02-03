=== Plugin Name ===
Stable tag: 4.5.5
Tested up to: 4.1
Requires at least: 3.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Contributors: Tkama
Tags: democracy, poll, polls, polling, vote, survey, opinion, research, usability
Donate link: wp-kama.ru

Adds an AJAX democratic polls to you site. Visitors can vote more than one answer & add ther own answers.


== Description ==

This plugin adds a clever and convenient system to create various Polls with different features, such as:

* Single voting
* Multiple voting
* Poll with ability to add new answers by visitors
* Poll with the specified date of the end
* You can block vote function for unregistered users
* You can choose different design of a poll
* and so on. See changelog

It's very convenient plugin. I focus on "easy-admin" features & fast performance. So you will have:

* Quick Edit button for Admin, right above a poll
* Plugin menu in toolbar
* Inline css & js incuding
* Css & js connection only where it's needed
* and so on. See changelog

### More Info ###

This plugin is a reborn of once-has-been-famous Democracy Poll. Even if it hasn't been updated since far-far away 2006, it still has the great idea of adding users' own answers. So here's a completely new code, I have left only the idea and great name of the original DP by Andrew Sutherland.

What can it perform?

* adding new polls;
* users may add their own answers (Democracy), the option may be disabled if necessary;
* multi-voting: users may multiple answers instead of a single one (may be disabled on demand);
* closing the poll after the date specified beforehand;
* showing a random poll when some are available;
* closing polls for still unregistered users;
* a comfortable editing of a selected poll: 'Edit' key for administrators;
* votes amount editing;
* a user can change his opinion when re-vote option is enabled;
* remember users by their IP, cookies, WP profiles (for authorized users). The vote history may be cleaned up;
* inserting new polls to any posts: the [demоcracy] (shortcode). A key in visual editor is available for this function;
* a widget (may be disabled);
* convenient polls editing: the plugin's Panel is carried out to the WordPress toolbar; (may be disabled);
* .css or .js files may be disabled or embedded to HTML code;
* showing a note under the poll: a short text with any notes to the poll or anything around it;
* changing the poll design (css themes);
* primary interface is Russian, but Enlglish interface is also available.


Localisation: Russian, English

Needs PHP 5.3 and above.


== Installation ==

### Instalation via FTP ###
1. Download the plugin `.zip` archive
2. Open `/wp-content/plugins/` directory
3. Put `democracy` folder from archive to opened `/plugins` folder
4. Activate `Democracy Poll` Plugin in WordPress Admin
5. Go to `WP-Admin > Settings > Democracy Poll`


### Instalation via WP Admin ###
1. Go to `Plugins > Add New > Search Plugins` enter "Democracy Poll"
2. Find the plugin in search results and activate it.


### Usage (Widget) ###
1. Go to `WP-Admin -> Appearance -> Widgets` and find `Democracy Poll` Widget.
2. Add this widget to one of existing sidebar.
3. Set Up added widget and press Save.
4. Done!


### Usage (Without Widget) ###
1. Open sidebar.php file of your theme: `wp-content/themes/<YOUR THEME NAME>/sidebar.php`
2. Add such code in place you want Poll is appeared:

~~~
<?php if( function_exists('democracy_poll') ){ ?>
	<li>
		<h2>Polls</h2>
		<ul>
			<li><?php democracy_poll();?></li>
		</ul>
	</li>
<?php } ?>
~~~

#### Display Archive ####
For displaing archive polls use function:

~~~
<?php democracy_archives( $hide_active, $before_title, $after_title ); ?>
~~~





== Frequently Asked Questions ==
Comming soon...





== Screenshots ==
Comming soon...





== TODO ==
* IMPORTANT: right working with cache plugins (wp total cache)
* limit multiple answers select
* set archive page ID in option
* cron: shadule polls opening & activation
* add link to post in admin polls list if polls shortcode is used in post content
* add link to selected css file in settings page, to conviniently copy or view the css code
* in_archive check... to don't show poll in sidebar when on archive page


== Changelog ==
= 4.5.5 =
* Fix: Archive link detection by ID not by url

= 4.5.4 =
* Fix: js code. Now All with jQuery
* Fix: Separate js and css connections: css connect on all pages into the head, but js connected into the bottom just for page where it need

= 4.5.3 =
* Fix: code fix, about $_POST[*] vars

= 4.5.2 =
* Fix: Remove colling wp-load.php files directly on AJAX request. Now it works with wordpress environment - it's much more stable.
* Fix: fixes about safe SQL calls. Correct escaping of passing variables. Now work with $wpdb->* functions where it posible
* Fix: admin messages

= 4.5.1 =
* Fix: Localisation bug on activation.

= 4.5 =
* Added: css style themes support.
* Added: new flat (flat.css) theme.
* Fix: Some bugs in code.

= 4.4 =
* Added: All plugin functionality when javascript is disabled in browser.
* Fix: Some bug.

= 4.3.1 =
* Added: "add user answer text" field close button when on multiple vote. Now it's much more convenient.
* Fix: Some bug.

= 4.3 =
* Added: TinyMCE button.
* Fix: Some bug.

= 4.2 =
* Added: Revote functionality.

= 4.1 =
* Added: "only registered users can vote" functionality.
* Added: Minified versions of CSS (*.min.css) and .js (*.min.js) is loaded if they exists.
* Added: js/css inline including: Adding code of .css and .js files right into HTML. This must improve performance a little.
* Added: .js and .css files (or theirs code) loads only on the pages where polls is shown.
* Added: Toolbar menu for fast access. It help easily manage polls. The menu can be disabled.

= 4.0 =
* Added: Multiple voting functionality.
* Added: Opportunity to change answers votes in DataBase.
* Added: "Random show one of many active polls" functionality.
* Added: Poll expiration date functionality.
* Added: Poll expiration datepicker on jQuery.
* Added: Open/close polls functionality.
* Added: Localisation functionality. Translation to English.
* Added: Change {democracy}/{democracy:*} shortcode to standart WP [democracy]/[democracy id=*].
* Added: jQuery support and many features because of this.
* Added: Edit button for each poll (look at right top corner) to convenient edit poll when logged in.
* Added: Clear logs button.
* Added: Smart "create archive page" button on plugin's settings page.
* Fix: Improve about 80% of plugin code and logic in order to easily expand the plugin functionality in the future.
* Fix: Improve css output. Now it's more adaptive for different designs.


== Upgrade Notice ==
All upgrades are made automatically
