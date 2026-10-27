=== Duplicate Post - duplicate pages, copy content, clone posts ===
Contributors: rbplugins
Tags: duplicate posts, duplicate pages, copy post, copy pages, duplicate post
Requires at least: 5.0
Tested up to: 7.1
Stable tag: 2.5.0
License: GPLv2 or later

Duplicate Post RB makes it easy to duplicate posts, pages and custom post types. Create duplicate posts, clone content, automate duplication

== Description ==

[📄 Details](https://rbplugins.com/wordpress-plugins/duplicate-post-rb/) | [🚀 Get PRO Version](https://rbplugins.com/wordpress-plugins/duplicate-post-rb/#pricing) | [📖 Documentation](https://rbplugins.com/docs/duplicate-post-rb/)

**Duplicate Post RB** is a powerful yet lightweight plugin that allows you to **duplicate posts, duplicate pages, and clone custom post types** in WordPress.  
Whether you want to **copy posts**, **clone pages**, or **generate multiple content variations**, this plugin provides flexible and professional tools for content duplication.

Unlike basic duplicate post tools, Duplicate Post RB gives you full control over what exactly gets copied, including content, title, slug, featured image, taxonomies, templates, metadata, and more. This makes it ideal for websites with structured content, landing pages, learning systems, and large editorial workflows.

= New in Duplicate Post RB 2 =

* **Assistant** — every profile can adapt its copies step by step: replace a city or a product name, move links to another domain, set the status; it asks questions when you copy ("Which city?") and shows a preview on a real post
* **Rewrite & Republish** — edit a draft of a live post and publish it into the original: same address, date and comments; SEO fields, custom fields and WooCommerce data come along
* **Cleanup** — find duplicate posts and remove them, with 301 redirects to the post that stays (with our free RB Links Redirection)
* **PRO:** WordPress Multisite — copy posts to any site of your network, AI rewrite, translation and SEO texts (Claude, ChatGPT, Grok), Auto copy on publish, automatic cleanup, pages ↔ posts, WooCommerce, Elementor, WPBakery and Jetpack data

The new version introduces advanced duplication logic with configuration profiles and [WP-CLI support](https://rbplugins.com/docs/duplicate-post-rb/wp-cli-duplication/) - making Duplicate Post RB suitable for editors, agencies, and developers who need full control over how content is copied.

Duplicate Post RB now offers both profile-based duplication logic and global plugin settings, making it suitable for editors, administrators, agencies, and developers working with complex WordPress setups.

### ⭐ What's New?

- **New global option: All Post Meta**
You can now copy all post meta fields at once using a single global switch. This significantly simplifies duplication when working with complex posts, custom fields, and third-party plugins.

- **Improved Custom Post Types support**  
Enhanced support for duplicating custom post types, including those registered by themes and plugins, with more consistent behavior across different setups.

- **Full Advanced Custom Fields (ACF) integration**  
Added full support for ACF, including custom fields, field groups, post types, and taxonomies, ensuring complete and accurate duplication of structured data.

- **Import and Export for duplication profiles**  
You can now export and import duplication profiles, making it easier to reuse configurations across multiple projects and environments.

- **Custom profile buttons in post/page list**  
Added the ability to create profile-based custom buttons, allowing one-click duplication with predefined settings directly from the posts and pages list.

- **Improved integrations support**  
Better handling of third-party plugins and extended compatibility with complex WordPress setups.

- **Fixed and improved Custom Taxonomies support**
Custom taxonomies are now copied more reliably and consistently across different post types and duplication scenarios.

- **Stability fixes for duplication options**
Improved handling of Featured Image, Attachments, Categories, Taxonomies, and related options to ensure predictable results when duplicating posts and pages.

- **New Copy button in the Gutenberg editor**
Duplicate posts directly from the block editor with a dedicated Copy button, no need to leave the editor or switch views.

- **New duplication history widget in editors**
A new History widget is now available inside both the Gutenberg editor and the Classic Editor, showing:
  - source post/page
  - duplication date and time
  - used duplication profile

These improvements make post duplication faster, clearer, and more transparent, especially when working with advanced setups and multiple profiles.

### 🔥 Key Features

- **Duplicate any post, page, or custom post type**
- **Clone** items instantly or create a **new draft**
- **Profiles** for fully customizable duplication settings
- Choose which elements to copy (title, content, featured image, templates, taxonomies, etc.)
- Smart **naming rules** with placeholders:
  - `[Counter]`
  - `[CurrentDate]`
  - `[CurrentTime]`
- **Quick Copy Popup** with options:
  - number of copies
  - profile selection
  - site selection: copy to another site of a WordPress Multisite network (PRO)
- **WP-CLI support** for automation and bulk duplication
- Clean UI, lightweight code, compatible with Classic and Gutenberg editors
- All post meta duplication
- Duplication history
- Duplication source

### 🔧 WP-CLI Commands

Duplicate Post RB includes full **WP-CLI support**, making it perfect for automation, bulk duplication, and integration with scripts or CI/CD pipelines.

`wp rb-duplicate-post duplicate 1,2,6,7,8,9`

Duplicate posts using a specific profile. Use any saved duplication profile to control which fields and metadata are copied:

`wp rb-duplicate-post duplicate "123,456" --profile=1`

List available profiles:

`wp rb-duplicate-post profiles`

This allows server administrators, agencies, and developers to duplicate posts programmatically without using the WordPress dashboard.

### 🧩 Configuration Profiles

Profiles let you save different duplication behaviors - for example:

- SEO-friendly copy with prefix  
- Exact clone with all meta fields  
- Minimal copy with custom taxonomies only

You can create unlimited profiles and switch between them during duplication.

### 🚀 Optimized for Performance

Duplicate Post RB is built with performance and reliability in mind. The plugin works smoothly on large websites, supports Gutenberg and Classic Editor, and remains lightweight even under heavy use.

Whether you're running a blog, a landing-page builder, an online store, a multisite network, or a content-heavy website Duplicate Post RB helps you duplicate posts and pages faster, easier, and with full control over the result.

### 🏷️ Naming Rules

Duplicate Post RB includes flexible [Naming Rules](https://rbplugins.com/docs/duplicate-post-rb/naming-rules/) that let you define how copied posts, pages, and custom post types will be named. This is useful when you create multiple copies of the same content and want each new item to follow a clear and predictable naming pattern.

For each duplication profile, you can define a prefix and suffix for generated copies. The original post title stays in the middle, so you can build naming structures that match your editorial workflow. The plugin also supports dynamic placeholders such as [Counter], [CurrentDate], and [CurrentTime]. These values can be added to the prefix or suffix to automatically generate unique names for duplicated items.

A live preview helps you see the final naming format before duplication. You can also define the counter start value and configure custom date and time formats to match your preferred naming style.

This makes [Naming Rules](https://rbplugins.com/docs/duplicate-post-rb/naming-rules/) useful for content teams, landing page variations, product drafts, template-based workflows, and any project where duplicated content needs to stay organized.

### 🎨 Profile Custom Button

Duplicate Post RB allows you to create custom duplication buttons for each profile and display them directly in the posts and pages list. You can enable a custom button per profile and assign it a specific duplication configuration. This makes it possible to trigger different duplication behaviors with a single click, without opening additional dialogs or switching settings.

Each button can be customized with its own label, color, and display type. You can choose between label, icon, or a combination of both, depending on how you want the action to appear in the interface. Custom buttons are displayed alongside standard WordPress actions in the post and page listing, allowing quick access to duplication workflows directly from the admin list view.

In addition, an optional “copy without confirmation” mode allows you to skip confirmation dialogs and perform instant duplication. This feature is especially useful for teams working with predefined duplication scenarios, templates, or structured content workflows where speed and consistency are important.

### 🔗 Integrations

Duplicate Post RB supports duplication of standard WordPress content types such as posts and pages, as well as custom post types created by themes or plugins. You can enable duplication for all registered custom post types or selectively exclude specific ones based on your workflow. This provides flexibility when working with complex site structures.

The plugin also includes support for duplicating data created by third-party plugins. Integration with Advanced Custom Fields (ACF) allows you to duplicate custom field data along with the post content, preserving structured data and layouts. Additional integrations with popular plugins such as Yoast SEO, Rank Math, WooCommerce, Elementor, WPBakery, and others are planned to extend duplication capabilities across the WordPress ecosystem.

This ensures that duplicated content remains complete and consistent, even when using advanced plugins and custom-built solutions.

### 🧩 Advanced Custom Fields (ACF) Integration

Duplicate Post RB provides full integration with Advanced Custom Fields (ACF), allowing you to duplicate not only content, but the entire ACF data structure associated with a post.

The plugin supports duplication of:

• ACF custom fields and their values  
• Field Groups assigned to posts and post types  
• ACF-based custom post types  
• Custom taxonomies created via ACF  

All ACF data is copied accurately, preserving relationships, field configurations, and structured content without data loss. This ensures that duplicated posts remain fully functional and consistent, even in complex setups where ACF is used to build dynamic layouts, custom data models, or advanced content structures. The integration works seamlessly in the background and does not require additional configuration.

### ⚙️ Global Settings

The new Global Settings section allows you to control how duplication features behave across your WordPress installation. From a single settings screen, you can define which user roles are allowed to duplicate content, including Super Admins, Administrators, Editors, Authors, and Contributors. This makes it easy to align duplication permissions with your team structure and editorial responsibilities.

In addition, you can choose where the Copy / Duplicate actions are displayed within the WordPress admin interface. The plugin allows you to enable duplication buttons on posts and pages lists, individual edit screens, the admin bar, inside the Gutenberg editor, and within the bulk actions menu. These options give you full flexibility to integrate duplication tools exactly where your workflow requires them.

Overall, the Global Settings section helps tailor Duplicate Post RB to different editorial processes, team sizes, and usage scenarios without adding unnecessary complexity.

Duplicate Post RB now includes an option to move the plugin menu to the Tools section.

This is useful if you want to: keep the WordPress admin menu cleaner, reduce visual clutter, or hide the plugin from the primary navigation while keeping it accessible.

### 🚀 PRO Features

Duplicate Post RB includes additional features designed for advanced workflows and professional use cases.

Key extended capabilities include:

• **WordPress Multisite:** copy posts, pages and products to any site of your network — from the copy dialog, with WP-CLI (`--target-blog`) or automatically with Auto copy. Categories and tags are made on the target site, images get their own files there (also the images in the text, with the text pointing at them), and Yoast SEO and Rank Math data come along with the primary category set to the target site's own term. People see only the sites where they may publish
• Custom duplication buttons per profile for one-click actions  
• Option to duplicate content without confirmation dialogs  
• Full integration with Advanced Custom Fields (ACF), including fields, field groups, post types, and taxonomies  
• Ability to exclude specific custom post types from duplication  
• Import and export of duplication profiles for reuse across projects  
• Extended control over duplication behavior for complex content structures  

These features are useful for teams managing structured content, templates, landing pages, and large-scale WordPress installations. PRO features are available in the full version of the plugin.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/duplicate-post-rb/`
2. Activate the plugin through the **Plugins** menu
3. Go to **Posts / All Posts** or **Pages / All Pages**
4. Hover an item and click **Clone** or **New Draft**
5. (Optional) Configure Profiles in the plugin settings
6. (Optional) Use WP-CLI commands for automation

== Frequently Asked Questions ==

= Does Duplicate Post RB support duplicating pages and custom post types? =
Yes. You can duplicate posts, pages, and any custom post type registered as public.

= What are Duplication Profiles? =
Profiles allow you to save custom duplication settings:
- which elements to copy  
- naming format  
- counter rules  
- date/time formatting  
- template and taxonomy behavior  

You can create multiple profiles and choose one before duplication.

= Can I use WP-CLI to duplicate posts? =
Yes! Example:

`wp rb-duplicate-post duplicate 10,22,45`

List profiles:

`wp rb-duplicate-post profiles`

= Which elements can be copied? =
Everything you need:
- Title  
- Content  
- Status  
- Date  
- Excerpt  
- Slug  
- Template  
- Featured image  
- Author  
- Taxonomies  
- Categories  
- Tags  
- Menu order  
- Password  
- Format  
- Attachments  
- Children  
- Navigation menu  
- All Post Meta

= Does the plugin support WordPress Multisite? =
Yes. On a network every site copies its own posts as on a single site. With PRO you can also copy posts from one site of the network to another: the copy dialog gets a Site choice, WP-CLI takes `--target-blog`, and Auto copy rules can copy new posts to another site. Settings → Multisite decides whether images get their own files on the target site (the default) or keep pointing at the source site's media library. Users see only the sites where they may publish; super admins see every site.

= Can I change how duplicated posts are named? =
Yes. Use prefix, suffix and placeholders:
`[Counter] [CurrentDate] [CurrentTime]`

= Is the plugin lightweight? =
Yes. It's optimized for speed and does not affect admin performance.

== Screenshots ==

1. Elements to Copy – flexible toggles for selecting which post elements will be duplicated.
2. Profiles List – overview of saved duplication profiles.
3. Add New Profile – create a custom profile with your preferred settings.
4. Naming Rules – create naming formats using [Counter], [CurrentDate], [CurrentTime].
5. Quick Copy popup – choose how many copies to create, select a profile, and duplicate posts or pages instantly.
6. WP-CLI Output – duplicating post IDs through command line.

== Changelog ==

= 2.5.0 (27-10-2026) =
* PRO: WordPress Multisite — copies between the sites of a network, from the copy dialog, WP-CLI (--target-blog) and Auto copy; Settings → Multisite
* New: images placed in the text of a copy on another site are copied there too and the text points at them (Settings → Multisite → Images in the text)
* New: a welcome screen after install and after this update: what is new, and RB Links Redirection installed in one click
* New: Integrations is a view of its own next to Settings; Cleanup can find posts with the same content
* Improved: the copy dialog lists what PRO adds, and its Copy button stays in view in the expanded dialog
* Improved: in the block editor, Copy saves the post first; when a post can't be copied, the dialog says why
* Improved: redirects for removed duplicates are now opt-in, in Find duplicates and in Auto cleanup
* Improved: profile names up to 40 letters, digits and spaces; title tags accept spaces and any letter case ([ CurrentDate ], [counter]); no more than 100 copies at a time
* Security: copies follow the user's rights on the site they are made on: a copy goes live only for a user who may publish there, keeps another author only for a user who may edit others' posts, and a profile's source post must be one the user may edit or read in full
* Security: on a network, the posts to copy are checked on the site they come from, with the user's role there
* Security: Assistant steps may change protected custom fields only when saved by an administrator; an AI answer is never taken as trusted markup
* Security: Rewrite & Republish goes into its original only for a user who may republish it, also on schedule; a copied rewrite mark makes no rewrite
* Fixed: a page with its children copied to another site of a network: the children are made there, under the page's copy; a post among its own descendants is not copied again
* Fixed: copies made without a user (Auto copy, WP-CLI) and scheduled Rewrite & Republish lost the original's embeds, forms and scripts; on a network, Auto copy and WP-CLI could not copy between sites
* Fixed: uninstall removes the plugin's options and scheduled events on every site of a network; deleting the PRO key keeps its settings for a reinstall
* Fixed: Yoast SEO data was not copied (Yoast was taken as inactive), and its switch in Settings → Integrations was not used
* Fixed: the primary category of Yoast SEO and Rank Math on a copy whose categories are copied, and on another site of a network
* Fixed: the copy history of a copy on another site of a network was written on the wrong site
* Fixed: media "link" mode across sites could set an unrelated post as the copy's featured image
* Fixed: Jetpack's "already shared" marks were carried over to copies made with All post meta
* Fixed: an empty "RB Plugins" menu item for super admins of a network
* Fixed: PHP 8.4 deprecation notices

= 2.4.0 (11-10-2026) =
* PRO: AI in the Assistant — rewrite, translate, and write the excerpt, SEO title or SEO description of every copy with Claude, ChatGPT or Grok and your own API key (stored encrypted, never sent to the browser). Templates: "A unique text with AI", "A translation draft with AI"
* PRO: Auto copy — rules that copy a post as soon as it is first published, with a profile, to this site or another site of the network; made in the background
* New: Settings → AI and Settings → Auto copy
* Fixed: since 2.3.0 the Copy button and Template source tabs of a profile showed the wrong options
* Improved: settings screen - Select all and Clear for the elements to copy, custom fields in their own group, the "AI Assistant" tab with tips, the PRO and RoboSoft panel above the settings can be collapsed

= 2.3.0 (11-10-2026) =
* New: the Assistant — every profile can run steps on its copies: replace a text (exactly, any letter case or whole words) in the title, content and excerpt, change link addresses, set the status. Ready-made templates: a landing page for another city, a copy for another domain, a product variant, a clean draft
* New: the Assistant asks questions when you copy ("Which city?") and uses the answers as {name} in its steps; {title}, {date} and {site} are always there
* New: "Try it on a post" shows what a copy would look like — nothing is copied
* New: WP-CLI --answer=name=value for the Assistant's questions
* PRO: Assistant steps for patterns (regular expressions), custom fields (set, replace, remove) and terms (add, remove, set) (Duplicate Post RB — Key 2.3.0)
* Fixed: the result of a cleanup stayed hidden when no duplicates were left

= 2.2.0 (11-10-2026) =
* New: works with our free RB Links Redirection plugin — a new Cleanup tab lists the redirects made for removed duplicates
* PRO: Cleanup with redirects — every removed published duplicate redirects (301) to the post that stays, so links and search results keep working; for the manual cleanup and the auto cleanup (Duplicate Post RB — Key 2.2.0)
* Dev: action rb_duplicate_post_before_remove

= 2.1.0 (11-10-2026) =
* New: Rewrite & Republish — edit a draft of a live post and publish it into the original: same address, date, author and comments. Works in the block editor, the classic editor and on schedule. SEO fields (Yoast SEO, Rank Math, All in One SEO), custom fields and, with PRO, WooCommerce data come back into the original
* New: Cleanup — find posts with the same title (or title and content), oldest first, and move the extra ones to the trash or delete them; every removal is in the log
* PRO: Auto cleanup — remove duplicate drafts on a schedule, per content type (Duplicate Post RB — Key 2.1.0)
* PRO: "Copy as" — copy a page as a post, or a post as a page
* PRO: WooCommerce products can be rewritten and republished: the product keeps its SKU, sales and variation IDs, prices and text are updated
* Improved: the log names a removed post by its title

= 2.0.0 (10-10-2026) =
* New: settings screen rebuilt without React — Copy profiles and Settings views, hints, FAQ, faster to load
* New: Copy dialog rebuilt — "Advanced settings" chooses the elements of one copy without changing the profile
* New: "After copying" profile option — stay in the list, open the copy for editing, or choose each time
* New: copy the comments of a post, with their replies (Elements to copy → Comments)
* New: "Copy of <original>" next to copies in the posts lists; the History box shows only on copies
* New: Log tab — filter copies by action and date, export to CSV, clear the log
* New: WP-CLI tab with ready-to-use commands
* PRO: WooCommerce products (prices, stock, variations, unique SKU), Elementor, WPBakery and Jetpack data in copies (Duplicate Post RB — Key 2.0.0)
* Fixed: copied custom fields with backslashes (JSON layouts, escaped text) lost their backslashes
* Dev: filter rb_duplicate_post_after_copy_transformers for extra copy steps

= 1.6.8 (25-09-2026) =
- Improved: SEO fields are now copied when duplicating posts
- New: Added support for Yoast SEO
- Improved: Added compatibility with WordPress 7.1

= 1.6.7 (13-07-2026) =
* Improved: Custom Profile buttons functionality updated
* New: Added ability to specify a template source post/page for Custom Profile
* New: new items copy content from the selected template

= 1.6.2 (27-05-2026) =
* Wordpress 7.0 compatibility 

= 1.6.1 (27-03-2026) =
* Added full Advanced Custom Fields (ACF) integration (fields, field groups, post types, taxonomies)
* Improved support for custom post types across different plugins and themes
* Added import/export functionality for duplication profiles
* Added profile-based custom buttons in post/page lists
* Added option to exclude specific custom post types from duplication
* Added option to duplicate content without confirmation (instant copy)
* Improved integration handling for third-party plugins

= 1.5.8 (17-01-2026) =
* Updated duplication dialog with a refreshed and more user-friendly interface
* Added new field to define the number of copies to create
* Updated import/export functionality for duplication profiles

= 1.5.7 (13-01-2026) =
* Added global All Post Meta option for copying all post meta at once
* Fixed and improved Custom Taxonomies duplication
* Improved stability of duplication options (Featured Image, Attachments, Categories, Taxonomies)
* Added new Copy button in the Gutenberg editor
* Added duplication history widget for Gutenberg and Classic editors
* Added source post and duplication profile details for duplicated posts

= 1.5.6 =
* Updated Gutenberg block with new configuration options
* Added in-block duplication settings for Gutenberg editor
* Fixed UI issues in the admin settings section
* Fixed access control and permissions handling in global settings

= 1.5.5 =
* Added new global Settings section for managing duplication behavior and access
* Added user role permissions for content duplication
* Added controls to manage where Copy/Duplicate actions are displayed in the admin UI
* Added duplication support directly inside the editor and Gutenberg
* Improved admin UI structure and navigation

= 1.5.4 =
* Wordpress 6.9 compatibility 

= 1.5.3 =
* Fixed the Profiles menu display in the admin section
* Added new WP-CLI command with full profile support

= 1.5.2 =
* Added hierarchy copy for parent/child pages
* Implemented bulk duplication option in bulk list actions

= 1.5.1 =
* Initial release of the advanced Duplicate Post RB
* Added duplication profiles
* Added flexible copy-element settings
* Added naming rules with placeholders
* Added Quick Copy modal window
* Added WP-CLI integration
* Improved clone & new draft logic
* Optimized performance and UI

= 1.0.10 =
* Duplicate post RB - Wordpress 6.8 compatibility 

= 1.0.9 =
* Duplicate post RB - Wordpress 6.7 compatibility 

= 1.0.8 =
* Duplicate post RB - Wordpress 6.5 compatibility 

= 1.0.7 =
* Duplicate post RB - Wordpress 6.2 compatibility 

= 1.0.6 =
* Changed default plugin options settings, changed description

= 1.0.5 =
* Duplicate post RB - Wordpress 6.1 compatibility 

= 1.0.4 =
* Duplicate post RB - Wordpress 6.0 compatibility 

= 1.0.3 =
* Duplicate post RB - Wordpress 5.9 compatibility 

= 1.0.2 =
* Added new menu options.

= 1.0.0 =
* First release of the Duplicate Post plugin.

== Upgrade Notice ==

= 2.5.0 =
The biggest update since 1.0: a faster settings screen, Rewrite & Republish, duplicate Cleanup, the Assistant for copies, copying of comments, Multisite and security fixes. Your profiles, settings and copy history are kept.
