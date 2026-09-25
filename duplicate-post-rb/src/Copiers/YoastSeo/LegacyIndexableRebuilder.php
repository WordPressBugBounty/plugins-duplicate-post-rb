<?php
/* 
*      RB Duplicate Post     
*      Version: 1.6.8
*      By RbPlugin
*
*      Contact: https://robosoft.co 
*      Created: 2025
*      Licensed under the GPLv3 license - http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace rbDuplicatePost\Copiers\YoastSeo;

defined( 'WPINC' ) || exit;

/**
 * Indexable rebuild for Yoast SEO versions predating the "indexables"
 * architecture (pre-14.x).
 *
 * Those versions have no separate denormalized cache table — SEO data is
 * read directly from postmeta on every request — so there is nothing to
 * invalidate or rebuild. Intentionally a no-op; kept as an explicit class
 * (rather than skipping the call entirely) so the version split stays
 * visible and documented at the call site in YoastSeoCopier.
 */
class LegacyIndexableRebuilder implements IndexableRebuilderInterface {

    public function rebuild( int $post_id ): void {
        // Intentionally empty — see class docblock.
    }
}
