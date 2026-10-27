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

namespace rbDuplicatePost\AfterCopyTransformers;

defined( 'WPINC' ) || exit;

use rbDuplicatePost\Contracts\AbstractPostAfterCopyTransformer;
use rbDuplicatePost\Contexts\TransformerContext;
use rbDuplicatePost\Copiers\RankMathCopier;
use rbDuplicatePost\GeneralOptions;

class RankMathTransformer extends AbstractPostAfterCopyTransformer {

    protected static function get_option_name(): string {
        return 'enableTypeRankMath';
    }

    public function transform( TransformerContext $context ) {
        if ( ! self::supports( $context ) ) {
            return;
        }

        if ( ! GeneralOptions::getOptionValue( self::get_option_name() ) ) {
            return;
        }

        $data     = $context->get_data();
        $term_map = ( isset( $data['term_map'] ) && is_array( $data['term_map'] ) ) ? $data['term_map'] : array();

        $args = array(
            'overwrite' => true,
            'term_map'  => $term_map,
        );

        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- rb_duplicate_post_ is this plugin's prefix; Plugin Check only guesses rbDuplicatePost from the namespace
        $args = apply_filters( 'rb_duplicate_post_rank_math_copy_args', $args, $context );

        RankMathCopier::copy( $context->to_operation_context(), $args );
    }
}
