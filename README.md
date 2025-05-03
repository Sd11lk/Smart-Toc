# Smart-Toc
Smart TOC is a simple WordPress plugin that auto-generates a Table of Contents from post headings (&lt;h2>–&lt;h6>). It adds anchor links for easy navigation, improving UX and SEO. Lightweight, clean, and easy to use—just install and activate.

=== Smart TOC - Auto Table of Contents ===
Contributors: Savio Dcruz
Tags: table of contents, toc, navigation, headings, content, indexing
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Automatically generate a Table of Contents for your posts based on headings with smooth styling and advanced customization options.

== Description ==

**Smart TOC** makes it easy to add a professional Table of Contents to your WordPress posts and pages. Improve your content navigation, boost SEO, and enhance user experience by helping readers quickly find what they're looking for.

### Key Features

* **Automatic TOC Generation**: Analyzes your content and generates a Table of Contents based on headings (H1-H6)
* **Smart Positioning**: Choose where to display the TOC (top of page, before first heading, after first paragraph)
* **Hierarchical Display**: Shows nested headings in a clean, hierarchical structure
* **Collapsible Display**: Allows readers to show/hide the TOC as needed
* **Smooth Scrolling**: Offers smooth animated scrolling to headings when clicked
* **Minimum Heading Threshold**: Only shows TOC when a minimum number of headings are present
* **Heading Level Selection**: Choose which heading levels to include in your TOC
* **Per-Post Control**: Enable or disable the TOC on specific posts/pages
* **Shortcode Support**: Manually place the TOC anywhere with a simple shortcode
* **Visual Editor Button**: Easily insert the TOC shortcode with a TinyMCE button
* **Responsive Design**: Looks great on all devices from mobile to desktop
* **Custom CSS Support**: Add your own styling to perfectly match your theme
* **Accessibility Friendly**: Follows best practices for screen readers and keyboard navigation

### Perfect For
* Long-form content
* Tutorial and how-to articles
* Documentation pages
* Educational content
* Product reviews
* Any content with multiple sections

### Why Add a Table of Contents?
* **Improved User Experience**: Helps readers quickly navigate to sections they're interested in
* **Reduced Bounce Rate**: Keeps readers engaged by making content more accessible
* **SEO Benefits**: Can help generate rich snippets in search results
* **Better Content Structure**: Encourages better organization of your content

== Installation ==

### Automatic Installation
1. Log in to your WordPress admin panel and navigate to "Plugins > Add New".
2. Search for "Smart TOC".
3. Click "Install Now" and then "Activate".

### Manual Installation
1. Download the plugin zip file.
2. Log in to your WordPress admin panel and navigate to "Plugins > Add New".
3. Click "Upload Plugin" and select the downloaded zip file.
4. Click "Install Now" and then "Activate".

### After Installation
1. Go to "Settings > Smart TOC" to configure the plugin.
2. Choose your preferred settings for TOC position, heading levels, and styling.
3. Start creating or editing content with headings to see the TOC in action!

== Frequently Asked Questions ==

= Does the TOC work with Gutenberg editor? =
Yes, Smart TOC works seamlessly with both the Gutenberg block editor and the classic editor.

= Can I customize the appearance of the TOC? =
Absolutely! You can customize the TOC through the settings page using custom CSS. The plugin includes a built-in field for adding your custom styles.

= Can I place the TOC manually instead of automatically? =
Yes, you can disable automatic insertion and use the `[smart_toc]` shortcode to place the TOC exactly where you want it.

= Will the TOC work with my theme? =
Smart TOC is designed to work with any properly coded WordPress theme. The plugin uses its own styling that adapts to your theme's design.

= Does the TOC support all languages? =
Yes, Smart TOC works with any language supported by WordPress, including RTL languages.

= What if I don't want the TOC on a specific post? =
You can disable the TOC for individual posts or pages through a meta box in the editor.

= Can I change the TOC title? =
Yes, you can customize the title of the TOC from the settings page.

== Screenshots ==

1. Example of the Smart TOC in action on a post
2. Plugin settings page
3. Editor meta box for per-post control
4. TinyMCE button for shortcode insertion
5. TOC with hierarchical display
6. TOC on mobile devices

== Changelog ==

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release of Smart TOC plugin.

== Additional Information ==

= Custom CSS Examples =

Want to customize your TOC? Here are some CSS examples:

**Change the TOC border color and style:**
```css
.smart-toc-container {
    border: 2px solid #336699;
    border-radius: 10px;
}
```

**Customize the TOC title:**
```css
.smart-toc-title {
    font-size: 22px;
    color: #d54e21;
    font-family: Georgia, serif;
}
```

**Change list item styles:**
```css
.smart-toc-item a {
    color: #0073aa;
    font-size: 16px;
    padding: 4px 0;
}

.smart-toc-item a:hover {
    background-color: #f0f0f0;
    padding-left: 5px;
}
```

= Shortcode Usage =

Basic usage:
`[smart_toc]`


-Signed
_____  _     ____  _____ _  _      ____  _____  ____  ____
/__ __\/ \ /\/  __\/  __// \/ \  /|/  _ \/__ __\/  _ \/  __\
 / \  | | |||  \/|| |  _| || |\ ||| / \|  / \  | / \||  \/|
 | |  | \_/||    /| |_//| || | \||| |-||  | |  | \_/||    /
 \_/  \____/\_/\_\\____\\_/\_/  \|\_/ \|  \_/  \____/\_/\_\
