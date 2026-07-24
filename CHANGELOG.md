# Changelog

All notable changes to Bunny Magazine are documented in this file.

## 1.4.1

### Added

- **Profile Card – Image placeholder mode**: New `placeholderType` value `image` adds a third content visual mode alongside `none` and `grid`.
- New `placeholderImage` attribute (`string`, default `""`) stores the URL of an image selected from the WordPress Media Library.
- Native Gutenberg `MediaUpload` / `MediaUploadCheck` controls in the editor panel: select, replace, and remove an image without any external dependencies.
- `render.php`: when `placeholderType === 'image'` and a valid URL is present, renders a decorative `<img loading="lazy" decoding="async" alt="">` wrapped in an `aria-hidden="true"` container.
- `style.css`: `.wpbm-social-profile__image` uses `aspect-ratio: 16/9`, `object-fit: cover`, matching `border-radius`, and `overflow: hidden` to prevent CLS and maintain visual consistency with the grid placeholder.

### Notes

- 0 JavaScript on the frontend. No HTTP requests. No external libraries.
- Existing blocks with `placeholderType: grid` or `none` are fully unaffected.
- The image position respects the existing `placeholderPosition` setting (`before_description` / `after_description`).

## 1.4.0

### Added

- New **Profile Card** (`social-profile`) layout component for displaying social media, creator, affiliate, and partner profiles.
- PHP Icon Library (`core/icons/`) featuring 10 lightweight, inline SVG icons (`instagram`, `facebook`, `youtube`, `tiktok`, `x`, `discord`, `patreon`, `kofi`, `website`, `generic`) using `currentColor`.
- Flexible layout controls:
  - `styleVariant`: Brand colors (official brand backgrounds/hovers) or Theme style (inheriting site colors).
  - `alignment`: Left or Center alignment for card content and header.
  - `placeholderType` & `placeholderGridSize`: Pure CSS post placeholders with 5 fixed grid dimensions (`2x2`, `2x3`, `3x3`, `3x4`, `3x5`).
  - `placeholderPosition`: Position grid `before_description` or `after_description`.
  - Header & Button toggles: Independent visibility for name, username, description, and button.
  - Native WordPress `URLInput` control integration in Gutenberg.

### Improved

- `Block_Registry` and `Layout_Registry` updated to support `static` layout types, bypassing unnecessary `WP_Query` database execution.
- Flexbox equal height card layout (`height: 100%`) with bottom-aligned action button (`margin-top: auto`).
- Guaranteed 0 JavaScript files enqueued on the frontend for static layouts.

## 1.3.0

### Added

- Affiliate Top Scored Posts content source (`wpam_top_scored_posts`).
- WP Affiliate Manager `get_top_scored_posts()` integration (soft dependency).
- Period selector support for Affiliate Top Scored Posts (Today, This Week, This Month, All Time).
- Full filter passthrough (categories, tags, authors include/exclude) for Affiliate Top Scored Posts.

### Improved

- Period selector in the editor now activates for all three WPAM sources (Top Posts, Top Viewed Posts, Top Scored Posts).
- Content Sources list in Query Settings admin screen updated to include Affiliate Top Scored Posts.
- `editor.js` fallback source list updated to include Affiliate Top Scored Posts.
- README and CHANGELOG updated to document all current providers and sources.

## 1.2.0

### Added

- Affiliate Top Viewed Posts content source (`wpam_top_viewed_posts`).
- WP Affiliate Manager `get_top_viewed_posts()` integration (soft dependency).
- Period selector support for Affiliate Top Viewed Posts (Today, This Week, This Month, All Time).
- Filter passthrough for Affiliate Top Posts: categories, tags, and authors include/exclude now forwarded directly to `WPAM_API::get_top_posts()`.
- Filter passthrough for Affiliate Top Viewed Posts: same passthrough applied consistently to the new provider.

### Improved

- Content Sources list in Query Settings admin screen updated to reflect both WPAM providers.
- `editor.js` fallback source list corrected to include `wpam_top_posts` (previously missing, caused manual workaround).
- Period selector condition in the editor extended to cover both WPAM sources.
- README updated to document filter passthrough behavior and both WPAM providers.

## 1.1.0

### Added

- Affiliate Top Posts content source (`wpam_top_posts`).
- WP Affiliate Manager `get_top_posts()` integration (soft dependency).
- Period selector for Affiliate Top Posts (Today, This Week, This Month, All Time).
- Extensible Query Provider architecture (`Query_Provider_Interface`).

### Improved

- Query settings management.
- Editor controls adapt dynamically to the selected content source.
- Provider-specific query normalization.

### Fixed

- Settings persistence between Cache Settings and Query Settings tabs.
- Automatic cache invalidation when Query Settings change.
- Editor interaction and block selection improvements.

## 1.0.0

- Initial release: editorial Gutenberg layouts (Hero, Carousel, Magazine, Grid, Mosaic, Category Showcase).
- Latest Posts, Latest Updated Posts, and Random Posts content sources.
- Category, tag, and author query filters.
- Transient-based query caching with configurable TTL and manual flush.
- Automatic layout discovery via the Layout Registry.
