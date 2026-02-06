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

use rbDuplicatePost\Contracts\PostTransformer;
use rbDuplicatePost\Contracts\PostAfterCopyTransformer;
use rbDuplicatePost\OptionsManager;

use rbDuplicatePost\Transformers\TitleTransformer;
use rbDuplicatePost\Transformers\DateTransformer;
use rbDuplicatePost\Transformers\SlugTransformer;
use rbDuplicatePost\Transformers\StatusTransformer;
use rbDuplicatePost\Transformers\ExcerptTransformer;
use rbDuplicatePost\Transformers\ContentTransformer;
use rbDuplicatePost\Transformers\TemplateTransformer;
use rbDuplicatePost\Transformers\AuthorTransformer;
use rbDuplicatePost\Transformers\PasswordTransformer;
use rbDuplicatePost\Transformers\MenuOrderTransformer;
use rbDuplicatePost\Transformers\CategoriesTransformer;
use rbDuplicatePost\Transformers\TagsTransformer;

use rbDuplicatePost\AfterCopyTransformers\FormatTransformer;
use rbDuplicatePost\AfterCopyTransformers\HistoryTransformer;
use rbDuplicatePost\AfterCopyTransformers\ChildrenTransformer;
use rbDuplicatePost\AfterCopyTransformers\AttachmentsTransformer;
use rbDuplicatePost\AfterCopyTransformers\FeaturedImageTransformer;
use rbDuplicatePost\AfterCopyTransformers\MetaTransformer;
use rbDuplicatePost\AfterCopyTransformers\TermTransformer;


use rbDuplicatePost\Contexts\TransformerContext;


/**
 * Class PostProcessor
 * 
 * @package rbDuplicatePost
 */
class PostProcessor {
    // Transformers
    private array $transformers;
    // After Copy Transformers
    private array $afterCopyTransformers;
    // Options manager
    private OptionsManager $options_manager;
    
    /**
     * Constructor.
     *
     * @param OptionsManager $options_manager
     */
    public function __construct(OptionsManager $options_manager) {
        $this->options_manager = $options_manager;
        $this->transformers = $this->registerTransformers();
        $this->afterCopyTransformers = $this->registerAfterCopyTransformers();
    }
    
    /**
     * Register transformers.
     *
     * @return array        
     */
    private function registerTransformers(): array {
        return array(
            new TitleTransformer(),
            new DateTransformer(),
            new SlugTransformer(),
            new StatusTransformer(),
            new ContentTransformer(),
            new ExcerptTransformer(),
            new TemplateTransformer(),
            new CategoriesTransformer(),
            new TagsTransformer(),
        );
    }


    /**
     * Register transformers.
     *
     * @return array        
     */
    private function registerAfterCopyTransformers(): array {
        return array(
            new MetaTransformer(),
            new TermTransformer(),
            new FormatTransformer(),
            
            new ChildrenTransformer(),
            new AttachmentsTransformer(),
            new FeaturedImageTransformer(),
            
            /* Important this is last */
            new HistoryTransformer(),
        );
    }
    
    /**
     * Process post.
     *
     * @param int $source_post_id
     * @param int $profile_id
     * @return int
     * @throws \Exception
     */
    public function processPost(int $source_post_id, int $profile_id = 0): int {
        $source_post = $this->readSourcePost($source_post_id);
        
        $options = $this->options_manager->getOptions($profile_id);

        $transformer_context = TransformerContext::from_current_blog( $source_post_id, 0, $options, $profile_id, );
        
        $modified_data = $this->applyTransformers($transformer_context, $source_post);

        $new_post_id =  $this->createNewPost($modified_data);

        $transformer_context_after_create = TransformerContext::from_current_blog( $source_post_id, $new_post_id, $options, $profile_id );
        
        $this->applyAfterCopyTransformers($transformer_context_after_create);

        return $new_post_id;
    }
    
    /**
     * Apply post transformers to post data.
     *
     * @param TransformerContext $context
     * @return void
     */
    private function applyAfterCopyTransformers(TransformerContext $context): void {
        foreach ($this->afterCopyTransformers as $transformer) {
            $transformer->transform($context);
        }
    }
    /**
     * Apply transformers to post data.
     *
     * @param TransformerContext $context
     * @param array $post_data
     * @return array
     */
    private function applyTransformers(TransformerContext $context, array $post_data): array {
        $result = $post_data;
        
        foreach ($this->transformers as $transformer) {
            $result = $transformer->transform($context, $result);
        }
        
        return $result;
    }
    
    /**
     * Read source post data.
     *
     * @param int $post_id
     * @return array
     * @throws \Exception
     */
    private function readSourcePost(int $post_id): array {
        // Get post data
        $post = get_post($post_id, ARRAY_A);
        if (!$post) {
            throw new \Exception("Post not found");
        }
        // Return post data
        return $post;
    }
    
    private function createNewPost(array $post_data): int {
        if(isset($post_data['ID'])) {
             // Reset ID to create new post
            unset($post_data['ID']);
        }

        // Create new post
        $new_post_id = wp_insert_post($post_data);

        // Check if post creation failed
        if (is_wp_error($new_post_id)) {
            throw new \Exception("Failed to create post: " . $new_post_id->get_error_message());
        }
        
        // Return new post ID
        return $new_post_id;
    }
}