=== Duplicate Post - duplicate pages, copy content, clone posts ===
Contributors: rbplugins
Tags: duplicate posts, duplicate pages, copy post, copy pages, duplicate post
Requires at least: 3.1
Tested up to: 6.9
Stable tag: 1.5.8
License: GPLv2 or later

Duplicate Post RB makes it easy to duplicate posts, pages and custom post types. Create duplicate posts, clone content, automate duplication

== Description ==

**Duplicate Post RB** is a powerful yet lightweight plugin that allows you to **duplicate posts, duplicate pages, and clone custom post types** in WordPress.  
Whether you want to **copy posts**, **clone pages**, or **generate multiple content variations**, this plugin provides flexible and professional tools for content duplication.

Unlike basic duplicate post tools, Duplicate Post RB gives you full control over what exactly gets copied, including content, title, slug, featured image, taxonomies, templates, metadata, and more. This makes it ideal for websites with structured content, landing pages, learning systems, and large editorial workflows.

The new version introduces advanced duplication logic with configuration profiles and WP-CLI support - making Duplicate Post RB suitable for editors, agencies, and developers who need full control over how content is copied.

Duplicate Post RB now offers both profile-based duplication logic and global plugin settings, making it suitable for editors, administrators, agencies, and developers working with complex WordPress setups.

### ⭐ What's New?

- **New global option: All Post Meta**
You can now copy all post meta fields at once using a single global switch. This significantly simplifies duplication when working with complex posts, custom fields, and third-party plugins.

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
  - site selection (multisite support coming soon)
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

### ⚙️ Global Settings

The new Global Settings section allows you to control how duplication features behave across your WordPress installation. From a single settings screen, you can define which user roles are allowed to duplicate content, including Super Admins, Administrators, Editors, Authors, and Contributors. This makes it easy to align duplication permissions with your team structure and editorial responsibilities.

In addition, you can choose where the Copy / Duplicate actions are displayed within the WordPress admin interface. The plugin allows you to enable duplication buttons on posts and pages lists, individual edit screens, the admin bar, inside the Gutenberg editor, and within the bulk actions menu. These options give you full flexibility to integrate duplication tools exactly where your workflow requires them.

Overall, the Global Settings section helps tailor Duplicate Post RB to different editorial processes, team sizes, and usage scenarios without adding unnecessary complexity.

Duplicate Post RB now includes an option to move the plugin menu to the Tools section.

This is useful if you want to: keep the WordPress admin menu cleaner, reduce visual clutter, or hide the plugin from the primary navigation while keeping it accessible.

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

= Does the plugin support multisite? =
Basic support is available. Advanced multisite duplication (cross-site copying) is coming soon.

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
* Updated duplication dialog with a refreshed and more user-friendly interface
* Added new field to define the number of copies to create
* Updated import/export functionality for duplication profiles

