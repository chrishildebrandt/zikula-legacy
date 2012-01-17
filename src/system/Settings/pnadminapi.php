<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2002, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: pnadminapi.php 27363 2009-11-02 16:40:08Z mateo $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @author Simon Wunderlin
 * @package Zikula_System_Modules
 * @subpackage Settings
 */

/**
 * get available admin panel links
 *
 * @author Mark West
 * @return array array of admin links
 */
function Settings_adminapi_getlinks()
{
    $links = array();

    $domain = ZLanguage::getModuleDomain('settings');
    if (SecurityUtil::checkPermission('Settings::', '::', ACCESS_ADMIN)) {
        $links[] = array('url' => pnModURL('Settings', 'admin', 'modifyconfig'), 'text' => __('Owner settings'));
        $links[] = array('url' => pnModURL('Settings', 'admin', 'multilingual'), 'text' => __('Localisation settings'));
        $links[] = array('url' => pnModURL('Settings', 'admin', 'errorhandling'), 'text' => __('Error settings'));
    }

    return $links;
}

/**
 * clear all compiled and cache directories
 *
 * This function simply calls the theme and pnrender modules to refresh the entire site
 *
 * @author Mark West
 */
function settings_adminapi_clearallcompiledcaches()
{
    pnModAPIFunc('pnRender', 'user', 'clear_compiled');
    pnModAPIFunc('pnRender', 'user', 'clear_cache');
    pnModAPIFunc('Theme', 'user', 'clear_compiled');
    pnModAPIFunc('Theme', 'user', 'clear_cache');
    return true;
}
