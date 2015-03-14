WP Post Babel
===============

A WordPress Multilingual plugin.

Approach
--------
**Posts:** Alters posts table by adding a `post_locale` and `post_translation_group` column.
Each Posts Translation is a post on its own. New translations are added by cloning a post, 
so each property gets copied to the translation.

**Taxonomies and Menus:** generates a po / mo file for every taxonomy and menu.

**Widgets:** (To be done.)

Usage:
------
(to be done)

Restrictions
------------
(to be done)

Plugin API:
-----------
(to be done)

ToDo:
-----
- Settings, Feature: only show translated posts vs. fallback to default language
- Posts list table -> Trash post action: trash translation group / trash single post
- Bulk edit: clone all posts to all missing languages
- Language switcher widget
- Settings: prevent duplicate locales
- Robust language detection (should always return existing locale)
- Permastruct: current item translation url: search, archive
- Feature: Map posts to each other (= edit translation group)
- Integrate GlottyPoMo bridge
- Map custom plugin locale to WP-Locale (take best match, like `pt_BR` -> `pt_PT` )
- link rel=alternate in WP head
- check import/export
- Feature: sync post meta, parent, ...
- only ajax clone post

PRO:
- Feature: Translation mapping Table
- Feature: Translate widget contents
- Feature: Clone tree
- Feature: translator capability
- auto translate
- output translated feeds
- WPML Migration