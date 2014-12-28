WP Post Babel
===============

A WordPress Multilingual plugin.

Approach
--------
Alters posts table by adding a language and translation group column.
Each Post Translation is a post of its own. New translations are added by cloning a post, 
so each property gets copied to the translation.
Post attachments are cloned as well.

Media posts preserve their file sources by default.

On the frontend only posts for the selected language show up.

Features
--------
- Supports import/export of multilingual posts.
- Use [Loco Translate Plugin](http://wordpress.org/plugins/loco-translate/) to translate Taxonomies an Menus.
  (Note: The Author would need to deploy [this Change](https://github.com/loco/wp-loco/pull/2) first. 
  Apply the Patch yourself if you're too impatient, it's just a one-liner.)


Usage:
------
- Posts / Pages / Media: First Copy Post, then translate
- Taxonomies / Menus: First install Loco Translate.


Restrictions
------------
- Taxonomy translation is highly dangerous in Multisite Environment.
- The "most recent" tab in the menu editor shows all translations. AFAIK this can't be fixed.


Plugin API:
-----------
action `glottybot_post_cloned` , $post , $new_post
filter `glottybot_post_clone_data` , $postarr , $post 
filter `glottybot_edit_po_url` , $edit_url , $this->textdomain_prefix , $object_identifier , $language 




ToDo:
-----
- Frontend: Localized feed links
- Frontend: add get param for non mod_rewrite permalinks
- Frontend: language switch (widget, menu, ...)
	- DONE Method 1: content sensitive menu item > links to current item in other language
	- Methos 3: do_action( 'glottybot_language_switch' , $args );
- Frontend: header <link rel=alternate> to translated pages
- Admin: Bug selected lang falls back to default sometimes
- Build ACF Bridge, @ clone: change attached items to their translated versions (if exist)
- Cleanup & comment code
- Remove taxo/menu po from WP-language select
  - prefix po files, hide everything having that prefix.
- Multisite: seperate taxos / menus for each blog. (needs better po editor than loco)

- Tool for "this post is translation of [ Select: OTHER-POST ]" 
	- OTHER-POST: Post that has no translation in original

- DONE Frontend: localized taxonomy URLs
- DONE Edit Menu: Filter Posts list / Group by translation group.
- DONE Check with post type archive links.
- CAN'T DO: Put taxonomies in their own subfolder 
  (loco won't find pot and po)
- DONE Load WP Locales
- DONE =0= Post / Page Permalink?

- DONE Menus
- DONE set menu item translations
- DONE mo/po editor for Taxonomies. Store in languages/taxonomy-LOCALE.mo/.po, languages/menus-LOCALE.mo/.po
- DONE load\_textdomains for the above
- Same for Menus
- UGLY set proper post count on table views: hook into wp_count_posts() in wp-includes/post.php
	-> post issue in trac, might be done through a wp_query!
- UGLY add language param to admin menu urls ... gosh! they're partially hardcoded
- DONT move permalink settings to permalink admin page (doesn't save there, needs a closer look)
- DONE Show untranslated posts with clone-from link on admin list screen
- DONE Enable lang-filter in admin only on public (= translatable) posts 
- DONE Disable lang-filter for trashed posts 
