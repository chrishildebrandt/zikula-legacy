<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2001, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: pnajax.php 27396 2009-11-04 01:38:04Z mateo $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula_System_Modules
 * @subpackage Permissions
*/

/**
 * Updates a permission rule in the database
 *
 * @author Frank Schummertz
 * @param pid the permission id
 * @param gid the group id
 * @param seq the sequence
 * @param component the permission component
 * @param instance the permission instance
 * @param level the permission level
 * @return mixed updated permission as array or Ajax error
 */
function permissions_ajax_updatepermission()
{
    if (!SecurityUtil::checkPermission('Permissions::', '::', ACCESS_ADMIN)) {
        AjaxUtil::error(__('Sorry! You have not been granted access to this page.'));
    }

    if (!SecurityUtil::confirmAuthKey()) {
        AjaxUtil::error(FormUtil::getPassedValue('authid') . ' : ' . __("Sorry! Invalid authorisation key ('authkey'). This is probably either because you pressed the 'Back' button to return to a page which does not allow that, or else because the page's authorisation key expired due to prolonged inactivity. Please refresh the page and try again."));
    }

    $pid       = FormUtil::getPassedValue('pid', null, 'post');
    $gid       = FormUtil::getPassedValue('gid', null, 'post');
    $seq       = FormUtil::getPassedValue('seq', 9999, 'post');
    $component = DataUtil::convertFromUTF8(FormUtil::getPassedValue('comp', '.*', 'post'));
    $instance  = DataUtil::convertFromUTF8(FormUtil::getPassedValue('inst', '.*', 'post'));
    $level     = FormUtil::getPassedValue('level', 0, 'post');

    if (preg_match("/[\n\r\t\x0B]/", $component)) {
        $component = trim(preg_replace("/[\n\r\t\x0B]/", "", $component));
        $instance = trim(preg_replace("/[\n\r\t\x0B]/", "", $instance));
    }
    if (preg_match("/[\n\r\t\x0B]/", $instance)) {
        $component = trim(preg_replace("/[\n\r\t\x0B]/", "", $component));
        $instance = trim(preg_replace("/[\n\r\t\x0B]/", "", $instance));
    }

    // Pass to API

    pnModAPIFunc('Permissions', 'admin', 'update',
                 array('pid'       => $pid,
                       'seq'       => $seq,
                       'oldseq'    => $seq,
                       'realm'     => 0,
                       'id'        => $gid,
                       'component' => $component,
                       'instance'  => $instance,
                       'level'     => $level));

    // read current settings and return them
    $perm = DBUtil::selectObjectByID('group_perms', $pid, 'pid');
    $accesslevels = accesslevelnames();
    $perm['levelname'] = $accesslevels[$perm['level']];
    switch($perm['gid']) {
        case -1:
            $perm['groupname'] = __('All groups');
            break;
        case 0:
            $perm['groupname'] = __('Unregistered');
            break;
        default:
            $group = DBUtil::selectObjectByID('groups', $perm['gid'], 'gid');
            $perm['groupname'] = $group['name'];
    }

    return $perm;
}

/**
 *
 *
 * @author Frank Schummertz
 * @param permorder array of sorted permissions (value = permission id)
 * @return mixed true or Ajax error
 */
function permissions_ajax_changeorder()
{
    if (!SecurityUtil::checkPermission('Permissions::', '::', ACCESS_ADMIN)) {
        AjaxUtil::error(__('Sorry! You have not been granted access to this page.'));
    }

    if (!SecurityUtil::confirmAuthKey()) {
        AjaxUtil::error(__("Sorry! Invalid authorisation key ('authkey'). This is probably either because you pressed the 'Back' button to return to a page which does not allow that, or else because the page's authorisation key expired due to prolonged inactivity. Please refresh the page and try again."));
    }

    $permorder = FormUtil::getPassedValue('permorder');

    $pntable = pnDBGetTables();
    $permcolumn = $pntable['group_perms_column'];

    for($cnt=0; $cnt<count($permorder); $cnt++) {
        $where = "WHERE $permcolumn[pid] = '" . (int)DataUtil::formatForStore($permorder[$cnt]) . "'";
        $obj = array('sequence' => $cnt);
        DBUtil::updateObject($obj, 'group_perms', $where, 'pid');
    }
    return array('result' => true);
}

/**
 * Create a blank permission and return it
 *
 * @author Frank Schummertz
 * @param none
 * @return mixed array with new permission or Ajax error
 */
function permissions_ajax_createpermission()
{
    if (!SecurityUtil::checkPermission('Permissions::', '::', ACCESS_ADMIN)) {
        AjaxUtil::error(__('Sorry! You have not been granted access to this page.'));
    }

    if (!SecurityUtil::confirmAuthKey()) {
        AjaxUtil::error(__("Sorry! Invalid authorisation key ('authkey'). This is probably either because you pressed the 'Back' button to return to a page which does not allow that, or else because the page's authorisation key expired due to prolonged inactivity. Please refresh the page and try again."));
    }

    // add a blank permission
    $dummyperm = array('realm'     => 0,
                       'id'        => 0,
                       'component' => '.*',
                       'instance'  => '.*',
                       'level'     => ACCESS_NONE,
                       'insseq'    => -1);

    $newperm = pnModAPIFunc('Permissions', 'admin', 'create', $dummyperm);
    if ($newperm == false) {
        AjaxUtil::error(__('Error! Could not create new permission rule.'));
    }

    $accesslevels = accesslevelnames();

    $newperm['instance']  = DataUtil::formatForDisplay($newperm['instance']);
    $newperm['component'] = DataUtil::formatForDisplay($newperm['component']);
    $newperm['levelname'] = $accesslevels[$newperm['level']];
    $newperm['groupname'] = __('Unregistered');

    return $newperm;
}

/**
 * Delete a permission
 *
 * @author Frank Schummertz
 * @param pid the permission id
 * @return mixed the id of the permission that has been deleted or Ajax error
 */
function permissions_ajax_deletepermission()
{
    if (!SecurityUtil::checkPermission('Permissions::', '::', ACCESS_ADMIN)) {
        AjaxUtil::error(__('Sorry! You have not been granted access to this page.'));
    }

    if (!SecurityUtil::confirmAuthKey()) {
        AjaxUtil::error(__("Sorry! Invalid authorisation key ('authkey'). This is probably either because you pressed the 'Back' button to return to a page which does not allow that, or else because the page's authorisation key expired due to prolonged inactivity. Please refresh the page and try again."));
    }

    $pid = FormUtil::getPassedValue('pid', null, 'get');

    // check if this is the overall admin permssion and return if this shall be deleted
    $perm = DBUtil::selectObjectByID('group_perms', $pid, 'pid');
    if ($perm['pid'] == 1 && $perm['level'] == ACCESS_ADMIN && $perm['component'] == '.*' && $perm['instance'] == '.*') {
        AjaxUtil::error(__('Notice: You cannot delete the main administration permission rule.'));
    }

    if (pnModAPIFunc('Permissions', 'admin', 'delete', array('pid' => $pid)) == true) {
        if ($pid == pnModGetVar('Permissions', 'adminid')) {
            pnModSetVar('Permissions', 'adminid', 0);
            pnModSetVar('Permissions', 'lockadmin', false);
        }
        return array('pid' => $pid);
    }

    AjaxUtil::error(__f('Error! Could not delete permission rule with ID %s.', $pid));
}

/**
 * Test a permission rule for a given username
 *
 * @author Frank Schummertz
 * @param test_user the username
 * @param test_component the component
 * @param test_instance the instance
 * @param test_level the accesslevel
 * @return string with test result for display
 */
function permissions_ajax_testpermission()
{
    if (!SecurityUtil::checkPermission('Permissions::', '::', ACCESS_ADMIN)) {
        AjaxUtil::error(__('Sorry! You have not been granted access to this page.'));
    }

    $uname = DataUtil::convertFromUTF8(FormUtil::getPassedValue('test_user', '', 'get'));
    $comp  = DataUtil::convertFromUTF8(FormUtil::getPassedValue('test_component', '.*', 'get'));
    $inst  = DataUtil::convertFromUTF8(FormUtil::getPassedValue('test_instance', '.*', 'get'));
    $level = DataUtil::convertFromUTF8(FormUtil::getPassedValue('test_level', ACCESS_READ, 'get'));

    $result = __('Permission check result:').': ';
    $uid = pnUserGetIDFromName($uname);

    if ($uid==false) {
        $result .= __('unknown user.');
    } else {
        if (SecurityUtil::checkPermission($comp, $inst, $level, $uid)) {
            $result .= __('permission granted.');
        } else {
            $result .= __('permission not granted.');
        }
    }

    return array('testresult' => $result);
}
