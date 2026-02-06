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

defined( 'WPINC' ) || exit;

use rbDuplicatePost\Contracts\AbstractPostTransformer;
use rbDuplicatePost\Contexts\TransformerContext;
use rbDuplicatePost\Constants;
use rbDuplicatePost\ProfileOptions;

class TitleTransformer extends AbstractPostTransformer {

    protected static function get_option_name(): string{
        return 'copyTitle';
    }

    public function transform( TransformerContext $context, array $post_data ): array {

        if ( !self::supports( $context ) ) {
            return $post_data;
        }

        if(!self::is_copy_enabled($context->get_options())) {
            $post_data[ 'post_title' ] = '';
            return $post_data;
        }

        $options                   = $context->get_options();
        $profile_id                = $context->get_profile_id();

        $original_title            = $post_data[ 'post_title' ];
        $post_data[ 'post_title' ] = $this->generateNewTitle( $original_title, $options, $profile_id  );

        return $post_data;
    }

    private function generateNewTitle( string $title, array $options, int $profile_id ): string {
        $newTitle = sanitize_text_field( $title );

        $prefixValue = self::getOptionValue( $options, 'prefixValue' );
        $suffixValue = self::getOptionValue( $options, 'suffixValue' );

        if(self::isTagInStr( 'Counter', $prefixValue) || self::isTagInStr( 'Counter',  $suffixValue)) {
            $counterValue      = self::getOptionValue( $options, Constants::COUNTER_OPTION_NAME );
            $newCounterValue = (int) $counterValue + 1;
            $prefixValue =  self::replaceTag( 'Counter',(string) $newCounterValue, $prefixValue );
            $suffixValue =  self::replaceTag( 'Counter', (string) $newCounterValue, $suffixValue );

            $optionsForUpdate= array( Constants::COUNTER_OPTION_NAME => $newCounterValue );
            ProfileOptions::updateOptionsInDb( $profile_id, $optionsForUpdate );
            //update_option( Constants::COUNTER_OPTION_NAME, $newCounterValue );
        }

        if(self::isTagInStr( 'CurrentDate', $prefixValue) || self::isTagInStr( 'CurrentDate',  $suffixValue)) {
            $dateFormat      = self::getOptionValue( $options, 'dateFormat' );
            $dateValue = date( $dateFormat );
            $prefixValue =  self::replaceTag( 'CurrentDate', $dateValue, $prefixValue );
            $suffixValue =  self::replaceTag( 'CurrentDate', $dateValue, $suffixValue );
        }

        if(self::isTagInStr( 'CurrentTime', $prefixValue) || self::isTagInStr( 'CurrentTime',  $suffixValue)) {
            $timeFormat      = self::getOptionValue( $options, 'timeFormat' );
            $timeValue = date( $timeFormat );
            $prefixValue =  self::replaceTag( 'CurrentTime', $timeValue, $prefixValue );
            $suffixValue =  self::replaceTag( 'CurrentTime', $timeValue, $suffixValue );
        }

       $newTitle = ($prefixValue?$prefixValue.' ':'') . $newTitle . ($suffixValue? ' '.$suffixValue:'');

        return $newTitle;
    }

    private static function getOptionValue( array $options, string $option_id, string $default = '' ): string {
        if ( isset( $options[ $option_id ] ) && isset( $options[ $option_id ][ 'value' ] ) ) {
            return $options[ $option_id ][ 'value' ];
        }
        return '';
    }


    // private static function updateCounterOption( $profile_id, $newCounterValue ): void {
    //     ProfileOptions::updateOption( $profile_id, Constants::COUNTER_OPTION_NAME, $newCounterValue );
    //     if ( isset( $options[ $option_id ] ) && isset( $options[ $option_id ][ 'value' ] ) ) {
    //         return $options[ $option_id ][ 'value' ];
    //     }
    //     return '';
    // }

    private static function isTagInStr( string $tag, string $str ): bool {
        $tag = '[' . trim($tag) . ']';
        $lowerTag = strtolower( $tag );
        return strpos( $str, $tag ) !== false || strpos( $str,  $lowerTag ) !== false;
    }

    private static function replaceTag( string $tag, string $value, string $str ): string {
        $tag = '[' . trim($tag) . ']';
        $lowerTag = strtolower( $tag );
        return str_replace( array($tag, $lowerTag), $value, $str );
    }
}
