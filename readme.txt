=== Plugin Name ===
Stable tag: 5.0.1
Tested up to: 4.3.0
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


Localisation: Russian, English, German (Matthias Siebler)

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
* ADD: Poll option to don't show results screen untill voting are closed
* ADD: add link to the post in admin-polls-list if polls shortcode is used in post content
* cron: shadule polls opening & activation
* sorting on archive page
* Collect cookies demPoll_N in one option array 


== Changelog ==
= 5.0.1 =
* ADD: expand answers list on Polls list page by click on the block.

= 5.0 =
* BUGFIX: replace VOTE button with REVOTE. On cache mode, after user voting he see backVOTE button (on result screen), but not "revote" or "nothing" (depence on poll options).
* HUGE ADD: Don't show results until vote is closed. You can choose this option for single poll or for all polls (on settings page).
* ADD: edit & view links on admin logs page.
* ADD: Search poll field on admin polls list page.
* ADD: All answers (not just win) in "Winner" column on polls list page. For usability answers are folds.
* ADD: Poll shordcode on edit poll page. Auto select on its click.
* CHANGE: sort answers by votes on edit poll page.

= 4.9.4 =
* FIX: change default DB tables charset from utf8mb4 to utf8. Thanks to Nanotraktor

= 4.9.3 =
* ADD: single poll option that allow set limit for max answers if there is multiple answers option.
* ADD: global option that allow hide vote button on polls with no multiple answers and revote possibility. Users will vote by clicking on answer itself.
* fix: disable cache on archive page.

= 4.9.2 =
* FIX: bootstrap .label class conflict. Rename .label to .dem-label. If you discribe .label class in 'additional css' rename it to .dem-label please.
* ADD: Now on new version css regenerated automaticaly when you enter any democracy admin page.

= 4.9.1 =
* FIX: Polls admin table column order

= 4.9.0 =
* ADD: Logs table in admin and capability to remove only logs of specific poll.
* ADD: 'date' field to the democracy_log table.

= 4.8 =
* Complatelly change polls list table output. Now it work under WP_List_Table and have sortable colums, pagination, search (in future) etc.

= 4.7.8 =
* ADD: en_US l10n if no l10n file.

= 4.7.7 =
* ADD: de_DE localisation. Thanks to Matthias Siebler

= 4.7.6 =
* DELETED: possibility to work without javascript. Now poll works only with enabled javascript in your browser. It's better because you don't have any additional URL with GET parametrs. It's no-need-URL in 99% cases..

= 4.7.5 =
* CHANGE: Convert tables from utf8 to utf8mb4 charset. For emoji uses in polls

= 4.7.4 =
* CHANGE: Some css styles in admin

= 4.7.3 =
* ADD: Custom front-end localisation - as single settings page. Now you can translate all phrases of Poll theme as you like.

= 4.7.2 =
* CHANGE: in main js cache result/vote view was setted with animation. Now it sets without animation & so the view change invisible for users. Also, fix with democracy wrap block height set, now it's sets on "load" action, but not "document.ready".
* CHANGE: "block.css" theme improvements for better design.

= 4.7.1 =
* ADD: "on general options page": global "revote" and "democratic" functionality disabling ability
* ADD: localisation POT file & english transtation

= 4.7.0 =
* CHANGE: "progress fill type" & "answers order" options now on "Design option page"
* FIX: english localisation 

= 4.6.9 =
* CHANGE: delete "add new answer" button on Add new poll and now field for new answerr adds when you focus on last field.

= 4.6.8 =
* FIX: options bug appers in 4.6.7

= 4.6.7 =
* ADD: check for current user has an capability to edit polls. Now toolbar doesn't shown if user logged in but not have capability

= 4.6.6 =
* FIX: Huge bug about checking is user already vote or not. This is must have release!
* CHANGE: a little changes in js code
* 'notVote' cookie check set to 1 hour

= 4.6.5 =
* ADD: New theme "block.css"
* ADD: Preset theme (_preset.css) now visible and you can set it and wtite additional css styles to customize theme

= 4.6.4 =
* FIX: when user send democratic answer, new answer couldn't have comma

= 4.6.3 =
* FIX: Widget showed screens uncorrectly because of some previous changes in code.
* Improve: English localisation

= 4.6.2 =
* FIX: great changes about polls themes and css structure.
* ADD: "Ace" css editor. Now you can easely write your own themes by editing css in admin.

= 4.6.1 =
* FIX: some little changes about themes settings, translate, css.
* ADD: screenshots to WP directory.

= 4.6.0 =
* ADD: Poll themes management
* FIX: some JS and CSS bugs
* FIX: Unactivate pool when closing poll 

= 4.5.9 =
* FIX: CSS fixes, prepare to 4.6.0 version update
* ADD: Cache working. Wright/check cookie "notVote" for cache gear optimisation

= 4.5.8 =
* ADD: AJAX loader images SVG & css3 collection
* ADD: Sets close date when closing poll

= 4.5.7 =
* BugFIX: revote button didn't minus votes if "keep-logs" option was disabled
        
= 4.5.6 =
* ADD: right working with cache plugins. Auto unable/dasable with wp total cache, wp super cache, WordFence, WP Rocket, Quick Cache. If you use the other plugin you can foorce enable this option.
* ADD: add link to selected css file in settings page, to conviniently copy or view the css code
* ADD: php 5.3+ needed check & notice if php unsuitable
* Changed: archive page ID in option, but not link to the archive page
* FIX: in_archive check... to not show archive link on archive page
* FIX: many code improvements & some bug fix (hide archive page link if 0 set as ID, errors on activation, etc.)

= 4.5.5 =
* Changed: Archive link detection by ID not by url

= 4.5.4 =
* FIX: js code. Now All with jQuery
* FIX: Separate js and css connections: css connect on all pages into the head, but js connected into the bottom just for page where it need

= 4.5.3 =
* FIX: code fix, about $_POST[*] vars

= 4.5.2 =
* FIX: Remove colling wp-load.php files directly on AJAX request. Now it works with wordpress environment - it's much more stable.
* FIX: fixes about safe SQL calls. Correct escaping of passing variables. Now work with $wpdb->* functions where it posible
* FIX: admin messages

= 4.5.1 =
* FIX: Localisation bug on activation.

= 4.5 =
* ADD: css style themes support.
* ADD: new flat (flat.css) theme.
* FIX: Some bugs in code.

= 4.4 =
* ADD: All plugin functionality when javascript is disabled in browser.
* FIX: Some bug.

= 4.3.1 =
* ADD: "add user answer text" field close button when on multiple vote. Now it's much more convenient.
* FIX: Some bug.

= 4.3 =
* ADD: TinyMCE button.
* FIX: Some bug.

= 4.2 =
* ADD: Revote functionality.

= 4.1 =
* ADD: "only registered users can vote" functionality.
* ADD: Minified versions of CSS (*.min.css) and .js (*.min.js) is loaded if they exists.
* ADD: js/css inline including: Adding code of .css and .js files right into HTML. This must improve performance a little.
* ADD: .js and .css files (or theirs code) loads only on the pages where polls is shown.
* ADD: Toolbar menu for fast access. It help easily manage polls. The menu can be disabled.

= 4.0 =
* ADD: Multiple voting functionality.
* ADD: Opportunity to change answers votes in DataBase.
* ADD: "Random show one of many active polls" functionality.
* ADD: Poll expiration date functionality.
* ADD: Poll expiration datepicker on jQuery.
* ADD: Open/close polls functionality.
* ADD: Localisation functionality. Translation to English.
* ADD: Change {democracy}/{democracy:*} shortcode to standart WP [democracy]/[democracy id=*].
* ADD: jQuery support and many features because of this.
* ADD: Edit button for each poll (look at right top corner) to convenient edit poll when logged in.
* ADD: Clear logs button.
* ADD: Smart "create archive page" button on plugin's settings page.
* FIX: Improve about 80% of plugin code and logic in order to easily expand the plugin functionality in the future.
* FIX: Improve css output. Now it's more adaptive for different designs.


== Upgrade Notice ==
All upgrades are made automatically
