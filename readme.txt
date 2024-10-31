=== MyPosts ===
Contributors: iamzozo
Tags: frontend post, voting, ranking, starring, user content
Requires at least: 3.0.1
Tested up to: 4.6.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Create your own news aggregation and content ranking website, like reddit or hackernews.

== Description ==

With Myposts users are able to post links or articles to your website through a form on the frontend.
When members posting links, the plugin is fetching the title, description, and image from the given website (open graph data),
which makes an easier content submission on your site.
Users can upvote different contents on your website and also filter and sort posts by date and votes.

*   Logged in users can post an article or a link
*   Users can upvote and sort posts
*   Remote websites' meta are fetched with ajax
*   Different date ranges available for filtering, like Today, This week, etc.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/myposts` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Settings > MyPosts Options screen to configure the plugin
4. Create a page and use the following shortcode for the submission form `[myposts_form]`
