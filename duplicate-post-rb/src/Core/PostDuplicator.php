<?php
/* 
*      RB Duplicate Post     
*      Version: 1.6.1
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
use rbDuplicatePost\Helpers\PostTypes;
use rbDuplicatePost\Log\Logger;
use rbDuplicatePost\Log\Actions;

class PostDuplicator implements DuplicatorInterface {

    public function supports( string $type ): bool {
        return PostTypes::is_type_support( $type );
    }

    // return 1 if allowed, 0 if not allowed, -1 if not special type
    public function is_allowed_special_post(int $id): int {
        if(!$id){
            return -1;
        }
        $type = get_post_type($id); 
        if(!$type){
            return -1;
        }
        return PostTypes::is_allowed_special_type($type);
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

        $new_id = $processor->processPost( $originalId, $profile_id );

        Logger::add(  Actions::COPY, $originalId, $new_id );

        return $new_id;
    }
}
