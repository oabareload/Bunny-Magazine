# Bunny Magazine

**Bunny Magazine** is a lightweight editorial layout system for WordPress and Gutenberg.

Unlike page builders, Bunny Magazine focuses on displaying existing content through reusable editorial layouts, shared query logic, and intelligent caching. Layouts are fully server-rendered, configurable through Gutenberg, and designed to remain fast, maintainable, and extensible.

---

## Features

### Editorial Layouts

Bunny Magazine includes multiple editorial layouts designed for magazines, blogs, news sites, affiliate projects, and content-heavy websites.

Included layouts:

* Hero
* Carousel
* Magazine
* Grid
* Mosaic
* Category Showcase

Each layout is automatically discovered from its own folder and registered without hardcoded definitions.

---

### Content Sources

Blocks can retrieve content using different built-in sources:

| Source                      | Description                                                           |
| ---------------------------- | ---------------------------------------------------------------------- |
| Latest Posts                | Most recently published posts                                          |
| Latest Updated Posts        | Recently modified content                                              |
| Random Posts                | Randomized post selection (not cached, by design)                      |
| Affiliate Top Posts         | Most clicked posts reported by WP Affiliate Manager (soft dependency)  |
| Affiliate Top Viewed Posts  | Most viewed posts reported by WP Affiliate Manager (soft dependency)   |
| Affiliate Top Scored Posts  | Highest-scored posts reported by WP Affiliate Manager (soft dependency) |

The query system is provider-based and can be extended by third-party integrations.

See [Content Sources](#content-sources-1) below for details on each one.

---

### Query Filters

Each block can optionally filter content using:

* Include Categories
* Exclude Categories
* Include Tags
* Exclude Tags
* Include Authors
* Exclude Authors
* Post Limit
* Content Source
* Period — only shown/applied when Content Source is **Affiliate Top Posts**, **Affiliate Top Viewed Posts**, or **Affiliate Top Scored Posts**

Filter visibility (category, tag, author) can be controlled globally from the Query Settings screen. A filter that is disabled there is also stripped server-side, so it can never reach a query even from a stale or manually crafted block.

---

### Cache System

Bunny Magazine includes a built-in cache layer designed to reduce unnecessary queries.

Features:

* Request-level cache reuse
* Persistent transient caching
* Automatic invalidation on post publish/update
* Automatic invalidation when Query Settings (filter visibility) change
* Manual cache flushing
* Configurable cache TTL
* Layout discovery cache

Random content sources bypass transient caching by design to preserve randomness.

---

## Architecture

Bunny Magazine follows a layered architecture:

```text
Gutenberg Block
        ↓
Block Registry
        ↓
Query Manager
        ↓
Query Provider
        ↓
Cache Manager
        ↓
Layout Engine
        ↓
Render Template
```

### Block Registry

Registers every Gutenberg block discovered by the Layout Registry, exposes block attributes (content source, filters, period, layout-specific controls), and resolves block attributes into the normalized config consumed by the Query Manager.

### Query Manager

The central, single entry point used by all callers. Responsible for:

* Normalizing raw block/config arrays into a canonical shape
* Resolving and dispatching to the correct Query Provider
* Enforcing globally disabled filters server-side (not just hiding them in the editor)
* Request-level and transient cache integration
* Safe fallback to Latest Posts for invalid, missing, or legacy content source values

### Query Providers

Each content source is implemented as a Query Provider behind a shared interface (`Query_Provider_Interface`). Providers receive a normalized config array and return `WP_Post[]`; they have no knowledge of layouts, blocks, or the editor.

Built-in providers:

* Latest Posts
* Latest Updated Posts
* Random Posts
* Affiliate Top Posts (soft dependency on WP Affiliate Manager)
* Affiliate Top Viewed Posts (soft dependency on WP Affiliate Manager)
* Affiliate Top Scored Posts (soft dependency on WP Affiliate Manager)

Additional providers can be registered through the `wpbm_content_sources` filter without modifying any core plugin file.

### Cache Manager

Owns plugin settings (cache + query/filter visibility), the transient cache version used to build cache keys, and the helpers that bump that version on publish, update, or Query Settings changes — invalidating stale entries.

### Layout Registry

Automatically scans `layouts/*` and registers available layouts (title, description, query defaults, controls, assets) without manual configuration.

### Layout Engine

Responsible only for rendering. Layouts never execute queries directly — they receive posts, attributes, and context, and return HTML.

### Gutenberg Blocks

One dynamic block per layout, registered with server-side rendering. The editor exposes Content Source, Filters, Period (when applicable), and layout-specific controls, all driven by data localized from PHP (`wpbmEditorLayouts`, `wpbmQueryConfig`).

---

## Repository Structure

```text
Bunny-Magazine/
├── README.md
├── CHANGELOG.md
├── Bunny-Magazine.code-workspace
└── wp_bunnymagazine/
    ├── wp_bunnymagazine.php          # Plugin bootstrap, autoloader, version constants
    ├── uninstall.php                 # Cleans up plugin options on uninstall
    │
    ├── admin/
    │   ├── class-admin.php           # Admin menu, settings registration, sanitization
    │   ├── class-admin-header.php    # Shared admin page header/tabs renderer
    │   └── views/
    │       ├── dashboard.php         # Status overview (version, layouts, cache)
    │       ├── layouts.php           # Detected layouts + rescan action
    │       ├── query.php             # Query Settings: filter visibility, content sources info
    │       └── cache.php             # Cache Settings: enable/TTL/invalidation/manual flush
    │
    ├── assets/
    │   ├── css/
    │   │   ├── admin.css
    │   │   ├── bunny-admin.css
    │   │   └── frontend.css
    │   └── js/
    │       └── editor.js             # Gutenberg editor controls for every registered block
    │
    ├── blocks/
    │   └── class-block-registry.php  # Registers blocks, builds attributes schema, renders blocks
    │
    ├── core/
    │   ├── class-plugin.php                               # Boot sequence, hooks, cache invalidation events
    │   ├── class-query-manager.php                        # Central query facade (see Architecture)
    │   ├── class-query-provider-interface.php              # Contract implemented by all providers
    │   ├── class-query-provider-latest-posts.php           # Latest Posts provider
    │   ├── class-query-provider-latest-updated-posts.php   # Latest Updated Posts provider
    │   ├── class-query-provider-random-posts.php           # Random Posts provider
    │   ├── class-query-provider-wpam-top-posts.php         # Affiliate Top Posts provider (soft dependency)
    │   ├── class-query-provider-wpam-top-viewed-posts.php  # Affiliate Top Viewed Posts provider (soft dependency)
    │   ├── class-query-provider-wpam-top-scored-posts.php  # Affiliate Top Scored Posts provider (soft dependency)
    │   ├── class-cache-manager.php                        # Settings storage + cache version/key helpers
    │   ├── class-layout-registry.php                      # Discovers and registers layouts
    │   ├── class-layout-engine.php                        # Renders layouts (presentation only)
    │   ├── class-utils.php                                # Shared sanitization helpers
    │   └── helpers.php
    │
    └── layouts/
        ├── hero/
        │   ├── layout.json
        │   ├── render.php
        │   ├── style.css
        │   └── editor.css
        ├── carousel/
        │   ├── layout.json
        │   ├── render.php
        │   ├── style.css
        │   ├── editor.css
        │   └── carousel.js
        ├── magazine/
        │   ├── layout.json
        │   ├── render.php
        │   ├── style.css
        │   └── editor.css
        ├── grid/
        │   ├── layout.json
        │   ├── render.php
        │   ├── style.css
        │   └── editor.css
        ├── mosaic/
        │   ├── layout.json
        │   ├── render.php
        │   ├── style.css
        │   └── editor.css
        └── category-showcase/
            ├── layout.json
            ├── render.php
            ├── style.css
            └── editor.css
```

> Note: `languages/` is referenced by the plugin's text domain loader but is created on demand when translation files are added; it is not part of the current repository contents.

---

## Layout Structure

Each layout lives in its own folder:

```text
layouts/hero/
├── layout.json
├── render.php
├── style.css
└── editor.css
```

### layout.json

Defines:

* Block title
* Description
* Query defaults
* Available controls
* Assets
* Layout schema

The registry automatically discovers layouts from these files.

---

## Content Sources

### Latest Posts

The most recently published posts, ordered by date descending. This is the default content source and the safe fallback used whenever a block's saved content source is missing, invalid, or unrecognized (e.g. a legacy block, or one referencing a provider that is no longer registered).

### Latest Updated Posts

Recently modified content, useful for surfacing posts that have been kept up to date rather than newly published.

### Random Posts

A randomized post selection. Random results are intentionally **not** written to the transient cache — only the in-memory, per-request cache applies — so a block rendered twice in the same page load doesn't run duplicate queries, but each new page load gets a fresh random set.

### Affiliate Top Posts

Available only when [WP Affiliate Manager](https://wordpress.org/plugins/wp-affiliate-manager/) is active. This is a **soft (optional) dependency**: if WP Affiliate Manager is not installed or active, the source simply returns no posts — no errors, no fatal, and no impact on any other content source.

Surfaces the `Period` selector in the editor:

* Today
* This Week
* This Month
* All Time

Also forwards category, tag, and author filters to `WPAM_API::get_top_posts()` directly — WP Affiliate Manager resolves them internally. Bunny Magazine does not interpret or transform those values.

Bunny Magazine relies only on the standard `WP_Post[]` shape returned by WP Affiliate Manager's API and does not depend on any of its plugin-specific post properties.

### Affiliate Top Viewed Posts

Identical behavior to Affiliate Top Posts, but powered by `WPAM_API::get_top_viewed_posts()`. Surfaces the most viewed posts (by page view count) rather than the most clicked.

Same soft dependency, same Period selector, same filter passthrough. Available only when WP Affiliate Manager is active.

### Affiliate Top Scored Posts

Identical behavior to the other WPAM providers, powered by `WPAM_API::get_top_scored_posts()`. Surfaces the highest-scored posts according to WP Affiliate Manager's internal scoring model.

Same soft dependency, same Period selector, same filter passthrough. Available only when WP Affiliate Manager is active.

---

## Query Configuration

Filters and controls available per block:

* **Content Source** — selects which Query Provider supplies the posts (Latest Posts, Latest Updated Posts, Random Posts, Affiliate Top Posts, Affiliate Top Viewed Posts, Affiliate Top Scored Posts).
* **Post Limit** — number of posts to retrieve, bounded by the layout's configured min/max.
* **Include Categories / Exclude Categories** — only shown and applied when category filters are enabled in Query Settings.
* **Include Tags / Exclude Tags** — only shown and applied when tag filters are enabled in Query Settings.
* **Include Authors / Exclude Authors** — only shown and applied when author filters are enabled in Query Settings (disabled by default).
* **Period** — only shown when Content Source is set to one of the three Affiliate sources (Top Posts, Top Viewed Posts, Top Scored Posts); accepts `day`, `week`, `month`, or `total`, defaulting to `week` for any missing or invalid value.

Category, tag, and author filter availability is controlled globally from the **Query Settings** admin screen. Disabling a filter type there hides it in the editor **and** strips it server-side from the query, so previously saved blocks cannot bypass it.

---

## Cache Behavior

* **Enable / disable** — the transient cache layer can be turned off entirely from the Cache settings screen; request-level (in-memory) caching always applies regardless.
* **TTL** — configurable cache lifetime in minutes (minimum 5).
* **Manual invalidation** — a "Clear cache now" action is available on the Cache screen.
* **Automatic invalidation on publish** — optional; bumps the cache version whenever a post is published.
* **Automatic invalidation on update** — optional; bumps the cache version whenever a previously published post is updated.
* **Automatic invalidation on Query Settings change** — whenever category/tag/author filter visibility actually changes value, the cache version is bumped automatically so any transient built under the old filter configuration is invalidated immediately.
* **Random Posts** are intentionally excluded from transient caching (see [Content Sources](#random-posts)).

---

## Gutenberg Features

* **Server Side Render** — every block previews using the same PHP render path used on the front end, so what you see in the editor matches the live output.
* **Content Sources** — a single selector exposes every registered content source (built-in and third-party) by querying the same provider registry used on the server.
* **Query Settings awareness** — the Filters panel in the editor only renders the filter controls (category, tag, author) that are currently enabled in Query Settings.
* **Dynamic controls per source** — additional controls adapt to the selected Content Source; for example, the Period selector (Today / This Week / This Month / All Time) appears only when an Affiliate source (Top Posts, Top Viewed Posts, or Top Scored Posts) is selected, and is hidden for every other source.

---

## Admin Screens

### Dashboard

General plugin information: current version, detected layout count, cache version, and cache enabled/disabled status.

### Layouts

Displays registered layouts and layout scan information, with a manual rescan action.

### Query Settings

Controls which query filters are available inside Gutenberg, and lists the built-in content sources.

Available settings:

* Category Filters
* Tag Filters
* Author Filters

### Cache

Cache management:

* Enable / Disable cache
* Cache TTL
* Invalidate on publish / update
* Manual cache flush

---

## Installation

1. Copy `wp_bunnymagazine` into:

```text
wp-content/plugins/
```

2. Activate the plugin from WordPress Admin.

3. Open Gutenberg.

4. Insert any Bunny Magazine block.

5. Configure Content Source, Filters, and Layout settings.

6. (Optional) Install and activate WP Affiliate Manager to enable the Affiliate Top Posts, Affiliate Top Viewed Posts, and Affiliate Top Scored Posts content sources.

---

## Extending Content Sources

Custom providers can be registered using:

```php
add_filter(
	'wpbm_content_sources',
	function (array $providers): array {

		$providers['my_source'] = new My_Content_Provider();

		return $providers;
	}
);
```

To expose the source in Gutenberg:

```php
add_filter(
	'wpbm_editor_content_sources',
	function (array $sources): array {

		$sources[] = [
			'value' => 'my_source',
			'label' => 'My Content Source',
		];

		return $sources;
	}
);
```

---

## Development Guidelines

* Keep layouts presentation-only.
* Keep queries inside Query Manager and Providers.
* Avoid direct WP_Query usage inside layouts.
* Prefer WordPress APIs over custom abstractions.
* Avoid jQuery.
* Load assets only when required.
* Optimize for performance and maintainability.

---

## What Bunny Magazine Is

* Editorial layout system
* Dynamic Gutenberg blocks
* Server-side rendered
* Query-driven
* Cache-aware
* Extensible

## What Bunny Magazine Is Not

* A page builder
* A visual drag-and-drop designer
* A theme framework
* An analytics platform
* A marketing automation tool

The goal is simple:

**Display WordPress content through fast, reusable, editorial layouts without the complexity of a page builder.**
