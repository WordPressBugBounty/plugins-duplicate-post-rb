<?php
/* 
*      RB Duplicate Post     
*      Version: 1.6.8
*      By RbPlugin
*
*      Contact: https://rbplugins.com 
*      Created: 2025
*      Licensed under the GPLv3 license - http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace rbDuplicatePost\Core;

defined( 'WPINC' ) || exit;

use rbDuplicatePost\Contracts\DuplicatorInterface;
use rbDuplicatePost\Profile\Profile;
use rbDuplicatePost\OptionsManager;
use rbDuplicatePost\PostProcessor;
use rbDuplicatePost\Helpers\PostTypes;
use rbDuplicatePost\Helpers\Multisite;
use rbDuplicatePost\Log\Logger;
use rbDuplicatePost\Log\Actions;

class PostDuplicator implements DuplicatorInterface {

    public function supports( string $type ): bool {
        return PostTypes::is_type_support( $type );
    }

    // return 1 if allowed, 0 if not allowed, -1 if not special type
    public function is_allowed_special_post( int $id ): int {
        if ( ! $id ) {
            return -1;
        }
        $type = get_post_type( $id );
        if ( ! $type ) {
            return -1;
        }
        return PostTypes::is_allowed_special_type( $type );
    }

    public function duplicate( int $originalId, int $profile_id = 0, int $target_blog_id = 0, int $source_blog_id = 0 ): int {

        $originalId = \absint( $originalId );

        if ( ! $originalId ) {
            throw new \InvalidArgumentException( 'Invalid post ID.' );
        }

        $source_blog_id = $source_blog_id > 0 ? absint( $source_blog_id ) : get_current_blog_id();
        $target_blog_id = Multisite::normalize_target_blog_id( $target_blog_id );

        // Load source post from the correct blog.
        $switched = false;
        if ( is_multisite() && get_current_blog_id() !== $source_blog_id ) {
            switch_to_blog( $source_blog_id );
            $switched = true;
        }

        $original = \get_post( $originalId );

        if ( $switched ) {
            restore_current_blog();
        }

        if ( ! $original || ! ( $original instanceof \WP_Post ) ) {
            throw new \InvalidArgumentException( 'Post not found.' );
        }

        // the post types switched off in the settings are not copied, whichever way the copy is asked for
        if ( ! $this->supports( $original->post_type ) ) {
            throw new \InvalidArgumentException( 'Copying of this post type is switched off.' );
        }

        if ( ! $profile_id || ! Profile::isProfileExists( $profile_id ) ) {
            $profile_id = Profile::getDefaultProfileId();
        }

        $options_manager = new OptionsManager();
        $processor       = new PostProcessor( $options_manager );

        $new_id = $processor->processPost( $originalId, $profile_id, $target_blog_id, $source_blog_id );

        Logger::add( Actions::COPY, $originalId, $new_id );

        return $new_id;
    }

    public function create_duplicate( int $profile_id, int $target_blog_id = 0 ): array {

        $profile_id = \absint( $profile_id );

        if ( ! $profile_id ) {
            throw new \InvalidArgumentException( 'Invalid profile ID.' );
        }

        if ( ! Profile::isProfileExists( $profile_id ) ) {
            throw new \InvalidArgumentException( 'Profile not found.' );
        }

        $originalId = Profile::getProfileSourceId( $profile_id );

        if ( ! $originalId ) {
            throw new \InvalidArgumentException( 'Don\'t have source  ID. Please select a source in profile options.' );
        }

        $original = \get_post( $originalId );

        if ( ! $original || ! ( $original instanceof \WP_Post ) ) {
            throw new \InvalidArgumentException( 'Post not found.' );
        }

        $options_manager = new OptionsManager();
        $processor       = new PostProcessor( $options_manager );

        $target_blog_id = Multisite::normalize_target_blog_id( $target_blog_id );
        $new_id         = $processor->processPost( $originalId, $profile_id, $target_blog_id );

        Logger::add( Actions::COPY, $originalId, $new_id );

        return array(
            'source_id'       => $originalId,
            'new_id'          => $new_id,
            'target_blog_id'  => $target_blog_id,
            'source_blog_id'  => get_current_blog_id(),
        );
    }
}
