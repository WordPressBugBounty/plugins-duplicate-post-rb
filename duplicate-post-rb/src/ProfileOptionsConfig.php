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

namespace rbDuplicatePost;

defined( 'WPINC' ) || exit;

use rbDuplicatePost\Constants;

class ProfileOptionsConfig {

    public static function getOptions() {
        $options = self::getOptionsData();

        foreach ( $options as $option_id => $option ) {
            $options[ $option_id ][ 'option_id' ] = $option_id;

            if ( ! isset( $options[ $option_id ][ 'sanitize' ] ) ) {
                $options[ $option_id ][ 'sanitize' ] = 'string';
            }
        }

        return $options;
    }

    public static function getOptionsGroupArray() {
        $OptionsGroupList = array();
        $options          = self::getOptions();

        foreach ( $options as $option_id => $option ) {
            $OptionsGroupList[ $option[ 'group' ] ][ $option_id ] = $option;
        }

        return $OptionsGroupList;
    }

    private static function getOptionsData() {

        return array(

            'copyTitle'          => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => true,
                'group'    => 'general',
            ),
            'copyDate'           => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => false,
                'group'    => 'general',
            ),
            'copyStatus'         => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => false,
                'group'    => 'general',
            ),
            'copySlug'           => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => false,
                'group'    => 'general',
            ),
            'copyExcerpt'        => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => false,
                'group'    => 'general',
            ),
            'copyContent'        => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => true,
                'group'    => 'general',
            ),
            'copyFeaturedImage'  => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => true,
                'group'    => 'general',
            ),
            'copyTemplate'       => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => true,
                'group'    => 'general',
            ),
            'copyFormat'         => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => true,
                'group'    => 'general',
            ),
            'copyAuthor'         => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => true,
                'group'    => 'general',
            ),
            'copyMenuOrder'      => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => false,
                'group'    => 'general',
            ),
            'copyPassword'       => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => true,
                'group'    => 'general',
            ),
            'copyAttachments'    => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => false,
                'group'    => 'general',
            ),
            'copyChildren'       => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => false,
                'group'    => 'general',
            ),
            'copyCategories'     => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => false,
                'group'    => 'general',
            ),
            'copyTags'           => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => true,
                'group'    => 'general',
            ),
            'copyTaxonomies'     => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => false,
                'group'    => 'general',
            ),
            'copyNavigationMenu' => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => false,
                'group'    => 'general',
            ),
            'copyLinkCategories' => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => false,
                'group'    => 'general',
            ),
            'copyAllPostMeta'    => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => false,
                'group'    => 'general',
            ),

            'counterStartValue'  => array(
                'type'     => 'text',
                'sanitize' => 'integer',
                'default'  => 1,
                'group'    => 'general',
            ),
            'dateFormat'         => array(
                'type'     => 'text',
                'sanitize' => 'string',
                'default'  => 'd/m/Y',
                'group'    => 'general',
            ),

            'timeFormat'         => array(
                'type'     => 'text',
                'sanitize' => 'string',
                'default'  => 'h:i',
                'group'    => 'general',
            ),

            'prefixValue'        => array(
                'type'     => 'text',
                'sanitize' => 'string',
                'default'  => '',
                'group'    => 'general',
            ),

            'suffixValue'        => array(
                'type'     => 'text',
                'sanitize' => 'string',
                'default'  => '',
                'group'    => 'general',
            ),

        );
    }
}
