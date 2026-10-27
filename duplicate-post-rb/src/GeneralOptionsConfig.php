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

            /* Multisite */
            'enableCrossSiteCopy'          => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => true,
            ),

            /**
             * How to handle media when copying across sites (or forced same-site):
             * - copy: physically duplicate attachment files on the target site
             * - link: reuse source media IDs / URLs (suitable for shared uploads)
             */
            // a copy names its original: in the posts lists, and in the editor's Copy history box
            'showOriginalInLists'          => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => false,
            ),
            'showOriginalOnEdit'          => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => true,
            ),

            /* Rewrite & Republish */
            'enableRewriteRepublish'          => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => true,
            ),

            /* Auto Cleanup (PRO: the key runs it) */
            'autoCleanupEnabled'          => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => false,
            ),
            'autoCleanupTypes'          => array(
                'type'     => 'text',
                'sanitize' => 'string',
                'default'  => 'post',
            ),
            'autoCleanupMatch'          => array(
                'type'     => 'select',
                'sanitize' => 'string',
                'default'  => 'title_content',
                'options'  => array( 'title', 'title_content', 'content' ),
            ),
            'autoCleanupKeep'          => array(
                'type'     => 'select',
                'sanitize' => 'string',
                'default'  => 'oldest',
                'options'  => array( 'oldest', 'newest' ),
            ),
            'autoCleanupDraftsOnly'          => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => true,
            ),
            // a published duplicate removed by the auto cleanup redirects to the post that stays (needs RB Links Redirection)
            'autoCleanupRedirect'          => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                // opt-in: no redirect is made unless the admin asks for it
                'default'  => false,
            ),
            'autoCleanupSchedule'          => array(
                'type'     => 'select',
                'sanitize' => 'string',
                'default'  => 'daily',
                'options'  => array( 'hourly', 'daily', 'weekly' ),
            ),

            'multisiteMediaMode'          => array(
                'type'     => 'select',
                'sanitize' => 'string',
                'default'  => 'copy',
                'options'  => array(
                    'copy',
                    'link',
                ),
            ),

            // a copy on another site of a network, media "copy": the images placed
            // in the text too (copy), or their addresses on the source site (keep)
            'multisiteContentImages'          => array(
                'type'     => 'select',
                'sanitize' => 'string',
                'default'  => 'copy',
                'options'  => array(
                    'copy',
                    'keep',
                ),
            ),

            'forceCopyMediaOnSameSite'          => array(
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => false,
            ),

        );
    }
}
