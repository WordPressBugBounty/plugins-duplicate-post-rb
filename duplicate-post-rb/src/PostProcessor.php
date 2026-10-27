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

use rbDuplicatePost\OptionsManager;
use rbDuplicatePost\Helpers\PrepareOptions;
use rbDuplicatePost\Helpers\Multisite;

use rbDuplicatePost\Transformers\TitleTransformer;
use rbDuplicatePost\Transformers\DateTransformer;
use rbDuplicatePost\Transformers\SlugTransformer;
use rbDuplicatePost\Transformers\StatusTransformer;
use rbDuplicatePost\Transformers\ExcerptTransformer;
use rbDuplicatePost\Transformers\ContentTransformer;
use rbDuplicatePost\Transformers\TemplateTransformer;
use rbDuplicatePost\Transformers\CategoriesTransformer;
use rbDuplicatePost\Transformers\TagsTransformer;
use rbDuplicatePost\Transformers\AuthorTransformer;
use rbDuplicatePost\Transformers\PasswordTransformer;
use rbDuplicatePost\Transformers\MenuOrderTransformer;

use rbDuplicatePost\AfterCopyTransformers\FormatTransformer;
use rbDuplicatePost\AfterCopyTransformers\HistoryTransformer;
use rbDuplicatePost\AfterCopyTransformers\ChildrenTransformer;
use rbDuplicatePost\AfterCopyTransformers\AttachmentsTransformer;
use rbDuplicatePost\AfterCopyTransformers\FeaturedImageTransformer;
use rbDuplicatePost\AfterCopyTransformers\ContentImagesTransformer;
use rbDuplicatePost\AfterCopyTransformers\MetaTransformer;
use rbDuplicatePost\AfterCopyTransformers\TermTransformer;
use rbDuplicatePost\AfterCopyTransformers\ACFTransformer;
use rbDuplicatePost\AfterCopyTransformers\YoastSeoTransformer;
use rbDuplicatePost\AfterCopyTransformers\RankMathTransformer;
use rbDuplicatePost\AfterCopyTransformers\AllInOneSeoTransformer;
use rbDuplicatePost\AfterCopyTransformers\CommentsTransformer;
use rbDuplicatePost\AfterCopyTransformers\ScenarioTransformer;

use rbDuplicatePost\Contexts\TransformerContext;
use rbDuplicatePost\Contexts\BlogPostContext;
use rbDuplicatePost\Copiers\PostTermCopier;


/**
 * Class PostProcessor
 * 
 * @package rbDuplicatePost
 */
class PostProcessor {
    private array $transformers;
    private array $afterCopyTransformers;
    private OptionsManager $options_manager;
    
    public function __construct( OptionsManager $options_manager ) {
        $this->options_manager = $options_manager;
        $this->transformers = $this->registerTransformers();
        $this->afterCopyTransformers = $this->registerAfterCopyTransformers();
    }
    
    private function registerTransformers(): array {
        return array(
            new TitleTransformer(),
            new DateTransformer(),
            new StatusTransformer(),
            new ContentTransformer(),
            new ExcerptTransformer(),
            new TemplateTransformer(),
            new CategoriesTransformer(),
            new TagsTransformer(),
            new SlugTransformer(),
            new AuthorTransformer(),
            new PasswordTransformer(),
            new MenuOrderTransformer(),
        );
    }

    private function registerAfterCopyTransformers(): array {
        $transformers = array(
            new MetaTransformer(),
            new TermTransformer(),
            new FormatTransformer(),
            new ChildrenTransformer(),
            new AttachmentsTransformer(),
            new FeaturedImageTransformer(),
            new ContentImagesTransformer(),
            new ACFTransformer(),
            new YoastSeoTransformer(),
            new RankMathTransformer(),
            new AllInOneSeoTransformer(),
            new CommentsTransformer(),
            /* the Assistant's steps on the copy, after everything is copied */
            new ScenarioTransformer(),
        );

        /**
         * Extra steps after the copy is created (the PRO key adds shop and
         * page-builder copiers here). Each item must implement PostAfterCopyTransformer.
         */
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- rb_duplicate_post_ is this plugin's prefix; Plugin Check only guesses rbDuplicatePost from the namespace
        $extra = apply_filters( 'rb_duplicate_post_after_copy_transformers', array() );
        foreach ( (array) $extra as $transformer ) {
            if ( $transformer instanceof \rbDuplicatePost\Contracts\PostAfterCopyTransformer ) {
                $transformers[] = $transformer;
            }
        }

        /* Important this is last */
        $transformers[] = new HistoryTransformer();
        return $transformers;
    }
    
    /**
     * Process post.
     *
     * @param int $source_post_id
     * @param int $profile_id
     * @param int $target_blog_id 0 = current blog
     * @param int $source_blog_id 0 = current blog
     * @return int New post ID on the target blog
     * @throws \Exception
     */
    public function processPost(
        int $source_post_id,
        int $profile_id = 0,
        int $target_blog_id = 0,
        int $source_blog_id = 0
    ): int {

        $source_blog_id = $source_blog_id > 0 ? $source_blog_id : get_current_blog_id();
        $target_blog_id = Multisite::normalize_target_blog_id( $target_blog_id );

        if ( is_multisite() ) {
            if ( ! Multisite::blog_exists( $source_blog_id ) ) {
                throw new \InvalidArgumentException( 'Source site does not exist.' );
            }
            if ( ! Multisite::blog_exists( $target_blog_id ) ) {
                throw new \InvalidArgumentException( 'Target site does not exist.' );
            }
            if ( $target_blog_id !== $source_blog_id && ! Multisite::is_cross_site_enabled() ) {
                throw new \RuntimeException( \rbDuplicatePost\ProCheck::isActive()
                    ? 'Cross-site duplication is disabled.'
                    : 'Copying to another site of the network is a PRO feature.' );
            }
            // a user's sites; without a user (Auto copy in cron, WP-CLI) the settings decide,
            // as on a single site - nothing a visitor can reach copies without a user
            if ( is_user_logged_in() && ! Multisite::can_user_copy_from_blog( $source_blog_id ) ) {
                throw new \RuntimeException( 'You are not allowed to copy from the source site.' );
            }
            if ( is_user_logged_in() && ! Multisite::can_user_copy_to_blog( $target_blog_id ) ) {
                throw new \RuntimeException( 'You are not allowed to copy to the target site.' );
            }
        }

        return Utils::withoutKsesWhenNoUser( function () use ( $source_post_id, $profile_id, $target_blog_id, $source_blog_id ) {
            return $this->copyPost( $source_post_id, $profile_id, $target_blog_id, $source_blog_id );
        } );
    }

    /** processPost() once the sites are checked: the copy itself */
    private function copyPost( int $source_post_id, int $profile_id, int $target_blog_id, int $source_blog_id ): int {

        $source_post_data = $this->readSourcePost( $source_post_id, $source_blog_id );
        
        $options = $this->options_manager->getOptions( $profile_id );
        $options = PrepareOptions::handle( $options, $source_post_id, $profile_id );

        $options['_rb_ms'] = array(
            'source_blog_id' => $source_blog_id,
            'target_blog_id' => $target_blog_id,
            'media_mode'     => Multisite::get_media_mode(),
            'copy_media'     => Multisite::should_copy_media_files( $source_blog_id, $target_blog_id ),
            'media_flag'     => Multisite::get_media_duplicate_flag( $source_blog_id, $target_blog_id ),
        );

        $transformer_context = TransformerContext::from_blog_ids(
            $source_post_id,
            $source_blog_id,
            0,
            $target_blog_id,
            $options,
            $profile_id
        );
        
        $modified_data = $this->applyTransformers( $transformer_context, $source_post_data );

        /**
         * The new post as it is about to be created (the PRO key changes its type here).
         *
         * @param array              $modified_data post fields for wp_insert_post()
         * @param TransformerContext $transformer_context
         */
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- rb_duplicate_post_ is this plugin's prefix; Plugin Check only guesses rbDuplicatePost from the namespace
        $modified_data = apply_filters( 'rb_duplicate_post_new_post_data', $modified_data, $transformer_context );
        $modified_data = $this->limitStatus( $modified_data, $target_blog_id );
        // the parent's ID belongs to the source site: on another site it names some other post
        // (a child copied with its parent gets the parent's copy from PostChildrenCopier)
        if ( $source_blog_id !== $target_blog_id ) {
            $modified_data['post_parent'] = 0;
        }
        $new_post_id   = $this->createNewPost( $modified_data, $target_blog_id );
        
        $transformer_context_after_create = TransformerContext::from_blog_ids(
            $source_post_id,
            $source_blog_id,
            $new_post_id,
            $target_blog_id,
            $options,
            $profile_id
        );

        $term_map = array();
        if ( $this->is_option_enabled( $options, 'copyTaxonomies' ) ) {
            $term_result = PostTermCopier::copy_all_taxonomies_with_map(
                $transformer_context_after_create->to_operation_context()
            );
            $term_map = isset( $term_result['map'] ) && is_array( $term_result['map'] )
                ? $term_result['map']
                : array();
        }

        // Categories and tags were copied before the post was made (CategoriesTransformer,
        // TagsTransformer): their map too, for the SEO copiers' primary terms. On another
        // site of a network the ids differ; a term the copy did not get maps to nothing,
        // so a primary term is dropped rather than left pointing at another site's id.
        foreach ( array( 'category', 'post_tag' ) as $taxonomy ) {
            if ( ! isset( $term_map[ $taxonomy ] ) ) {
                $map = $this->map_terms_by_slug( $source_post_id, $source_blog_id, $new_post_id, $target_blog_id, $taxonomy );
                if ( $map || $source_blog_id !== $target_blog_id ) {
                    $term_map[ $taxonomy ] = $map;
                }
            }
        }

        $data = $transformer_context_after_create->get_data();
        $data['term_map'] = $term_map;
        $transformer_context_after_create->set_data( $data );
        
        $this->applyAfterCopyTransformers( $transformer_context_after_create );

        return $new_post_id;
    }

    /**
     * The source post's terms of a taxonomy => the copy's term of the same slug.
     *
     * @return array<int,int>
     */
    private function map_terms_by_slug( int $source_post_id, int $source_blog_id, int $new_post_id, int $target_blog_id, string $taxonomy ): array {
        $read = function ( int $blog_id, int $post_id ) use ( $taxonomy ) {
            $switched = is_multisite() && get_current_blog_id() !== $blog_id;
            if ( $switched ) {
                switch_to_blog( $blog_id );
            }
            $terms = wp_get_object_terms( $post_id, $taxonomy );
            if ( $switched ) {
                restore_current_blog();
            }
            return is_wp_error( $terms ) ? array() : $terms;
        };
        $by_slug = array();
        foreach ( $read( $target_blog_id, $new_post_id ) as $term ) {
            $by_slug[ $term->slug ] = (int) $term->term_id;
        }
        $map = array();
        foreach ( $read( $source_blog_id, $source_post_id ) as $term ) {
            if ( isset( $by_slug[ $term->slug ] ) ) {
                $map[ (int) $term->term_id ] = $by_slug[ $term->slug ];
            }
        }
        return $map;
    }

    /**
     * A copy goes live (published, scheduled, private) only for a user who may publish posts
     * of its type on the site it is made on, whatever the profile or its scenario asks: else it
     * waits for review, as in WordPress's editor. Without a user (cron, WP-CLI) the settings decide.
     */
    private function limitStatus( array $post_data, int $target_blog_id ): array {
        $status = (string) ( $post_data['post_status'] ?? '' );
        if ( ! in_array( $status, array( 'publish', 'future', 'private' ), true ) || ! is_user_logged_in() ) {
            return $post_data;
        }
        $type = (string) ( $post_data['post_type'] ?? 'post' );
        $can  = Multisite::with_blog( $target_blog_id, function () use ( $type ) {
            $object = get_post_type_object( $type );
            return $object && current_user_can( $object->cap->publish_posts );
        } );
        if ( ! $can ) {
            $post_data['post_status'] = 'pending';
        }
        return $post_data;
    }

    private function is_option_enabled( array $options, string $name ): bool {
        if ( ! isset( $options[ $name ]['value'] ) ) {
            return false;
        }
        return (bool) $options[ $name ]['value'];
    }
    
    private function applyAfterCopyTransformers( TransformerContext $context ) {
        foreach ( $this->afterCopyTransformers as $transformer ) {
            $transformer->transform( $context );
        }
    }

    private function applyTransformers( TransformerContext $context, array $post_data ): array {
        $result = $post_data;
        foreach ( $this->transformers as $transformer ) {
            $result = $transformer->transform( $context, $result );
        }
        return $result;
    }
    
    private function readSourcePost( int $post_id, int $blog_id = 0 ): array {
        $blog_id = $blog_id > 0 ? $blog_id : get_current_blog_id();
        $ctx     = new BlogPostContext( $post_id, $blog_id );
        $did     = $ctx->maybe_switch_to_blog();
        $post    = get_post( $post_id, ARRAY_A );
        BlogPostContext::maybe_restore_blog( $did );

        if ( ! $post ) {
            throw new \Exception( 'Post not found' );
        }
        return $post;
    }
    
    private function createNewPost( array $post_data, int $target_blog_id = 0 ): int {
        if ( isset( $post_data['ID'] ) ) {
            unset( $post_data['ID'] );
        }
        if ( isset( $post_data['guid'] ) ) {
            unset( $post_data['guid'] );
        }

        $target_blog_id = Multisite::normalize_target_blog_id( $target_blog_id );
        $switched       = false;

        if ( is_multisite() && get_current_blog_id() !== $target_blog_id ) {
            switch_to_blog( $target_blog_id );
            $switched = true;
        }

        if ( ! empty( $post_data['post_author'] ) ) {
            $author_id = (int) $post_data['post_author'];
            if ( ! get_userdata( $author_id ) || ( is_multisite() && ! is_user_member_of_blog( $author_id, $target_blog_id ) ) ) {
                $post_data['post_author'] = get_current_user_id();
            }
        }

        // a template the (target site's) theme does not have - left from an earlier theme -
        // makes wp_insert_post() fail after it has already created the post: copy without it
        if ( ! empty( $post_data['page_template'] ) && 'default' !== $post_data['page_template'] ) {
            $post_type = isset( $post_data['post_type'] ) ? (string) $post_data['post_type'] : 'page';
            $templates = wp_get_theme()->get_page_templates( null, $post_type );
            if ( ! isset( $templates[ $post_data['page_template'] ] ) ) {
                unset( $post_data['page_template'] );
            }
        }

        $new_post_id = wp_insert_post( $post_data, true );

        if ( $switched ) {
            restore_current_blog();
        }

        if ( is_wp_error( $new_post_id ) ) {
            throw new \Exception( 'Failed to create post: ' . esc_html( $new_post_id->get_error_message() ) );
        }

        return (int) $new_post_id;
    }
}
