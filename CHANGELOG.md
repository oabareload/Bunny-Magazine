# Changelog

All notable changes to Bunny Magazine are documented in this file.

## 1.1.0

### Added

- Affiliate Top Posts content source.
- WP Affiliate Manager integration (soft dependency).
- Period selector for Affiliate Top Posts.
- Extensible Query Provider architecture.

### Improved

- Query settings management.
- Editor controls adapt dynamically to the selected content source.
- Provider-specific query normalization.

### Fixed

- Settings persistence between Cache Settings and Query Settings.
- Automatic cache invalidation when Query Settings change.
- Editor interaction and block selection improvements.

## 1.0.0

- Initial release: editorial Gutenberg layouts (Hero, Carousel, Magazine, Grid, Mosaic, Category Showcase).
- Latest Posts, Latest Updated Posts, and Random Posts content sources.
- Category, tag, and author query filters.
- Transient-based query caching with configurable TTL and manual flush.
- Automatic layout discovery via the Layout Registry.
