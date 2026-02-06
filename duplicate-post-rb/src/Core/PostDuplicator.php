<?php
/* 
*      RB Duplicate Post     
*      Version: 1.5.8
*      By RbPlugin
*
*      Contact: https://robosoft.co 
*      Created: 2025
*      Licensed under the GPLv3 license - http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace rbDuplicatePost\Core;

defined( 'WPINC' ) || exit;

use rbDuplicatePost\Contracts\DuplicatorInterface;
use rbDuplicatePost\Profile\Profile;
use rbDuplicatePost\ProfileOptions;
use rbDuplicatePost\OptionsManager;
use rbDuplicatePost\PostProcessor;

class PostDuplicator implements DuplicatorInterface {

    //TODO: need to check if post type is supported
    public function supports( string $type ): bool {
        return true;
    }

    public function duplicate( int $originalId, int $profile_id = 0 ): int {
        
        $originalId = \absint( $originalId );

        if ( ! $originalId ) {
            throw new \InvalidArgumentException( 'Invalid post ID.' );
        }

        $original = \get_post( $originalId );

        if ( ! $original || ! ( $original instanceof \WP_Post ) ) {
            throw new \InvalidArgumentException( 'Post not found.' );
        }

        if ( ! $profile_id || ! Profile::isProfileExists( $profile_id ) ) {
            $profile_id = Profile::getDefaultProfileId();
        }

        $options_manager = new OptionsManager(  );
        $processor       = new PostProcessor( $options_manager );

        return $processor->processPost( $originalId, $profile_id );
    }
}
