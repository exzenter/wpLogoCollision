=== Logo Collision ===
Contributors: wpmitch - exzent.de
Tags: animation, scroll, gsap, header, logo, responsive
Requires at least: 5.0
Tested up to: 6.9
Stable tag: 1.1.0
Version: 1.1.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Apply context-aware scroll animations to your WordPress header logo when it would collide with scrolling content.

== Description ==

Context-Aware Animation is a WordPress plugin that automatically detects when your header logo would collide with scrolling content and applies one of seven beautiful animation effects to move it out of the way.

**Features:**

* 7 different animation effects to choose from: Inspired by Codrops. MIT License applies to that Code parts.
  * Scale - Scales down and hides, then scales up and shows
  * Blur - Applies blur effect and scales
  * Slide Text - Slides text up and down
  * Text Split - Splits text into characters and scatters them
  * Character Shuffle - Shuffles characters before revealing
  * Rotation - Rotates and moves simultaneously
  * Move Away - Moves horizontally off-screen

* Automatic content detection - Works with common WordPress content selectors
* Excluded elements - Specify elements that should not trigger animations
* Easy configuration through WordPress admin settings
* Powered by GSAP (GreenSock Animation Platform) for smooth, performant animations
* **Export/Import Settings** - Backup your entire configuration to a JSON file and restore it anytime. Perfect for site migrations, staging to production deployments, or upgrading from Free to Pro without losing any settings!

**Pro Features:**

* **Responsive Viewport Settings** - Configure different animation settings for Desktop, Tablet, and Mobile viewports. Each viewport can have its own Duration, Easing, and Scroll Offsets with automatic fallback inheritance (Mobile → Tablet → Desktop). [Pro Feature - NEW in 1.1.0]
* Multiple Instances - Create up to 10 instances to animate different elements with separate settings. Each instance can have its own logo selector, effect, animation settings, and page filtering rules. [Pro Feature]
* Element Effect Mappings - Map specific elements to different effects. When the logo collides with these elements, the mapped effect will be used instead of the global default. Perfect for applying different effects to different sections of your page (e.g., scale for hero section, blur for portfolio, rotation for testimonials). [Pro Feature]
* Page Filtering - Control where the plugin runs with advanced filtering options. Include or exclude specific post types, all pages, all posts, or individual items. Choose between include mode (run only on selected pages) or exclude mode (run everywhere except selected pages). [Pro Feature]

== Installation ==

1. Upload the `logo-collision` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > Context-Aware Animation to configure the plugin
4. Enter your header logo CSS selector (e.g., `#site-logo` or `.logo`)
5. Select your preferred animation effect
6. Optionally add excluded elements that should not trigger animations

== Frequently Asked Questions ==

= What CSS selector should I use for my logo? =

Use the same selector you would use in CSS to target your logo. Common examples:
* `#site-logo` - if your logo has an ID
* `.logo` - if your logo has a class
* `#header-logo` - another common ID
* `header .site-branding img` - for more specific targeting

= Which effect should I choose? =

All effects work well, but some are better for different scenarios:
* **Scale** - Subtle and professional
* **Blur** - Modern and smooth
* **Slide Text** - Works best with text logos
* **Text Split** - Dramatic effect for text logos
* **Character Shuffle** - Playful effect for text logos
* **Rotation** - Dynamic and eye-catching
* **Move Away** - Simple and clean

= How do I exclude elements? =

Enter comma-separated CSS selectors in the "Excluded Elements" field. For example:
`#sidebar, .widget, .navigation, footer`

= How do I backup my settings or migrate to a new site? =

Use the Export/Import feature in General Settings. Click "Export Settings" to download a JSON file containing all your configurations. On the new site or after upgrading to Pro, simply import this file to restore all your settings instantly.

= Does this work with all themes? =

The plugin automatically detects common WordPress content areas. If your theme uses non-standard selectors, you may need to add custom CSS or modify the plugin's content detection.

= Does this plugin slow down my theme? =

No, this plugin does not slow down your theme. The required libraries (GSAP and ScrollTrigger) are only loaded if the effect is actually in use on the current page. These libraries are tiny and optimized for performance. A sample page with all effects enabled achieves a perfect 100 performance score on Google Lighthouse.

== Screenshots ==

1. Settings page with effect selection
2. Animation in action

== Changelog ==

= 1.1.0 =
* NEW: Responsive Viewport Settings (Pro) - Configure separate animation settings for Desktop, Tablet, and Mobile
* Viewport Switcher UI with Desktop/Tablet/Mobile toggle buttons
* Per-viewport Duration, Easing, Start Offset, and End Offset settings
* Automatic fallback inheritance: Mobile inherits from Tablet, Tablet inherits from Desktop
* Global viewport breakpoint configuration (default: Tablet ≤782px, Mobile ≤600px)
* Override indicators show which fields have viewport-specific values

= 1.0.1 =
* Added Export/Import Settings - Backup your configuration to JSON and restore anytime
* Perfect for site migrations, staging deployments, and upgrading to Pro without losing settings
* Import is compatible between Free and Pro versions

= 1.0.0 =
* Initial release
* 7 animation effects
* Admin settings page
* Excluded elements support
* Automatic content detection

== Upgrade Notice ==

= 1.1.0 =
New Responsive Viewport Settings feature allows different animation configurations for Desktop, Tablet, and Mobile viewports (Pro feature).

= 1.0.1 =
New Export/Import feature lets you backup settings and seamlessly migrate between sites or upgrade to Pro without losing your configuration.

= 1.0.0 =
Initial release of Context-Aware Animation plugin.


