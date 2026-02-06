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

namespace rbDuplicatePost\Transformers;

defined('WPINC') || exit;

use rbDuplicatePost\Contracts\AbstractPostTransformer;
use rbDuplicatePost\Contexts\TransformerContext;

class DateTransformer extends AbstractPostTransformer {

    protected static function get_option_name(): string{
        return 'copyDate';
    }

    public function transform(TransformerContext $context, array $post_data): array {

        if (!self::supports($context)) {
            return $post_data;
        }
        if (self::is_copy_enabled($context->get_options())) {  
            return $post_data;
        }

        // $post_data['post_date'] = $this->calculateNewDate($post_data['post_date'], $options);
        // $post_data['post_modified'] = current_time('mysql');
        $post_data[ 'post_date' ] = current_time( 'mysql' );
        $post_data[ 'post_modified' ] = current_time( 'mysql' );
        return $post_data;
    }
    
    // private function calculateNewDate(string $original_date, array $options): string {
    //     return date('Y-m-d H:i:s', strtotime($original_date . ' +1 day'));
    // }
}