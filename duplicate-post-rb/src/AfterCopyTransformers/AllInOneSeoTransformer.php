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
use rbDuplicatePost\Copiers\AllInOneSeoCopier;
use rbDuplicatePost\GeneralOptions;

/**
 * After-copy transformer that copies All in One SEO (AIOSEO) data.
 *
 * Controlled by the general option `enableTypeAllInOneSEO`.
 * Supports both modern AIOSEO (custom table) and legacy postmeta.
 */
class AllInOneSeoTransformer extends AbstractPostAfterCopyTransformer {

    protected static function get_option_name(): string {
        return 'enableTypeAllInOneSEO';
    }

    public function transform( TransformerContext $context ) {
        if ( ! self::supports( $context ) ) {
            return;
        }

        if ( ! GeneralOptions::getOptionValue( self::get_option_name() ) ) {
            return;
        }

        $args = array(
            'overwrite' => true,
        );

        /**
         * Filter args passed to AllInOneSeoCopier::copy().
         *
         * @param array              $args
         * @param TransformerContext $context
         */
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- rb_duplicate_post_ is this plugin's prefix; Plugin Check only guesses rbDuplicatePost from the namespace
        $args = apply_filters( 'rb_duplicate_post_aioseo_copy_args', $args, $context );

        AllInOneSeoCopier::copy( $context->to_operation_context(), $args );
    }
}
