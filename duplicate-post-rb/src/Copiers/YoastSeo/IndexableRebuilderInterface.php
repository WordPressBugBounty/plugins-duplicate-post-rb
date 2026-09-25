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
 * Adapter contract for invalidating/rebuilding a Yoast SEO "indexable" for
 * a given post ID.
 *
 * This is the one part of Yoast's API that genuinely differs between
 * versions/editions (the "indexables" cache architecture was introduced in
 * Yoast SEO 14.x) — isolating it behind this interface keeps
 * YoastSeoCopier itself version-agnostic; see CurrentIndexableRebuilder
 * and LegacyIndexableRebuilder.
 */
interface IndexableRebuilderInterface {

    /**
     * Forces the indexable for the given post to be rebuilt on next access.
     *
     * Callers are responsible for having already switched to the correct
     * blog (see YoastSeoCopier::rebuild_target_indexable()) — this method
     * does not itself touch Multisite blog context.
     *
     * @param int $post_id
     * @return void
     */
    public function rebuild( int $post_id ): void;
}
