<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: pninit.php 27272 2009-10-30 11:21:09Z mateo $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula_System_Modules
 * @subpackage Admin
 */

/**
 * initialise the Admin module
 * This function is only ever called once during the lifetime of a particular
 * module instance
 * @author Mark West
 * @return bool true if initialisation succcesful, false otherwise
 */
function Admin_init()
{
    if (!DBUtil::createTable('admin_category')) {
        return false;
    }

    if (!DBUtil::createTable('admin_module')) {
        return false;
    }

    pnModSetVar('Admin', 'modulesperrow', 3);
    pnModSetVar('Admin', 'itemsperpage', 15);
    pnModSetVar('Admin', 'defaultcategory', 5);
    pnModSetVar('Admin', 'modulestylesheet', 'navtabs.css');
    pnModSetVar('Admin', 'admingraphic', 1);
    pnModSetVar('Admin', 'startcategory', 1);
    // change below to 0 before release - just makes it easier doing development meantime - drak
    // we can now leave this at 0 since the code also checks the development flag (config.php) - markwest
    pnModSetVar('Admin', 'ignoreinstallercheck', 0);
    pnModSetVar('Admin', 'admintheme', '');
    pnModSetVar('Admin', 'displaynametype', 1);
    pnModSetVar('Admin', 'moduledescription', 1);

    Admin_defaultdata();

    // Initialisation successful
    return true;
}

/**
 * upgrade the module from an old version
 *
 * This function must consider all the released versions of the module!
 * If the upgrade fails at some point, it returns the last upgraded version.
 *
 * @author       Mark West
 * @param        string   $oldVersion   version number string to upgrade from
 * @return       mixed    true on success, last valid version string or false if fails
 */
function Admin_upgrade($oldversion)
{
    // Upgrade dependent on old version number
    switch ($oldversion)
    {
        case '1.5':
            if (!DBUtil::changeTable('admin_module')) {
                return '1.5';
            }

        case '1.6':
            pnModSetVar('Admin', 'modulesperrow', 3);
            pnModSetVar('Admin', 'itemsperpage', 15);
            pnModSetVar('Admin', 'moduledescription', 1);

        case '1.7':
        case '1.8':
            // future upgrade routines
    }

    // Update successful
    return true;
}

/**
 * delete the Admin module
 * This function is only ever called once during the lifetime of a particular
 * module instance
 * @author Mark West
 * @return bool true if deletetion succcesful, false otherwise
 */
function Admin_delete()
{
    if (!DBUtil::dropTable('admin_module')) {
        return false;
    }

    if (!DBUtil::dropTable('admin_category')) {
        return false;
    }

    pnModDelVar('Admin');

    // Deletion successful
    return true;
}

/**
 * create the default data for the modules module
 *
 * This function is only ever called once during the lifetime of a particular
 * module instance
 *
 * @author       Mark West
 * @return       bool       false
 */
function Admin_defaultdata()
{
    $record = array(array('catname'     => __('System'),
                          'description' => __('Core modules at the heart of operation of the site.')),
                    array('catname'     => __('Layout'),
                          'description' => __("Layout modules for controlling the site's look and feel.")),
                    array('catname'     => __('Users'),
                          'description' => __('Modules for controlling user membership, access rights and profiles.')),
                    array('catname'     => __('Content'),
                          'description' => __('Modules for providing content to your users.')),
                    array('catname'     => __('3rd-party'),
                          'description' => __('3rd-party add-on modules and newly-installed modules.')),
                    array('catname'     => __('Security'),
                          'description' => __('Modules for managing the site\'s security.')),
                    array('catname'     => __('Hooked'),
                          'description' => __('Auxiliary modules designed to be hooked to other modules, to give them added functionality.')));

    DBUtil::insertObjectArray($record, 'admin_category', 'cid');
}
