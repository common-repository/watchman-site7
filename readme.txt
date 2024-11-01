=== WatchMan-Site7 ===

Plugin Name: WatchMan-Site7
Plugin URI: https://wordpress.org/plugins/watchman-site7/
Tags: cron, statistic, security
Author: Oleg Klenitsky <klenitskiy.oleg@mail.ru>
Author URI: https://adminkov.bcr.by/
Contributors: adminkov, innavoronich
Donate link: https://adminkov.bcr.by/contact/
Requires at least: 6.0
Requires PHP: 7.5
Tested up to: 6.6
Stable tag: 4.2.0
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://adminkov.bcr.by/
Initiation:	is dedicated to daughter Inna Voronich

Control of site visits, system files.

== Description ==

The plugin keeps a log of site visits, both by humans and by robots.
The main functions of the plugin are:
1. Records the date and time of visiting the site, where the visitor came from, which page he visited, the visitor's browser.
2. Records the result of the visit: without login, with login, successful login, unsuccessful login, the visitor belongs to the black list.
4. Blacklisting of a visitor with simultaneous blocking of access to the site for a certain period of time.
5. Export of records of site visits to an external file for further analysis.
6. Automatic screen refresh mode using SSE technology (server-sent events).
8. System file editor: index.php, robots.txt, htaccess, wp-config.php
9. Displaying and Deleting cron events.
10. Statistics of site visits in tabular and graphical form.
12. admin console to run commands PHP and WordPress.
13. debug_log viewing the WP, PHP debug log of site.

<a href="https://adminkov.bcr.by/" target="_blank">Plugin home page</a>

<a href="https://www.youtube.com/watch?v=iB-7anPcUxU&list=PLe_4Q0gv64g3WgA1Mo_S3arSrK3htZ1Nt" target="_blank">Demo video - [RU]</a>

<a href="https://adminkov.bcr.by/doc/watchman-site7/api_doc/index.html" target="_blank">API Documentation</a>

<a href="https://adminkov.bcr.by/doc/watchman-site7/user_doc/index.htm" target="_blank">User Documentation</a>

You can send a letter to the developer at: klenitskiy.oleg@mail.ru

==Features include:==

1. Filters I level: by date, by country, by visitor's roles
2. Filters II level: by logged, by unlogged, by login errors, by visits of robots, by visitors from the black list
3. Report of selected site visit records
4. Log auto-truncation
5. File editor: index.php
6. File editor: robots.txt
7. File editor: .htaccess
8. File editor: wp-config.php
9. Manage cron - events of site
10. Statistics of visits to the site
11. Built-in console for managing WordPress environment.
12. Widget: site visits count with automatic update of visits data
14. Information about the IP of the visitor
15. Black list of visitors and blocking the IP, or user name, or user agent for the selected period of time
16. Automatic updating of the list of site visits using SSE technology

==Translations:==

- English [en_EN]
- Russian [ru_RU]

== Installation ==

1. Install and activate like any other basic plugin
2. Define basic plugin settings menu: Visitors/settings
3. Click on the Screen Options tab to expand the options section. You'll be able to change the number of results per page as well as hide/display table columns

== Screenshots ==

1. Screen basic settings of the plugin
2. Screen Options are available at the top of the this plugin page
3. Compliance of the information panel with filters II level
4. An example of working with a black list. The visitor's IP is automatically entered in .htaccess
5. Example of filling in the fields Black list for selected visitor's IP
6. File editor for: index.php, robots.txt, .htaccess, wp-config.php
7. Viewer wp-cron tasks
8. Statistic of visits to site

== Changelog ==

= 3.1.1 =
* Added to profile: country, city of registered user.

= 3.1 =
* Stable version of the plugin. Tested with WordPress 5.1

= 3.0.4 =
* Improved plugin control interface. Added sound notification.

= 3.0.3 =
* Eliminated plugin vulnerability discovered by WordPress developers. Previous versions of the plugin have been removed from the repository by the plugin developer.

= 4.0.0 =
* Refactoring code.

= 4.1.0 =
* Improved page wms7_black_list of plugin.

= 4.2.0 =
* Refactoring codes.

== Frequently Asked Questions ==

= Question: How to use the SSE button? =
The answer: The SSE function in the plugin should be used in cases when you want to WATCH for the arrival of new visitors to the site. In another case, when you want to perform some actions on the plugin page (for example: delete records using bulk actions), it is recommended before starting these steps to disable SSE. And then, when you've done your job, you can re-enable SSE to dynamically retrieve data.