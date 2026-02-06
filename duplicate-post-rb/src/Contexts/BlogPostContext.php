<?php

namespace rbDuplicatePost\Contexts;

/**
 * Represents a post within a specific blog context.
 * Encapsulates post_id, blog_id, and Multisite awareness.
 */

use WP_Post;
use InvalidArgumentException;

class BlogPostContext {

    /**
     * @var int Post ID.
     */
    private $post_id;

    /**
     * @var int Blog ID (always valid; on single-site it's 1).
     */
    private $blog_id;

    /**
     * @var bool Whether the site is Multisite.
     */
    private $is_multisite;


    private $empty_post_id = false;

    /**
     * Constructor.
     *
     * @param int $post_id Post ID.
     * @param int|null $blog_id Optional. If not provided, uses current blog.
     *
     * @throws InvalidArgumentException If post_id or blog_id is invalid.
     */
    public function __construct( $post_id, $blog_id = null, bool $allow_empty_post_id = false ) {
        $post_id = absint( $post_id );
        if ( $post_id <= 0 && !$allow_empty_post_id ) {
            throw new InvalidArgumentException( 'Post ID must be a positive integer.' );
        }

        $this->empty_post_id = $allow_empty_post_id && $post_id === 0;  

        $this->post_id      = $post_id ;
        $this->is_multisite = is_multisite();
        $this->blog_id      = $blog_id !== null ? absint($blog_id) : get_current_blog_id();

        if ( $this->blog_id <= 0 ) {
            throw new InvalidArgumentException( 'Blog ID must be a positive integer.' );
        }
    }

    public static function create_empty_target( int $blog_id = null ): self {
        return new self( 0, $blog_id, true );
    }

    /**
     * @return int
     */
    public function get_post_id() {
        return $this->post_id;
    }

    /**
     * @return int
     */
    public function get_blog_id() {
        return $this->blog_id;
    }

    /**
     * @return bool True if the site is Multisite.
     */
    public function is_multisite() {
        return $this->is_multisite;
    }

    /**
     * Safely switches to this context's blog (if Multisite and not already there).
     * Returns true if a switch occurred.
     *
     * @return bool
     */
    public function maybe_switch_to_blog() {
        if ( ! $this->is_multisite || get_current_blog_id() === $this->blog_id ) {
            return false;
        }
        switch_to_blog( $this->blog_id );
        return true;
    }

    /**
     * Restores the previous blog only if we are in Multisite and a switch occurred.
     *
     * @param bool $did_switch
     * @return void
     */
    public static function maybe_restore_blog( $did_switch ) {
        if ( $did_switch && is_multisite() ) {
            restore_current_blog();
        }
    }

    /**
     * Retrieves the WP_Post object for this context.
     *
     * @return WP_Post|null
     */
    public function get_post() {
        if( $this->empty_post_id ) {
            return null;
        }
        $did_switch = $this->maybe_switch_to_blog();
        $post = get_post( $this->post_id );
        self::maybe_restore_blog( $did_switch );
        return $post;
    }

    /**
     * Retrieves the featured image ID (post thumbnail) for this post.
     *
     * @return int|null
     */
    public function get_featured_image_id() {
        if( $this->empty_post_id ) {
            return null;
        }
        $did_switch = $this->maybe_switch_to_blog();
        $thumb_id = get_post_thumbnail_id( $this->post_id );
        self::maybe_restore_blog( $did_switch );
        return $thumb_id ? (int) $thumb_id : null;
    }

    /**
     * Retrieves all child attachments (post_parent = this post).
     *
     * @return array List of attachment IDs.
     */
    public function get_child_attachment_ids() {
        if( $this->empty_post_id ) {
            return array();
        }
        $did_switch = $this->maybe_switch_to_blog();
        $attachments = get_posts( array(
            'post_type'      => 'attachment',
            'post_parent'    => $this->post_id,
            'numberposts'    => -1,
            'post_status'    => 'any',
            'fields'         => 'ids',
        ) );
        self::maybe_restore_blog( $did_switch );
        return array_map( 'intval', $attachments );
    }

}