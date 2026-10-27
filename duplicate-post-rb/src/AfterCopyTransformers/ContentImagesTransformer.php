<?php
/*
*      RB Duplicate Post
*      By RbPlugin
*
*      Contact: https://rbplugins.com
*      Licensed under the GPLv3 license - http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace rbDuplicatePost\AfterCopyTransformers;

defined( 'WPINC' ) || exit;

use rbDuplicatePost\Contracts\AbstractPostAfterCopyTransformer;
use rbDuplicatePost\Contexts\BlogPostContext;
use rbDuplicatePost\Contexts\TransformerContext;
use rbDuplicatePost\Copiers\PostMediaCopier;
use rbDuplicatePost\GeneralOptions;
use rbDuplicatePost\Helpers\Multisite;

/**
 * A copy on another site of a network, media mode "copy": the images placed
 * in the text are copied to the target site too, and the text points at the
 * copies - their addresses (the full image and every size), the wp-image-N
 * classes and the ids of the image, cover, media & text and gallery blocks.
 * Without this the copy would show the source site's files and lose them
 * with the source site.
 *
 * Settings → Multisite → "Images in the text": copy (the default) or keep
 * the source site's addresses.
 */
class ContentImagesTransformer extends AbstractPostAfterCopyTransformer {

    protected static function get_option_name(): string {
        return 'multisiteContentImages';
    }

    public function transform( TransformerContext $context ) {
        $source = $context->get_source();
        $target = $context->get_target();
        if ( ! is_multisite() || $source->get_blog_id() === $target->get_blog_id() ) {
            return;
        }
        if ( Multisite::get_media_mode() !== Multisite::MEDIA_MODE_COPY || GeneralOptions::getOptionValue( 'multisiteContentImages' ) === 'keep' ) {
            return;
        }

        $did    = $target->maybe_switch_to_blog();
        $copy   = get_post( $target->get_post_id() );
        $content = $copy ? (string) $copy->post_content : '';
        BlogPostContext::maybe_restore_blog( $did );
        if ( '' === $content ) {
            return;
        }

        $ids = self::source_attachment_ids( $content, $source );
        if ( ! $ids ) {
            return;
        }

        $operation = $context->to_operation_context();
        $urls      = array();
        $id_map    = array();
        foreach ( $ids as $source_id ) {
            $new_id = PostMediaCopier::copy_attachment_by_id( $source_id, $operation, true );
            if ( ! $new_id ) {
                continue;
            }
            $id_map[ $source_id ] = (int) $new_id;
            $urls += self::url_map( $source_id, $source, (int) $new_id, $target );
        }
        if ( ! $id_map ) {
            return;
        }

        // the longest addresses first: a size's address contains the full image's name
        uksort( $urls, function ( $a, $b ) {
            return strlen( $b ) - strlen( $a );
        } );
        $new = str_replace( array_keys( $urls ), array_values( $urls ), $content );

        foreach ( $id_map as $old => $id ) {
            $new = preg_replace( '/\bwp-image-' . $old . '\b/', 'wp-image-' . $id, $new );
            // <!-- wp:image {"id":8 ...} -->, cover, media & text
            $new = preg_replace( '/(<!--\s+wp:(?:image|cover|media-text)\s+\{[^}]*"(?:id|mediaId)":)' . $old . '\b/', '${1}' . $id, $new );
        }
        // <!-- wp:gallery {"ids":[8,9]} -->
        $new = preg_replace_callback( '/("ids":\[)([0-9,\s]*)(\])/', function ( $m ) use ( $id_map ) {
            $ids = array_map( function ( $v ) use ( $id_map ) {
                $v = (int) trim( $v );
                return isset( $id_map[ $v ] ) ? $id_map[ $v ] : $v;
            }, array_filter( explode( ',', $m[2] ), 'strlen' ) );
            return $m[1] . implode( ',', $ids ) . $m[3];
        }, $new );

        if ( $new === $content ) {
            return;
        }
        $did = $target->maybe_switch_to_blog();
        wp_update_post( array( 'ID' => $target->get_post_id(), 'post_content' => wp_slash( $new ) ) );
        BlogPostContext::maybe_restore_blog( $did );
    }

    /**
     * The source site's attachments the text shows: by wp-image-N classes,
     * block ids, and file addresses in the source site's uploads.
     *
     * @return int[]
     */
    private static function source_attachment_ids( string $content, BlogPostContext $source ): array {
        $found = array();
        if ( preg_match_all( '/\bwp-image-(\d+)\b/', $content, $m ) ) {
            $found = array_merge( $found, $m[1] );
        }
        if ( preg_match_all( '/<!--\s+wp:(?:image|cover|media-text)\s+\{[^}]*"(?:id|mediaId)":(\d+)/', $content, $m ) ) {
            $found = array_merge( $found, $m[1] );
        }
        if ( preg_match_all( '/"ids":\[([0-9,\s]*)\]/', $content, $m ) ) {
            foreach ( $m[1] as $list ) {
                $found = array_merge( $found, array_filter( array_map( 'trim', explode( ',', $list ) ), 'strlen' ) );
            }
        }

        $did     = $source->maybe_switch_to_blog();
        $uploads = wp_get_upload_dir();
        if ( ! empty( $uploads['baseurl'] ) && preg_match_all( '#' . preg_quote( $uploads['baseurl'], '#' ) . '/[^"\'\s)]+\.(?:jpe?g|png|gif|webp|avif|svg)#i', $content, $m ) ) {
            foreach ( array_unique( $m[0] ) as $url ) {
                // a size's address (photo-300x200.jpg) belongs to the full image
                $id = attachment_url_to_postid( preg_replace( '/-\d+x\d+(\.[a-z0-9]+)$/i', '$1', $url ) );
                if ( ! $id ) {
                    $id = attachment_url_to_postid( $url );
                }
                if ( $id ) {
                    $found[] = $id;
                }
            }
        }
        $ids = array();
        foreach ( array_unique( array_map( 'intval', $found ) ) as $id ) {
            if ( $id > 0 && 'attachment' === get_post_type( $id ) ) {
                $ids[] = $id;
            }
        }
        BlogPostContext::maybe_restore_blog( $did );
        return $ids;
    }

    /**
     * Every address of an image on the source site => the address of its copy:
     * the full image, and each size (the copy's size of the same name, else its full image).
     *
     * @return array<string,string>
     */
    private static function url_map( int $source_id, BlogPostContext $source, int $target_id, BlogPostContext $target ): array {
        $read = function ( int $id ) {
            $full  = (string) wp_get_attachment_url( $id );
            $sizes = array();
            $meta  = wp_get_attachment_metadata( $id );
            if ( $full && is_array( $meta ) && ! empty( $meta['sizes'] ) ) {
                foreach ( $meta['sizes'] as $name => $size ) {
                    if ( ! empty( $size['file'] ) ) {
                        $sizes[ $name ] = dirname( $full ) . '/' . $size['file'];
                    }
                }
            }
            return array( $full, $sizes );
        };
        $did = $source->maybe_switch_to_blog();
        list( $from_full, $from_sizes ) = $read( $source_id );
        BlogPostContext::maybe_restore_blog( $did );
        $did = $target->maybe_switch_to_blog();
        list( $to_full, $to_sizes ) = $read( $target_id );
        BlogPostContext::maybe_restore_blog( $did );

        if ( ! $from_full || ! $to_full ) {
            return array();
        }
        $map = array( $from_full => $to_full );
        foreach ( $from_sizes as $name => $url ) {
            $map[ $url ] = isset( $to_sizes[ $name ] ) ? $to_sizes[ $name ] : $to_full;
        }
        return $map;
    }
}
