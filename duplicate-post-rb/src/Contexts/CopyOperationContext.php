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

namespace rbDuplicatePost\Contexts;

use InvalidArgumentException;
/**
 * Represents a copy operation between two posts in their respective blog contexts.
 * Encapsulates source and target BlogPostContext objects.
 */
class CopyOperationContext {

    /**
     * @var BlogPostContext Source post context.
     */
    private $source;

    /**
     * @var BlogPostContext Target post context.
     */
    private $target;

    /**
     * Constructor.
     *
     * @param BlogPostContext $source The post to copy from.
     * @param BlogPostContext $target The post to copy to.
     */
    public function __construct(BlogPostContext $source, BlogPostContext $target ) {
        $this->source = $source;
        $this->target = $target;
    }

    /**
     * @return BlogPostContext
     */
    public function get_source() {
        return $this->source;
    }

    /**
     * @return BlogPostContext
     */
    public function get_target() {
        return $this->target;
    }

    /**
     * Helper: creates a context from raw IDs (assumes current blog for both).
     *
     * @param int $source_post_id
     * @param int $target_post_id
     * @return self
     */
    public static function from_current_blog( $source_post_id, $target_post_id, $empty_target_post_id = false ) {

        $source = new BlogPostContext( $source_post_id );
        $target = new BlogPostContext( $target_post_id, null, $empty_target_post_id );
        return new self( $source, $target );
    }

    /**
     * Helper: creates a context with explicit blog IDs.
     *
     * @param int $source_post_id
     * @param int $source_blog_id
     * @param int $target_post_id
     * @param int $target_blog_id
     * @return self
     */
    public static function from_blog_ids( $source_post_id, $source_blog_id, $target_post_id, $target_blog_id ) {
        $source = new BlogPostContext( $source_post_id, $source_blog_id );
        $target = new BlogPostContext( $target_post_id, $target_blog_id );
        return new self( $source, $target );
    }
}