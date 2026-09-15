<?php declare(strict_types = 1);

namespace TheSaiged\Sections;

/**
 * Marker for sections whose own background is a fullpage image, dark
 * enough for the site header's transparent, white-text overlay state to
 * read correctly on top of it. Page::hasFullPageHero() checks this
 * against the first section only — Layout uses it to decide whether the
 * header overlays the page (fixed, transparent) or sits inline above it
 * (sticky, solid, taking up its own space), rather than every section
 * needing an explicit background-kind field.
 */
interface FullPageImageSection {}
