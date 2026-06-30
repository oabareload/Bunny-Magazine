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

| Source               | Description                   |
| -------------------- | ----------------------------- |
| Latest Posts         | Most recently published posts |
| Latest Updated Posts | Recently modified content     |
| Random Posts         | Randomized post selection     |

The query system is provider-based and can be extended by third-party integrations.

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

Filter visibility can be controlled globally from the Query Settings screen.

---

### Cache System

Bunny Magazine includes a built-in cache layer designed to reduce unnecessary queries.

Features:

* Request-level cache reuse
* Persistent transient caching
* Automatic invalidation on post updates
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
Content Provider
        ↓
Cache Manager
        ↓
Layout Engine
        ↓
Render Template
```

### Query Manager

Responsible for:

* Query normalization
* Provider dispatching
* Cache integration
* Content retrieval

### Content Providers

Built-in providers:

* Latest Posts
* Latest Updated Posts
* Random Posts

Additional providers can be registered through filters.

### Layout Engine

Responsible only for rendering.

Layouts never execute queries directly.

They receive:

* Posts
* Attributes
* Context

And return HTML.

### Layout Registry

Automatically scans:

```text
layouts/*
```

and registers available layouts without manual configuration.

---

## Repository Structure

```text
Bunny-Magazine/
├── README.md
└── wp_bunnymagazine/
    ├── admin/
    ├── assets/
    ├── blocks/
    ├── core/
    ├── layouts/
```

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

## Admin Screens

### Dashboard

General plugin information.

### Layouts

Displays registered layouts and layout scan information.

### Query Settings

Controls which query filters are available inside Gutenberg.

Available settings:

* Category Filters
* Tag Filters
* Author Filters

### Cache

Cache management:

* Enable / Disable cache
* Cache TTL
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
