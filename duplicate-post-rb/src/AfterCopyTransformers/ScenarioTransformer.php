<?php
/*
*      RB Duplicate Post
*      Version: 2.3.0
*      By RbPlugin
*
*      Contact: https://rbplugins.com
*      Created: 2025
*      Licensed under the GPLv3 license - http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace rbDuplicatePost\AfterCopyTransformers;

defined( 'WPINC' ) || exit;

use rbDuplicatePost\Contracts\AbstractPostAfterCopyTransformer;
use rbDuplicatePost\Contexts\TransformerContext;
use rbDuplicatePost\Scenario\Scenario;

/**
 * The Assistant's steps that act on the created copy (custom fields, terms):
 * after everything else is copied, so they change the copy's final data.
 */
class ScenarioTransformer extends AbstractPostAfterCopyTransformer {

    protected static function get_option_name(): string {
        return 'scenario';
    }

    public function transform( TransformerContext $context ) {
        Scenario::apply_after( $context );
    }
}
