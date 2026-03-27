=== Duplicate Post - duplicate pages, copy content, clone posts ===
Contributors: rbplugins
Tags: duplicate posts, duplicate pages, copy post, copy pages, duplicate post
Requires at least: 3.1
Tested up to: 6.9
Stable tag: 1.6.1
License: GPLv2 or later

Duplicate Post RB makes it easy to duplicate posts, pages and custom post types. Create duplicate posts, clone content, automate duplication

== Description ==

[📄 Details](https://rbplugins.com/wordpress-plugins/duplicate-post-rb/) | [🚀 Get PRO Version](https://rbplugins.com/wordpress-plugins/duplicate-post-rb/#pricing) | [📖 Documentation](https://rbplugins.com/docs/duplicate-post-rb/)

**Duplicate Post RB** is a powerful yet lightweight plugin that allows you to **duplicate posts, duplicate pages, and clone custom post types** in WordPress.  
Whether you want to **copy posts**, **clone pages**, or **generate multiple content variations**, this plugin provides flexible and professional tools for content duplication.

Unlike basic duplicate post tools, Duplicate Post RB gives you full control over what exactly gets copied, including content, title, slug, featured image, taxonomies, templates, metadata, and more. This makes it ideal for websites with structured content, landing pages, learning systems, and large editorial workflows.

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

= 1.6.1 (27-03-2026) =
* Added full Advanced Custom Fields (ACF) integration (fields, field groups, post types, taxonomies)
* Improved support for custom post types across different plugins and themes
* Added import/export functionality for duplication profiles
* Added profile-based custom buttons in post/page lists
* Added option to exclude specific custom post types from duplication
* Added option to duplicate content without confirmation (instant copy)
* Improved integration handling for third-party plugins
