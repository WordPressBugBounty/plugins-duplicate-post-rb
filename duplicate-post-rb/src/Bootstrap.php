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

defined('WPINC') || exit;

class Bootstrap
{
    public function __construct()
    {

        /* activation start */
        new \rbDuplicatePost\PluginActivator();   

        \rbDuplicatePost\Core\DuplicatorFactory::registerProvider(new \rbDuplicatePost\Providers\PostDuplicatorProvider());

        /* Profile type */
        new \rbDuplicatePost\ProfileType();

        /* REST API */
        new \rbDuplicatePost\restapi\REST();

        /* Admin  */
        new \rbDuplicatePost\AdminUI\AdminUI();

        /* the Assistant: scenario steps on the fields of every new copy */
        \rbDuplicatePost\Scenario\Scenario::init();

        /* Rewrite & Republish: also on cron, for scheduled rewrites */
        new \rbDuplicatePost\Rewrite\RewriteRepublish();

        /* CLI */
        if (defined('WP_CLI') && WP_CLI) {
            new \rbDuplicatePost\cli\Commands();
        }

        \rbDuplicatePost\Log\LogCleaner::schedule();
    }
}
