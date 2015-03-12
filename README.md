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
- Edit post list, Trash post action: trash all translations / trash only this translation
- Bulk edit: clone posts to missing languages


PRO:
- Translation progress Table: 
- Translate widget contents
- Clone tree
- auto translate