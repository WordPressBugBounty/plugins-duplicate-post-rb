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

namespace rbDuplicatePost\AdminUI;

defined('WPINC') || exit;

class AdminUI
{
    public function __construct()
    {
        new \rbDuplicatePost\AdminUI\ButtonCopy();
        new \rbDuplicatePost\AdminUI\ClassicEditorButtonCopy();
        new \rbDuplicatePost\AdminUI\GutenbergEditorButtonCopy();
        new \rbDuplicatePost\AdminUI\ButtonBulkCopy();
        new \rbDuplicatePost\ButtonCopyActionHandler();
        new \rbDuplicatePost\ButtonCopyJSLoader();
        new \rbDuplicatePost\AdminUI\AdminOptionsPage();
        new \rbDuplicatePost\AdminUI\AdminBarMenu();
        new \rbDuplicatePost\AdminUI\HistoryMetaBox();
        new \rbDuplicatePost\AdminUI\PluginList();
    }
}