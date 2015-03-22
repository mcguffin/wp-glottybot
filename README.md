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
- **Backend**
- [x] Settings, Feature: only show translated posts vs. fallback to default language
- [x] Posts list table -> Trash post action: trash translation group / trash single post
- [x] Language switcher widget
	- [x] Insert Placeholders
	- [x] Validate settings
- [x] Settings: prevent duplicate locales (JS)
- [?] prevent duplicate `post_locale` on post cloning
- [?] Robust language detection (should always return an existing locale)
- [x] Settings: Remove feature "Hide untranslated" (always on!)
- [x] only ajax clone post
	- [x] use button in locale list column
- [x] EditPost screen: force admin language
- [ ] Integrate GlottyPoMo
- [x] check import/export again
- [ ] QuickEdit: Clone missing translation
- [ ] Feature: post list table filter: show untranslated
- [ ] Feature: Bulk edit actions:
	- [ ] Clone missing translations
	- [ ] Trash translation group
- [ ] Feature: sync post meta, post parent, ...
- [ ] Feature: Map posts to each other (= edit translation group)
- [ ] Map custom plugin locale to WP-Locale (take best match, like `pt_BR` -> `pt_PT` )
- [ ] Feature: Widget option link to current page translation / home
- **Frontend**
- [x] Permastruct: current item translation url: search, archive
- [x] link rel=alternate in WP head
- [x] Locale in <head>

PRO:
- Feature: Translation mapping Table
- Feature: Translate widget contents
- Feature: Clone tree
- Feature: translator capability
- auto translate
- output translated feeds
- WPML Migration