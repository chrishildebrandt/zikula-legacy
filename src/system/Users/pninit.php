<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2001, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: pninit.php 27362 2009-11-02 15:45:10Z mateo $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula_System_Modules
 * @subpackage Users
*/

/**
 * initialise the users module
 *
 * This function is only ever called once during the lifetime of a particular
 * module instance.
 * This function MUST exist in the pninit file for a module
 *
 * @author       Mark West
 * @return       bool       true on success, false otherwise
 */
function users_init()
{
    if (!DBUtil::createTable('session_info')) {
        return false;
    }

    if (!DBUtil::createTable('users')) {
        return false;
    }

    if (!DBUtil::createTable('users_temp')) {
        return false;
    }

    // Set default values for module
    users_defaultdata();

    pnModSetVar('Users', 'itemsperpage', 25);
    pnModSetVar('Users', 'accountdisplaygraphics', 1);
    pnModSetVar('Users', 'accountitemsperpage', 25);
    pnModSetVar('Users', 'accountitemsperrow', 5);
    pnModSetVar('Users', 'changepassword', 1);
    pnModSetVar('Users', 'changeemail', 1);
    pnModSetVar('Users', 'reg_allowreg', 1);
    pnModSetVar('Users', 'reg_verifyemail', 1);
    pnModSetVar('Users', 'reg_Illegalusername', 'root adm linux webmaster admin god administrator administrador nobody anonymous anonimo');
    pnModSetVar('Users', 'reg_Illegaldomains', '');
    pnModSetVar('Users', 'reg_Illegaluseragents', '');
    pnModSetVar('Users', 'reg_noregreasons', __('Sorry! New user registration is currently disabled.'));
    pnModSetVar('Users', 'reg_uniemail', 1);
    pnModSetVar('Users', 'reg_notifyemail', '');
    pnModSetVar('Users', 'reg_optitems', 0);
    pnModSetVar('Users', 'userimg', 'images/menu');
    pnModSetVar('Users', 'avatarpath', 'images/avatar');
    pnModSetVar('Users', 'minage', 13);
    pnModSetVar('Users', 'minpass', 5);
    pnModSetVar('Users', 'anonymous', 'Guest');
    pnModSetVar('Users', 'savelastlogindate', 0);
    pnModSetVar('Users', 'loginviaoption', 0);
    pnModSetVar('Users', 'lowercaseuname', 0);
    pnModSetVar('Users', 'moderation', 0);
    pnModSetVar('Users', 'hash_method', 'sha256');
    pnModSetVar('Users', 'login_redirect', 1);
    pnModSetvar('Users', 'reg_question', '');
    pnModSetvar('Users', 'reg_answer', '');
    pnModSetvar('Users', 'idnnames', 1);

    // Initialisation successful
    return true;
}

/**
 * upgrade the users module from an old version
 *
 * This function must consider all the released versions of the module!
 * If the upgrade fails at some point, it returns the last upgraded version.
 *
 * @author       Mark West
 * @param        string   $oldVersion   version number string to upgrade from
 * @return       mixed    true on success, last valid version string or false if fails
 */
function users_upgrade($oldversion)
{
    // Upgrade dependent on old version number
    switch ($oldversion)
    {
        case '1.9':
            // dependencies do not work during upgrade yet so we check it manually
            $objectdata = pnModGetInfo(pnModGetIDFromName('ObjectData'));
            if (version_compare($objectdata['version'], '1.01', '<=')) {
                LogUtil::registerError(__("Notice: The object data manager module ('ObjectData') needs to be upgraded to version 1.02 before you can upgrade this module."));
                return '1.9';
            }

            if (!defined('_PNINSTALLVER') && !pnModAvailable('Profile')) {
                LogUtil::registerError(__("Notice: The profile manager module ('Profile') must be installed and activated before you can proceed with this upgrade step."));
                return '1.9';
            }

            // move duds to attributes
            users_migrate_duds_to_attributes();

            pnModSetVar('Users', 'accountdisplaygraphics', pnModGetVar('Profile', 'displaygraphics', 1));
            pnModSetVar('Users', 'accountitemsperpage',    pnModGetVar('Profile', 'itemsperpage', 25));
            pnModSetVar('Users', 'accountitemsperrow',     pnModGetVar('Profile', 'itemsperrow', 5));
            pnModSetVar('Users', 'changepassword', 1);
            pnModSetVar('Users', 'changeemail', 1);

        case '1.10':
        case '1.11':
            users_upgrade_migrateSerialisedUserTemp();
        case '1.12':
            pnModSetVar('Users', 'avatarpath', 'images/avatar');
            pnModSetVar('Users', 'lowercaseuname', 1);
        case '1.13':
            
    }

    // Update successful
    return true;
}

/**
 * delete the users module
 *
 * This function is only ever called once during the lifetime of a particular
 * module instance
 * This function MUST exist in the pninit file for a module
 *
 * Since the users module should never be deleted we'all always return false here
 * @author       Mark West
 * @return       bool       false
 */
function users_delete()
{
    // Deletion not allowed
    return false;
}

/**
 * create the default data for the users module
 *
 * This function is only ever called once during the lifetime of a particular
 * module instance
 *
 * Since the users module should never be deleted we'all always return false here
 * @author       Mark West
 * @return       bool       false
 */
function users_defaultdata()
{
    // Anonymous
    $record = array();
    $record['uid']             = '1';
    $record['uname']           = 'guest';
    $record['pass']            = '';
    $record['storynum']        = '10';
    $record['umode']           = '';
    $record['uorder']          = '0';
    $record['thold']           = '0';
    $record['noscore']         = '0';
    $record['bio']             = '';
    $record['ublockon']        = '0';
    $record['ublock']          = '';
    $record['theme']           = '';
    $record['commentmax']      = '4096';
    $record['counter']         = '0';
    $record['timezone_offset'] = '0';
    $record['hash_method']     = '1';
    $record['activated']       = '1';
    DBUtil::insertObject($record, 'users', 'uid', true);

    // Admin
    $record = array();
    $record['uid']             = '2';
    $record['uname']           = 'admin';
    $record['email']           = '';
    $record['pass']            = 'dc647eb65e6711e155375218212b3964';
    $record['storynum']        = '10';
    $record['umode']           = '';
    $record['uorder']          = '0';
    $record['thold']           = '0';
    $record['noscore']         = '0';
    $record['bio']             = '';
    $record['ublockon']        = '0';
    $record['ublock']          = '';
    $record['theme']           = '';
    $record['commentmax']      = '4096';
    $record['counter']         = '0';
    $record['timezone_offset'] = '0';
    $record['activated']       = '1';

    DBUtil::insertObject($record, 'users', 'uid', true);
}

/**
 * for the upgrade script, 0.8MS1 to 0.8.MS2 update
 *
 * @return boot
 */
function users_changestructure()
{
    // Easy drop and recreate for session table
    DBUtil::dropTable('session_info');
    DBUtil::createTable('session_info');

    return true;
}

/**
 * migrate old DUDs to attributes
 *
 */
function users_migrate_duds_to_attributes()
{
    pnModDBInfoLoad('Profile');
    pnModDBInfoLoad('ObjectData');
    $pntable = pnDBGetTables();
    $udtable   = $pntable['user_data'];
    $udcolumn  = $pntable['user_data_column'];
    $objtable  = $pntable['objectdata_attributes'];
    $objcolumn = $pntable['objectdata_attributes_column'];

    // load the user properties into an assoc array with prop_id as key
    $userprops = DBUtil::selectObjectArray('user_property', '', '', -1, -1, 'prop_id');

    // this array maps old DUDs to new attributes
    $mappingarray = array('_UREALNAME'      => 'realname',
                          '_UFAKEMAIL'      => 'publicemail',
                          '_YOURHOMEPAGE'   => 'url',
                          '_TIMEZONEOFFSET' => 'tzoffset',
                          '_YOURAVATAR'     => 'avatar',
                          '_YLOCATION'      => 'city',
                          '_YICQ'           => 'icq',
                          '_YAIM'           => 'aim',
                          '_YYIM'           => 'yim',
                          '_YMSNM'          => 'msnm',
                          '_YOCCUPATION'    => 'occupation',
                          '_SIGNATURE'      => 'signature',
                          '_EXTRAINFO'      => 'extrainfo',
                          '_YINTERESTS'     => 'interests');

    $aks = array_keys($userprops);
    // expand the old DUDs with the new attribute names for the real conversion
    foreach ($aks as $ak) {
        if ($userprops[$ak]['prop_label'] == '_PASSWORD' || $userprops[$ak]['prop_label'] == '_UREALEMAIL') {
            // password and real email are core information, not attributes!
            unset($userprops[$ak]);
        } elseif (array_key_exists($userprops[$ak]['prop_label'], $mappingarray)) {
            // old DUD found, replace it
            $userprops[$ak]['attribute_name'] = $mappingarray[$userprops[$ak]['prop_label']];
        } else {
            // user defined DUD found, do not touch it
            $userprops[$ak]['attribute_name'] = $userprops[$ak]['prop_label'];
        }
    }

    // One sql per user property to move all data from user_data table to the attributes table
    // This is the most efficient way to do this. During a test upgrade this took less than 0.3 secs for 6700
    // users and >15K of properties.
    foreach ($userprops as $userprop) {
        // Set cr_date and lu_date to now, cr_uid and lu_uid will be the uid of the user the attributes belong to
        $timestring = date('Y-m-d H:i:s');
        $sql = "INSERT INTO " . $objtable . " (" . $objcolumn['attribute_name'] . ",
                                               " . $objcolumn['object_type'] . ",
                                               " . $objcolumn['object_id'] . ",
                                               " . $objcolumn['value'] . ",
                                               " . $objcolumn['cr_date'] . ",
                                               " . $objcolumn['cr_uid'] . ",
                                               " . $objcolumn['lu_date'] . ",
                                               " . $objcolumn['lu_uid'] . ")
                SELECT '" . $userprop['attribute_name'] . "',
                       'users',
                       " . $udcolumn['uda_uid'] . ",
                       " . $udcolumn['uda_value'] . ",
                       '" . $timestring . "',
                       " . $udcolumn['uda_uid'] . ",
                       '" . $timestring . "',
                       " . $udcolumn['uda_uid'] . "
                FROM " . $udtable . "
                WHERE " . $udcolumn['uda_propid'] . "='" . $userprop['prop_id'] . "'";
        DBUtil::executeSQL($sql);
    }

    // done :-)
    return true;
}

function users_upgrade_migrateSerialisedUserTemp()
{
    $array = DBUtil::selectObjectArray('users_temp');
    foreach ($array as $obj) {
        if (DataUtil::is_serialized($obj['dynamics'])) {
            $obj['dynamics'] = serialize(DataUtil::mb_unserialize($obj['dynamics']));
        }
        DBUtil::updateObject($obj, 'users_temp', '', 'tid');
    }
}
