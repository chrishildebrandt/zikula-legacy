<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2003, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: pnuserapi.php 27281 2009-10-31 06:05:00Z mateo $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula_System_Modules
 * @subpackage Groups
 */

/**
 * get all group items
 * @author Mark West
 * @param int args['startnum'] record number to start get from
 * @param int args['numitems'] number of items to get
 * @return mixed array of group items, or false on failure
 */
function Groups_userapi_getall($args)
{
    // Optional arguments.
    if (!isset($args['startnum']) || !is_numeric($args['startnum'])) {
        $args['startnum'] = 1;
    }
    if (!isset($args['numitems']) || !is_numeric($args['numitems'])) {
        $args['numitems'] = -1;
    }

    $items = array();

    // Security check
    if (!SecurityUtil::checkPermission('Groups::', '::', ACCESS_READ)) {
        return $items;
    }

    // Get datbase setup
    $pntable = pnDBGetTables();
    $groupcolumn = $pntable['groups_column'];

    // Get items
    $orderBy = "ORDER BY $groupcolumn[name]";
    $permFilter = array(array('realm'          => 0,
                              'component_left' => 'Groups',
                              'instance_left'  => 'name',
                              'instance_right' => 'gid',
                              'level'          => ACCESS_READ));
    $objArray = DBUtil::selectObjectArray('groups', '', $orderBy, $args['startnum']-1, $args['numitems'], '', $permFilter);

    // Check for an error with the database code
    if ($objArray === false) {
        return LogUtil::registerError(__('Error! Could not load data.'));
    }

    // Return the items
    return $objArray;
}

/**
 * get a specific group item
 * @author Mark West
 * @param int args['gid'] id of group item to get
 * @param int args['startnum'] record number to start get from (group membership)
 * @param int args['numitems'] number of items to get (group membership)
 * @return mixed item array, or false on failure
 */
function Groups_userapi_get($args)
{
    // Argument check
    if (!isset($args['gid'])) {
        return LogUtil::registerArgsError();
    }

    // Optional arguments.
    if (!isset($args['startnum']) || !is_numeric($args['startnum'])) {
        $args['startnum'] = 1;
    }
    if (!isset($args['numitems']) || !is_numeric($args['numitems'])) {
        $args['numitems'] = -1;
    }

    // Get datbase setup
    $pntable = pnDBGetTables();
    $groupmembershipcolumn = $pntable['group_membership_column'];

    // Get item
    $result = DBUtil::selectObjectByID('groups', $args['gid'], 'gid');
    // Check for an error with the database code
    if (!$result) {
        return false;
    }

    // Get group membership
    $where = "WHERE  $groupmembershipcolumn[gid]= '" . (int)DataUtil::formatForStore($args['gid']) . "'";
    $uidsArray = DBUtil::selectObjectArray('group_membership', $where, '', $args['startnum']-1, $args['numitems'], 'uid');

    // Check for an error with the database code
    if ($uidsArray === false) {
        return false;
    }

    // Security check
    if (!SecurityUtil::checkPermission('Groups::', $result['gid'].'::', ACCESS_READ)) {
        return false;
    }

    // Create the item array
    $result['nbuser'] =  count($uidsArray);
    $result['members'] = $uidsArray;

    $uid = pnUserGetVar('uid');
    if ($uid != 0) {
        $result['status'] = pnModAPIFunc('Groups',
                            'user',
                            'isuserpending',
                            array('gid' => $args['gid'],
                                  'uid' => $uid));
    } else {
        $result['status'] = false;
    }

    // Return the item array
    return $result;
}

/**
 * utility function to count the number of items held by this module
 *
 * @author Mark West
 * @return int number of items held by this module
 */
function Groups_userapi_countitems()
{
    $pntable = pnDBGetTables();
    $grpcol = $pntable['groups_column'];

    $where = "WHERE $grpcol[gtype] >= '1'";
    if (pnModGetVar('Groups', 'hideclosed')) {
        $where .= " AND $grpcol[state] > '0'";
    }
    return DBUtil::selectObjectCount('groups', $where);
}

/**
 * utility function to count the number of items held by this module
 * @param int args['gid'] id of group item to get
 * @author Mark West
 * @return int number of items held by this module
 */
function Groups_userapi_countgroupmembers($args)
{
    // Argument check
    if (!isset($args['gid'])) {
        return LogUtil::registerArgsError();
    }

    // Get datbase setup
    $pntable = pnDBGetTables();
    $groupmembershipcolumn = $pntable['group_membership_column'];

    // Get item
    $where = "WHERE $groupmembershipcolumn[gid] = '" . (int)DataUtil::formatForStore($args['gid']) ."'";
    return DBUtil::selectObjectCount('group_membership', $where);
}

/**
 * get all of a users group memberships
 * @author Mark West
 * @since v1.8
 * @param int args['uid'] user id
 * @param int args['clean'] flag to return an array of GIDs
 * @return mixed array of group items, or false on failure
 */
function Groups_userapi_getusergroups($args)
{
    // Optional arguments.
    if (!isset($args['uid'])) {
        $args['uid'] = pnUserGetVar('uid');
    }
    if (!isset($args['uid'])) {
        return LogUtil::registerArgsError();
    }

    $items = array();

    // Security check
    if (!SecurityUtil::checkPermission('Groups::', '::', ACCESS_READ)) {
        return $items;
    }

    // Get datbase setup
    $pntable = pnDBGetTables();
    $groupmembershipcolumn = $pntable['group_membership_column'];

    // Get item
    $where = "WHERE  $groupmembershipcolumn[uid] = '" . (int)DataUtil::formatForStore($args['uid']) ."'";
    $objArray = DBUtil::selectObjectArray('group_membership', $where, '', -1, -1);

    // Check for an error with the database code
    if ($objArray === false) {
        return LogUtil::registerError(__('Error! Could not load data.'));
    }

    if (isset($args['clean']) && $args['clean']) {
        $newArray = array();
        foreach($objArray as $obj) {
            $newArray[] = $obj['gid'];
        }
        $objArray = $newArray;
    }

    // Return the items
    return $objArray;
}

/**
 * Get all groups
 *
 * @param array $args
 * @return array of groups
 */
function Groups_userapi_getallgroups($args)
{
    // Optional arguments.
    if (!isset($args['startnum']) || !is_numeric($args['startnum'])) {
        $args['startnum'] = 1;
    }
    if (!isset($args['numitems']) || !is_numeric($args['numitems'])) {
        $args['numitems'] = -1;
    }

    $items = array();

    if (!SecurityUtil::checkPermission('Groups::', '::', ACCESS_OVERVIEW)) {
        return $items;
    }

    $pntable = pnDBGetTables();
    $grpcol = $pntable['groups_column'];

    $where = "WHERE $grpcol[gtype] >= '1'";
    if (pnModGetVar('Groups', 'hideclosed')) {
        $where .= " AND $grpcol[state] > '0'";
    }
    $orderBy = "ORDER BY $grpcol[name]";
    $objArray = DBUtil::selectObjectArray('groups', $where, $orderBy, $args['startnum']-1, $args['numitems']);

    if ($objArray === false) {
        return LogUtil::registerError(__('Error! Could not load data.'));
    }

    $uid = pnUserGetVar('uid');

    if ($uid != 0) {
        $memberships = pnModAPIFunc('Groups', 'user', 'getusergroups',
                                    array('uid' => $uid,
                                          'clean' => true));
    } else {
        $memberships = false;
    }

    $row = 1;

    foreach ($objArray as $obj)
    {
        $gid         = $obj['gid'];
        $name        = $obj['name'];
        $gtype       = $obj['gtype'];
        $description = $obj['description'];
        $state       = $obj['state'];
        $nbumax      = $obj['nbumax'];

        if (SecurityUtil::checkPermission('Groups::', $gid.'::', ACCESS_OVERVIEW)) {
            if (!isset($gtype) || is_null($gtype)) {
                $gtype = -2;
            }
            if (is_null($state)) {
                $state = 0;
            }

            $ismember = false;
            if (is_array($memberships) && in_array($gid, $memberships)) {
                $ismember = true;
            }

            if ($uid != 0) {
                $status = pnModAPIFunc('Groups', 'user', 'isuserpending',
                                       array('gid' => $gid,
                                             'uid' => $uid));
            } else {
                $status = false;
            }

            $nbuser = pnModAPIFunc('Groups', 'user', 'countgroupmembers', array('gid' => $gid));

            if (SecurityUtil::checkPermission('Groups::', $gid.'::', ACCESS_READ)) {
                $canview = true;
                $canapply = true;
            } else {
                $canview = false;
                $canapply = false;
            }

            $items[] = array('gid'         => $gid,
                             'name'        => $name,
                             'gtype'       => $gtype,
                             'description' => $description,
                             'state'       => $state,
                             'nbuser'      => (($nbuser <> false) ? $nbuser : 0),
                             'nbumax'      => $nbumax,
                             'ismember'    => $ismember,
                             'status'      => $status,
                             'canview'     => $canview,
                             'canapply'    => $canapply,
                             'islogged'    => pnUserLoggedIn(),
                             'row'         => $row);

            if ($row == 1) {
                $row = 2;
            } else {
                $row = 1;
            }
        }
    }

    return $items;
}

/**
 * save application
 *
 * @param int $args['uid'] user id
 * @param int $args['gid'] group id
 * @return bool
 */
function Groups_userapi_saveapplication($args)
{
    if (!isset($args['gid']) || !isset($args['uid'])) {
        return LogUtil::registerArgsError();
    }

    $item = pnModAPIFunc('Groups', 'user', 'get', array('gid' => $args['gid']));

    if ($item == false) {
        return LogUtil::registerError(__('Sorry! No such item found.'));
    }

    if (!SecurityUtil::checkPermission('Groups::', $args['gid'].'::', ACCESS_READ)) {
        return LogUtil::registerPermissionError();
    }

    // Check in case the user already applied
    $pending = pnModAPIFunc('Groups', 'user', 'isuserpending',
                            array('gid' => $args['gid'],
                                  'uid' => $args['uid']));

    if ($pending) {
        return LogUtil::registerError(__('Error! You have already applied for membership of this group.'));
    }

    $obj = array('gid'         => $args['gid'],
                 'uid'         => $args['uid'],
                 'application' => $args['applytext'],
                 'status'      => '1');

    if (!DBUtil::insertObject($obj, 'group_applications', 'app_id')) {
        return LogUtil::registerError(__('Error! Could not create the new item.'));
    }

    return true;
}

/**
 * delete app from group_applications
 *
 * @param array $args
 * @return bool
 */
function Groups_userapi_cancelapp($args)
{
    if (!isset($args['gid']) || !isset($args['uid'])) {
        return LogUtil::registerArgsError();
    }

    // Checking first if this user is really pending.
    $ispending = pnModAPIFunc('Groups', 'user', 'isuserpending',
                              array('gid' => $args['gid'],
                                    'uid' => $args['uid']));

    if ($ispending == true) {
        $pntable = pnDBGetTables();
        $col = $pntable['group_applications_column'];

        list($gid, $uid) = DataUtil::formatForStore($gid, $uid);
        $where = "WHERE $col[gid] = '".(int)DataUtil::formatForStore($args['gid'])."'
                  AND   $col[uid] = '".(int)DataUtil::formatForStore($args['uid'])."'";

        if (!DBUtil::deleteWhere('group_applications', $where)) {
            return LogUtil::registerError(__('Error! Could not perform the deletion.'));
        }
    }

    return true;
}

/**
 * check if user is pending
 *
 * @param int $args['uid'] user id
 * @param int $args['gid'] group id
 * @return bool
 */
function Groups_userapi_isuserpending($args)
{
    if (!isset($args['gid']) || !isset($args['uid'])) {
        return LogUtil::registerArgsError();
    }

    $pntable = pnDBGetTables();
    $col = $pntable['group_applications_column'];

    // Check in case the user already applied
    $where = " WHERE $col[gid] = '".(int)DataUtil::formatForStore($args['gid'])."'
               AND   $col[uid] = '".(int)DataUtil::formatForStore($args['uid'])."'";
    $result = DBUtil::selectObjectCount('group_applications', $where);

    if ($result >= 1) {
        return true;
    }

    return false;
}

/**
 * update user
 *
 * @param int $args['uid'] user id
 * @param int $args['gtype']
 * @param string $args['action']
 * @return bool
 */
function Groups_userapi_userupdate($args)
{
    if (!isset($args['gid']) || !isset($args['action']) || !isset($args['gtype'])) {
        return LogUtil::registerArgsError();
    }

    if ($args['action'] != 'subscribe' && $args['action'] != 'unsubscribe' && $args['action'] != 'cancel') {
        return LogUtil::registerArgsError();
    }

    if (!pnUserLoggedIn()) {
        LogUtil::registerError(__('Error! You must register for a user account on this site before you can apply for membership of a group.'));
    }

    $userid = pnUserGetVar('uid');

    if ($args['action'] == 'subscribe') {

        if ($args['gtype'] == 2) {
            if (!isset($args['applytext'])) {
                return LogUtil::registerArgsError();
            }

            // We save the user in the application table
            $save = pnModAPIFunc('Groups', 'user', 'saveapplication',
                                 array('gid'       => $args['gid'],
                                       'uid'       => $userid,
                                       'applytext' => $args['applytext']));

            if ($save == false) {
                return false;
            }

            if (pnModGetVar('Groups', 'mailwarning')) {
                $uname = pnUserGetVar('uname', $userid);
                $send = pnModAPIFunc('Mailer', 'user', 'sendmessage',
                                     array('toname'    => __('Administrator'),
                                           'toaddress' => pnConfigGetVar('adminmail'),
                                           'subject'   => __('Group membership application registered'),
                                           'body'      => __f('The registered user %1$s has applied for membership of a group. The details of the application are as follows: %2$s', array($uname, $args['applytext']))));
            }

        } else {
            // We save the user into the groups
            $save = pnModAPIFunc('Groups', 'user', 'adduser',
                                 array('gid' => $args['gid'],
                                       'uid' => $userid));

            if ($save == false) {
                return LogUtil::registerError(__('Error! Could not add the user to the group.'));
            }
        }

    } elseif ($args['action'] == 'cancel') {

        $save = pnModAPIFunc('Groups', 'user', 'cancelapp',
                             array('gid' => $args['gid'],
                                   'uid' => $userid));

        if ($save == false) {
            return LogUtil::registerError(__('Error! Could not remove the user from the group.'));
        }

    } else {

        $save = pnModAPIFunc('Groups', 'user', 'removeuser',
                             array('gid' => $args['gid'],
                                   'uid' => $userid));

        if ($save == false) {
            return LogUtil::registerError(__('Error! Could not remove the user from the group.'));
        }
    }

    return true;
}

/**
 * add a user to a group item
 * @author Mark West
 * @param int $args['gid'] the ID of the item
 * @param int $args['uid'] the ID of the user
 * @return bool true if successful, false otherwise
 */
function Groups_userapi_adduser($args)
{
    // Argument check
    if (!isset($args['gid']) || !isset($args['uid'])) {
        return LogUtil::registerArgsError();
    }

    // The user API function is called.
    $item = pnModAPIFunc('Groups', 'user', 'get', array('gid' => $args['gid']));

    if ($item == false) {
        return LogUtil::registerError(__('Sorry! No such item found.'));
    }

    // Security check
    if (!SecurityUtil::checkPermission('Groups::', $args['gid'].'::', ACCESS_READ)) {
        return LogUtil::registerPermissionError();
    }

    //verify if the user is alredy a member of this group
    $is_member = pnModAPIFunc('Groups', 'user', 'isgroupmember', array('gid' => $args['gid'], 'uid' => $args['uid']));

    // Add item
    if(!$is_member) {
        $obj = array('gid' => $args['gid'],
                     'uid' => $args['uid']);
        $result = DBUtil::insertObject($obj, 'group_membership');
    } else {
        if (isset($args['verbose']) && !$args['verbose']) {
            return false;
        }
        return LogUtil::registerError(__('Error! You are already a member of this group.'));
    }

    // Check for an error with the database code
    if (!$result) {
        return LogUtil::registerError(__('Error! Could not create the new item.'));
    }

    // Let the calling process know that we have finished successfully
    return true;
}

/**
 * remove a user from a group item
 * @author Mark West
 * @param int $args['gid'] the ID of the item
 * @param int $args['uid'] the ID of the user
 * @return bool true if successful, false otherwise
 */
function Groups_userapi_removeuser($args)
{
    if (!isset($args['gid']) || !isset($args['uid'])) {
        return LogUtil::registerArgsError();
    }

    // The user API function is called.
    $item = pnModAPIFunc('Groups', 'user', 'get',
                         array('gid' => $args['gid']));

    if ($item == false) {
        return LogUtil::registerError(__('Sorry! No such item found.'));
    }

    // Security check
    if (!SecurityUtil::checkPermission('Groups::', $args['gid'].'::', ACCESS_READ)) {
        return LogUtil::registerPermissionError();
    }

    // Get datbase setup
    $pntable = pnDBGetTables();
    $groupmembershipcolumn = $pntable['group_membership_column'];

    // delete item
    $where = "WHERE $groupmembershipcolumn[gid] = '" . (int)DataUtil::formatForStore($args['gid']) . "'
              AND   $groupmembershipcolumn[uid] = '" . (int)DataUtil::formatForStore($args['uid']) . "'";
    $result = DBUtil::deleteWhere('group_membership', $where);

    // Check for an error with the database code
    if (!$result) {
        return LogUtil::registerError(__('Error! Could not create the new item.'));
    }

    // Let the calling process know that we have finished successfully
    return true;
}

/**
 * find who is online
 *
 * @param unknown_type $args
 * @return mixed array of users, or false
 */
function Groups_userapi_whosonline($args)
{
    $pntable = pnDBGetTables();
    $col = $pntable['session_info_column'];
    $activetime = time() - (pnConfigGetVar('secinactivemins') * 60);

    $where = "WHERE {$col['uid']} != 0 AND {$col['lastused']} > {$activetime} GROUP BY {$col['uid']}";
    $fa = DBUtil::selectFieldArray ('session_info', 'uid', $where, '', true);
    $items = array();
    foreach ($fa as $f) {
        $items[] = array('uid' => $f);
    }
    return $items;
}

/**
 * check if a user is a member of a group
 *
 * @author John Alarcon
 * @param int $args['uid'] user id
 * @param int $args['gid'] group id
 * @return bool true if member of a group, false otherwise
 */
function groups_userapi_isgroupmember($args)
{
    if (!isset($args['uid']) || !is_numeric($args['uid']) ||
        !isset($args['gid']) || !is_numeric($args['gid'])) {
        return LogUtil::registerArgsError();
    }

    // Security check
    if (!SecurityUtil::checkPermission('Groups::', '::', ACCESS_READ)) {
        return false;
    }

    // Get the group
    $group = pnModAPIFunc('Groups', 'user', 'get', array('gid' => $args['gid']));

    // check if group exists
    if (!$group) {
        // report failiure
        return false;
    }

    // check if the user exists in the group
    if (!isset($group['members'][$args['uid']])) {
        // report failiure
        return false;
    }

    // report the user is a member of the group
    return true;
}
