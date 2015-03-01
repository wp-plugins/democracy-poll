=== Plugin Name ===
Stable tag: 4.7.1
Tested up to: 4.1.1
Requires at least: 3.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Contributors: Tkama
Tags: democracy, poll, polls, polling, vote, survey, opinion, research, usability, cache
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

Democracy Poll works with cache plugins: WP Total Cache, WP Super Cache, WordFence, Quick Cache etc.

I focus on "easy-admin" features & fast performance. So you will have:

* Quick Edit button for Admin, right above a poll
* Plugin menu in toolbar
* Inline css & js incuding
* Css & js connection only where it's needed
* and so on. See changelog

### More Info ###

This plugin is a reborn of once-has-been-famous Democracy Poll. Even if it hasn't been updated since far-far away 2006, it still has the great idea of adding users' own answers. So here's a completely new code, I have left only the idea and great name of the original DP by Andrew Sutherland.

What can it perform?

* adding new polls;
* works with cache plugins: wp total cache, wp super cache, etc...
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
2. Add such code in the place you want Poll is appeared:

`
<?php if( function_exists('democracy_poll') ){ ?>
    <li>
        <h2>Polls</h2>
        <ul>
            <li><?php democracy_poll();?></li>
        </ul>
    </li>
<?php } ?>
`

* To show specific poll, use `<?php democracy_poll( 3 ); ?>` where 3 is your poll id.
* To embed a specific poll in your post, use `[democracy id="2"]` where 2 is your poll id.
* To embed a random poll in your post, use `[democracy]`


#### Display Archive ####
For display polls archive, use the function:

`<?php democracy_archives( $hide_active, $before_title, $after_title ); ?>`






== Frequently Asked Questions ==
Comming soon...





== Screenshots ==

1. Single vote view.
2. Single result view.
3. Multiple vote view.
4. Admin polls list page. For convenience there is add poll form.
5. Admin edit poll page.
6. Add poll admin page.
7. Democracy general settings.
8. Polls theme settings.





== TODO ==
* sorting on archive page
* limit multiple answers select
* cron: shadule polls opening & activation
* add link to the post in admin-polls-list if polls shortcode is used in post content


== Changelog ==
= 4.7.1 =
* Added: "on general options page": global "revote" and "democratic" functionality disabling ability
* Added: localisation POT file & english transtation

= 4.7.0 =
* Change: "progress fill type" & "answers order" options now on "Design option page"
* Fix: english localisation 

= 4.6.9 =
* Change: delete "add new answer" button on Add new poll and now field for new answerr adds when you focus on last field.

= 4.6.8 =
* Fix: options bug appers in 4.6.7

= 4.6.7 =
* Added: check for current user has an capability to edit polls. Now toolbar doesn't shown if user logged in but not have capability

= 4.6.6 =
* Fix: Huge bug about checking is user already vote or not. This is must have release!
* Change: a little changes in js code
* 'notVote' cookie check set to 1 hour

= 4.6.5 =
* Added: New theme "block.css"
* Added: Preset theme (_preset.css) now visible and you can set it and wtite additional css styles to customize theme

= 4.6.4 =
* Fix: when user send democratic answer, new answer couldn't have comma

= 4.6.3 =
* Fix: Widget showed screens uncorrectly because of some previous changes in code.
* Improve: English localisation

= 4.6.2 =
* Fix: great changes about polls themes and css structure.
* Added: "Ace" css editor. Now you can easely write your own themes by editing css in admin.

= 4.6.1 =
* Fix: some little changes about themes settings, translate, css.
* Added screenshots to WP directory.

= 4.6.0 =
* Added: Poll themes management
* Fix: some JS and CSS bugs
* Fix: Unactivate pool when closing poll 

= 4.5.9 =
* Fix: CSS fixes, prepare to 4.6.0 version update
* Added: Cache working. Wright/check cookie "notVote" for cache gear optimisation

= 4.5.8 =
* Added: AJAX loader images SVG & css3 collection
* Added: Sets close date when closing poll

= 4.5.7 =
* BugFix: revote button didn't minus votes if "keep-logs" option was disabled
        
= 4.5.6 =
* Added: right working with cache plugins. Auto unable/dasable with wp total cache, wp super cache, WordFence, WP Rocket, Quick Cache. If you use the other plugin you can foorce enable this option.
* Added: add link to selected css file in settings page, to conviniently copy or view the css code
* Added: php 5.3+ needed check & notice if php unsuitable
* Changed: archive page ID in option, but not link to the archive page
* Fix: in_archive check... to not show archive link on archive page
* Fix: many code improvements & some bug fix (hide archive page link if 0 set as ID, errors on activation, etc.)

= 4.5.5 =
* Changed: Archive link detection by ID not by url

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
