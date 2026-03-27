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

namespace rbDuplicatePost;

defined( 'WPINC' ) || exit;

use rbDuplicatePost\Constants;

class GeneralOptionsConfig {

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

    private static function getOptionsData() {

        return array(

            'pluginMenuInToolsMenu'         => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => false,
            ),


            /* Allow for */

            'allowForSuperAdmins'          => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => true,
            ),

            'allowForAdministrators'          => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => true,
            ),

            'allowForEditors'          => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => true,
            ),

            'allowForAuthors'          => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => true,
            ),

            'allowForContributors'          => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => false,
            ),

            'allowForSubscribers'          => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => false,
            ),

            /* Show on */

            'showOnLists'          => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => true,
            ),

            'showOnEdit'          => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => true,
            ),

            'showOnAdminBar'          => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => true,
            ),
            'showOnGutenberg'          => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => true,
            ),
            'showOnBulkActions'          => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => true,
            ),

            'enablePostPostType'          => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => true,
            ),

            'enablePagePostType'          => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => true,
            ),

            'enableCustomPostType'          => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => true,
            ),

            'enableTypeYoastSEO'          => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => false,
            ),

            'enableTypeRankMath'          => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => false,
            ),
            'enableTypeAllInOneSEO'          => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  =>  false,
            ),
           
             'enableTypeACF'          => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => false,
            ),

            'enableTypeWooCommerce'          => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => false,
            ),

            'enableTypeElementor'          => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => false,
            ),

            'enableTypeJetPack'          => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => false,
            ),

            'enableTypeWPBakery'          => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => false,
            ),

            'disabledCustomPostTypes'          => array(
                'type'     => 'text',
                'sanitize' => 'multiline_string',
                'default'  => '',
            ),

        );
    }
}
