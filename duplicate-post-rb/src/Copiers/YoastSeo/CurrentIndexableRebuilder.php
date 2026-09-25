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
 * Indexable rebuild for Yoast SEO's "indexables" architecture
 * (Yoast\WP\SEO\Repositories\Indexable_Repository and related classes,
 * introduced in Yoast SEO 14.x and present in current versions).
 *
 * Rather than calling Yoast's internal Indexable_Builder directly — its
 * exact method signatures are not public API and have changed across
 * releases — this deletes the existing cached indexable row for the post,
 * if one exists. Yoast's own repository is designed to auto-build a fresh
 * indexable from current postmeta the next time anything asks for this
 * post's indexable (a front-end view, an admin edit-screen load, a
 * sitemap build, a REST 'yoast_head_json' request, etc). This keeps the
 * copier decoupled from Yoast's internal Builder implementation, which is
 * exactly the kind of detail we agreed to confirm/adjust on a real test
 * bench rather than hard-lock into this class.
 *
 * If Yoast's DI container or repository shape doesn't match what's
 * expected (unexpected version, container renamed, etc.), this fails
 * silently rather than throwing — see YoastSeoCopier::rebuild_target_indexable(),
 * which also wraps this call in a try/catch as a second line of defence.
 * A missed rebuild is not data loss: the copied postmeta is still correct,
 * only the denormalized cache may lag until Yoast repopulates it on its
 * own (e.g. on the next front-end view or manual reindex).
 */
class CurrentIndexableRebuilder implements IndexableRebuilderInterface {

    public function rebuild( int $post_id ): void {

        if ( $post_id <= 0 || ! function_exists( 'YoastSEO' ) ) {
            return;
        }

        $repository_class = '\Yoast\WP\SEO\Repositories\Indexable_Repository';
        if ( ! class_exists( $repository_class ) ) {
            return;
        }

        $repository = \YoastSEO()->classes->get( $repository_class );

        if ( ! $repository || ! method_exists( $repository, 'find_by_id_and_type' ) ) {
            return;
        }

        // Third argument false = look up only, don't auto-build one just to
        // immediately delete it.
        $indexable = $repository->find_by_id_and_type( $post_id, 'post', false );

        if ( $indexable && method_exists( $indexable, 'delete' ) ) {
            $indexable->delete();
        }
    }
}
