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

defined('WPINC') || exit;

class Constants
{
    // Option name for default profile ID
    public const OPTION_NAME_DEFAULT_PROFILE = 'rb_duplicate_post_default_profile_id';

    // Option name for last viewed profile ID
    public const OPTION_NAME_PROFILE_LASTVIEW = 'rb_duplicate_post_profile_lastview_id';

    // Option name to flag that rewrite rules need to be flushed after installation
    public const INSTALL_FLAG_OPTION_NAME = 'rb_duplicate_post_after_install';
    
    // Option name for storing plugin version
    public const VERSION_OPTION_NAME = 'rb-duplicate-post-version';

    // Option name for storing plugin options
    public const OPTION_NAME = 'rb-duplicate-post-options';

    // Options name for storing general options
    public const GENERAL_OPTIONS_NAME = 'rb-duplicate-post-general-options';

    // Success or fail status constants
    public const SUCCESS = 'success';
    public const FAIL = 'fail';

    // Action name for handling duplicate post
    public const COPY_ACTION_NAME = 'rb_duplicate_action';

    // Option name for storing counter value
    public const COUNTER_OPTION_NAME = 'counterStartValue';

    // Notification types
    public const NOTIFICATION_TYPE_POST_COPIED = 'POST_COPIED';   

    /* Allowed post statuses */
    public const ALLOWED_POST_STATUSES = ['publish', 'private', 'draft', 'future', 'pending', 'inherit']; //, 'trash'


    /* Allowed post types */
    public const ALLOWED_POST_TYPES = ['post', 'page'];

    public const HISTORY_META_KEY = 'rb-duplicate-post-history';
}