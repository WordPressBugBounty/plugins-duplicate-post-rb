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

namespace rbDuplicatePost\restapi;

defined('WPINC') || exit;

class REST
{
    public function __construct()
    {
        new \rbDuplicatePost\restapi\REST_DuplicatePost_Controller();
        new \rbDuplicatePost\restapi\REST_General_Options_Controller();
        new \rbDuplicatePost\restapi\REST_Profile_Controller();
        new \rbDuplicatePost\restapi\REST_Profile_Options_Controller();
        new \rbDuplicatePost\restapi\REST_Profile_Export_Controller();
        new \rbDuplicatePost\restapi\REST_Profile_Import_Controller();
    }
}
