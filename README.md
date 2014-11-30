WP Post Babel
===============

A WordPress Multilingual plugin.

Approach
--------
Alters posts table by adding a language and translation group column.
Each Post Translation is a post of its own. 

Features
--------
- Supports import/export of multilingual posts.
- Clone Posts 
- Use [Loco Translate Plugin](http://wordpress.org/plugins/loco-translate/) to translate Taxonomies.
  (Note: The Author would need to accepts and deploy this [PullRequest](https://github.com/loco/wp-loco/pull/2) first. 
  Apply the Patch yourself if you're too impatient, it's just a one-liner.)
- Seamless integration in WordPress.

Restrictions
------------
- Taxonomy translation is highly dangerous in Multisite Environment.

ToDo:
-----
- Tool for "this post is translation of [ Select: OTHER-POST ]" 
	- OTHER-POST: Post that has no translation in original-post.post\_language
- Build ACF Bridge

- DONE mo/po editor for Taxonomies. Store in languages/taxonomies-LOCALE.mo/.po, languages/menus-LOCALE.mo/.po
- DONE load\_textdomains for the above
- Same for Menus
- UGLY set proper post count on table views: hook into wp_count_posts() in wp-includes/post.php
	-> post issue in trac, might be done through a wp_query!
- UGLY add language param to admin menu urls ... gosh! they're partially hardcoded
- DONT move permalink settings to permalink admin page (doesn't save there, needs a closer look)
- DONE Show untranslated posts with clone-from link on admin list screen
- DONE Enable lang-filter in admin only on public (= translatable) posts 
- DONE Disable lang-filter for trashed posts 
