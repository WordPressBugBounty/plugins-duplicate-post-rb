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

namespace rbDuplicatePost\AfterCopyTransformers;

defined( 'WPINC' ) || exit;

use rbDuplicatePost\Contracts\AbstractPostAfterCopyTransformer;
use rbDuplicatePost\Contexts\TransformerContext;

class GUIDTransformer extends AbstractPostAfterCopyTransformer {

    //this transformer  needed all the time
     protected static function get_option_name(): string{
        return '';
    }

    public function transform(TransformerContext $context)  {
        if ( !self::supports( $context ) ) {
            return ;
        }

        return ;

        $post_source_id = $context->get_source()->get_post_id();
        $post_new_id = $context->get_target()->get_post_id();

        //$guid = \get_the_guid($post_source_id);
        $guid = \get_post_field('guid', $post_source_id, 'raw');

        if(empty($guid)) {      // strange case - no guid
            return ;
        }

        $new_guid  = self::replace_id_in_guid($guid, $post_source_id, $post_new_id);

        if( empty($new_guid )) {
            return ;
        }

        global $wpdb;

        $result = $wpdb->update(
            $wpdb->posts, 
            ['guid' => $new_guid ],
            ['ID'   => $post_new_id] ,
            [ '%s' ],
            [ '%d' ]
        );

        if ($result === false) {
            error_log("GUIDTransformer: failed to update GUID for post {$post_new_id}: {$wpdb->last_error}");
        }
    }

    /**
     * 
     * Replace ID in GUID
     *
    * @param string $guid
    * @param int    $source_id
    * @param int    $new_id
    * @return string|null  New GUID or null if source ID not found
    */

    private static function replace_id_in_guid(
        string $guid, 
        int $source_id, 
        int $new_id
    ) {

        $parts = parse_url($guid);

        if ($parts === false) {
            return null; // not a valid URL
        }

        $replaced = false;
        $source_str = (string) $source_id;
        $new_str    = (string) $new_id;

        // 1. Search in query params
        // Replace only if the value is equal to source_id
        // Safe for: /?p=123&post_type=post
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query_params);

            foreach ($query_params as $key => $value) {
                if ($value === $source_str) {
                    $query_params[$key] = $new_str;
                    $replaced = true;
                }
            }

            $parts['query'] = http_build_query($query_params);
        }

        // 2. Search in path
        // Replace only if the whole path is equal to source_id
        // Safe for: /archives/123/, /post-name/123/
        if (!$replaced && !empty($parts['path'])) {
            $segments = explode('/', $parts['path']);
            
            foreach ($segments as &$segment) {
                if ($segment === $source_str) {
                    $segment = $new_str;
                    $replaced = true;
                }
            }
            unset($segment);

            $parts['path'] = implode('/', $segments);
        }

        if (!$replaced) {
            return null; // not found 
        }

        return self::build_url($parts);
    }

    /**
     * Builds a URL from the given parts.
     */
    private static function build_url(array $parts): string {
        $url = '';

        if (!empty($parts['scheme']))   $url .= $parts['scheme'] . '://';
        if (!empty($parts['host']))     $url .= $parts['host'];
        if (!empty($parts['port']))     $url .= ':' . $parts['port'];
        if (!empty($parts['path']))     $url .= $parts['path'];
        if (!empty($parts['query']))    $url .= '?' . $parts['query'];
        if (!empty($parts['fragment'])) $url .= '#' . $parts['fragment'];

        return $url;
    }
}
