<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2001, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: pnaccountapi.php 27365 2009-11-02 18:30:56Z mateo $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @author Mark West
 * @package Zikula_System_Modules
 * @subpackage Users
 */

/**
 * Return an array of items to show in the your account panel
 *
 * @return   array   indexed array of items
 */
function Users_accountapi_getall($args)
{
    $items = array();

    $modvars = pnModGetVar('Users');

    if ($modvars['changepassword'] == 1) {
        /* show edit password link */
        $items['1'] = array('url' => pnModURL('Users', 'user', 'changepassword'),
                            'module' => 'core',
                            'set' => 'icons/large',
                            'title' => __('Password changer'),
                            'icon' => 'password.gif');
    }

    if ($modvars['changeemail'] == 1) {
        /* show edit email link */
        $items['2'] = array('url' => pnModURL('Users', 'user', 'changeemail'),
                            'module' => 'Users',
                            'title' => __('E-mail address manager'),
                            'icon' => 'changemail.gif');
    }

    // check if the users block exists
    $blocks = pnModAPIFunc('Blocks', 'user', 'getall');
    $mid = pnModGetIDFromName('Users');
    $found = false;
    foreach ($blocks as $block) {
        if ($block['mid'] == $mid && $block['bkey'] == 'user') {
            $found = true;
            break;
        }
    }

    if ($found) {
        $items['3'] = array('url' => pnModURL('Users', 'user', 'usersblock'),
                            'module' => 'core',
                            'set' => 'icons/large',
                            'title' => __('Personal custom block'),
                            'icon' => 'folder_home.gif');
    }

    if (pnConfigGetVar('multilingual')) {
        $items['4'] = array('url' => pnModURL('Users', 'user', 'changelang'),
                            'module' => 'core',
                            'set' => 'icons/large',
                            'title' => __('Language switcher'),
                            'icon' => 'fonts.gif');
    }

    $items['5'] = array('url' => pnModURL('Users', 'user', 'logout'),
                        'module' => 'core',
                        'set' => 'icons/large',
                        'title' => __('Log out'),
                        'icon' => 'exit.gif');

    // Return the items
    return $items;
}
