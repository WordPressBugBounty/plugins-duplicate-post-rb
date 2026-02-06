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
 * Represents a transformation operation between two posts in their respective blog contexts.
 * Encapsulates source and target BlogPostContext objects, along with options and profile ID.
 */
class TransformerContext {

    /**
     * @var BlogPostContext Source post context.
     */
    private $source;

    /**
     * @var BlogPostContext Target post context.
     */
    private $target;


    /**
     * @var array Options used during the transformation.
     */
    private $options;

    /**
     * @var int Profile ID associated with the transformation.
     */
    private $profile_id;

    /**
     * Constructor.
     *
     * @param BlogPostContext $source The post to copy from.
     * @param BlogPostContext $target The post to copy to.
     * @param array $options Options used during the transformation.
     * @param int $profile_id Profile ID associated with the transformation.
     */
    public function __construct( BlogPostContext $source, BlogPostContext $target, $options = array(), $profile_id = 0   ) {
        $this->source = $source;
        $this->target = $target;

        $this->options = $options;

        $this->profile_id = is_numeric( $profile_id ) ? (int) $profile_id : 0;
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
     * @return array
     */
    public function get_options() {
        return $this->options;
    }

    /**
     * Checks if a specific option exists.
     *
     * @param string $key
     * @return bool
     */
    public function has_option( $key ) {
        return array_key_exists( $key, $this->options );
    }

    /**
     * Gets an option value with a default fallback.
     *
     * @param string $key
     * @return mixed
     */
    public function get_option( $key ) {
        if( !is_string( $key ) || $key === '' ) {
            throw new InvalidArgumentException( 'Option key must be a non-empty string.' );
        }
        if($this->options === null || !is_array( $this->options ) ) {
            throw new InvalidArgumentException( 'Options must be a valid array.' );
        }

        if( $this->has_option( $key ) && isset($this->options[ $key ]['value']) ) {
            return $this->options[ $key ]['value'];
        }
        if( $this->has_option( $key )  && isset($this->options[ $key ]['default']) ) {
            return $this->options[ $key ]['default'];
        }
        throw new InvalidArgumentException( 'Option key does not exist in options.' );
    }

    public function to_operation_context() {
        return CopyOperationContext::from_blog_ids(
            $this->source->get_post_id(),
            $this->source->get_blog_id(),
            $this->target->get_post_id(),
            $this->target->get_blog_id()
        );
    }   

    /**
     * @return int
     */
    public function get_profile_id() {
        return $this->profile_id;
    }

    /**
     * Helper: creates a context from raw IDs (assumes current blog for both).
     *
     * @param int $source_post_id
     * @param int $target_post_id
     * @param array $options
     * @param int $profile_id
     * @return self
     */
    public static function from_current_blog( $source_post_id, $target_post_id, $options = array(), $profile_id = 0 ) {
        $source = new BlogPostContext( $source_post_id );
        $allow_empty_target = $target_post_id === 0;
        $target = new BlogPostContext( $target_post_id, null, $allow_empty_target );
        return new self( $source, $target, $options, $profile_id );
    }

    /**
     * Helper: creates a context with explicit blog IDs.
     *
     * @param int $source_post_id
     * @param int $source_blog_id
     * @param int $target_post_id
     * @param int $target_blog_id
     * @param array $options
     * @param int $profile_id
     * @return self
     */
    public static function from_blog_ids( $source_post_id, $source_blog_id, $target_post_id, $target_blog_id, $options = array(), $profile_id = 0 ) {
        $source = new BlogPostContext( $source_post_id, $source_blog_id );

        $allow_empty_target = $target_post_id === 0;
        $target = new BlogPostContext( $target_post_id, $target_blog_id, $allow_empty_target );
        
        return new self( $source, $target, $options, $profile_id );
    }
}