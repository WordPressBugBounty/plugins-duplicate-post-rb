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
use rbDuplicatePost\Copiers\YoastSeoCopier;
use rbDuplicatePost\GeneralOptions;

class YoastSeoTransformer extends AbstractPostAfterCopyTransformer {

    protected static function get_option_name(): string {
        return 'enableTypeYoastSEO';
    }

    public function transform( TransformerContext $context ) {
        if ( ! self::supports( $context ) ) {
            return;
        }

        // switched on in Settings → Integrations, as Rank Math and All in One SEO
        if ( ! GeneralOptions::getOptionValue( self::get_option_name() ) ) {
            return;
        }

        $data     = $context->get_data();
        $term_map = ( isset( $data['term_map'] ) && is_array( $data['term_map'] ) ) ? $data['term_map'] : array();

        $args = array(
            'overwrite' => true,
            'term_map'  => $term_map,
        );

        YoastSeoCopier::copy( $context->to_operation_context(), $args );
    }
}
