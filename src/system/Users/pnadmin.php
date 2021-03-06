<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: pnadmin.php 28205 2010-02-04 07:06:32Z aperezm $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @author Xiaoyu Huang
 * @package Zikula_System_Modules
 * @subpackage Users
 */

/**
 * users_admin_main()
 * Redirects users to the "view" page
 *
 * @return bool true if successful false otherwise
 */
function users_admin_main()
{
    if (!SecurityUtil::checkPermission('Users::', '::', ACCESS_ADMIN)){
        return LogUtil::registerPermissionError();
    }

    return pnRedirect(pnModURL('Users', 'admin', 'view'));
}

/**
 * form to add new item
 *
 * This is a standard function that is called whenever an administrator
 * wishes to create a new module item
 *
 * @author       The Zikula Development Team
 * @return       output       The main module admin page.
 */
function users_admin_new()
{
    // Security check - important to do this as early as possible to avoid
    // potential security holes or just too much wasted processing
    if (!SecurityUtil::checkPermission('Users::', '::', ACCESS_ADD)) {
        return LogUtil::registerPermissionError();
    }

    $userinfo = FormUtil::getPassedValue('userinfo');

    // Create output object - this object will store all of our output so that
    // we can return it easily when required
    $pnRender = & pnRender::getInstance('Users', false);

    // Assign the data and Users setting
    $pnRender->assign('userinfo', $userinfo);
    $pnRender->assign('modvars', pnModGetVar('Users'));

    // Return the output that has been generated by this function
    return $pnRender->fetch('users_admin_new.htm');
}

/**
 * Create user
 *
 * @param  $args
 * @return mixed true if successful, string otherwise
 */
function users_admin_create()
{
    // check permisisons
    if (!SecurityUtil::checkPermission('Users::', '::', ACCESS_ADD)) {
        return LogUtil::registerPermissionError();
    }

    // get arguments
    $userinfo = array();
    $userinfo['add_uname'] = FormUtil::getPassedValue('add_uname', null, 'POST');
    $userinfo['add_email'] = FormUtil::getPassedValue('add_email', null, 'POST');

    $pass  = FormUtil::getPassedValue('add_pass', null, 'POST');
    $vpass = FormUtil::getPassedValue('add_vpass', null, 'POST');
    $dynadata = FormUtil::getPassedValue('dynadata');

    $profileModule = pnConfigGetVar('profilemodule', '');
    $useProfileMod = (!empty($profileModule) && pnModAvailable($profileModule));

    // call the API
    $checkuser = pnModAPIFunc('Users', 'user', 'checkuser',
                              array('uname'        => $userinfo['add_uname'],
                                    'email'        => $userinfo['add_email'],
                                    'agreetoterms' => 1));

    // if errorcode != 1 then return error msgs
    $errormsg = array();
    if ($checkuser != 1) {
        switch($checkuser)
        {
            case -1:
                $errormsg[] = __('Sorry! You have not been granted access to this module.');
                break;
            case 2:
                $errormsg[] = __('Sorry! The e-mail address you entered was incorrectly formatted or is unacceptable for other reasons. Please correct your entry and try again.');
                break;
            case 3:
                $errormsg[] = __("Error! Please click on the checkbox to accept the site's 'Terms of use' and 'Privacy policy'.");
                break;
            case 4:
                $errormsg[] = __('Sorry! The user name you entered is not acceptable. Please correct your entry and try again.');
                break;
            case 5:
                $errormsg[] = __('Sorry! The user name you entered is too long. The maximum length is 25 characters.');
                break;
            case 6:
                $errormsg[] = __('Sorry! The user name you entered is reserved and cannot be registered. Please choose another name and try again.');
                break;
            case 7:
                $errormsg[] = __('Sorry! Your user name cannot contain spaces. Please correct your entry and try again.');
                break;
            case 8:
                $errormsg[] = __('Sorry! This user name has already been registered. Please choose another name and try again.');
                break;
            case 9:
                $errormsg[] = __('Sorry! This e-mail address has already been registered, and it cannot be used again for creating another account.');
                break;
            default:
                $errormsg[] = __('Sorry! You have not been granted access to this module.');
        } // switch

        return LogUtil::registerError($errormsg, null, pnModURL('Users', 'admin', 'new', array('userinfo' => $userinfo, 'dynadata' => $dynadata)));
    }

    if (!empty($dynadata) && $useProfileMod) {
        // Check for required fields - The API function is called.
        $checkrequired = pnModAPIFunc($profileModule, 'user', 'checkrequired',
                                      array('dynadata' => $dynadata));

        if ($checkrequired['result'] == true) {
            $errormsg[] = __f('Sorry! A required item is missing from your profile information (%s).', $checkrequired['translatedFieldsStr']);
        }
    }

    $minpass = pnModGetVar('Users', 'minpass');

    if (empty($pass)) {
        $errormsg[] = __('Sorry! You did not provide a password. Please correct your entry and try again.');

    } elseif ((isset($pass)) && ("$pass" != "$vpass")) {
        $errormsg[] = __('Sorry! You did not enter the same password in each password field. Please enter the same password once in each password field (this is required for verification).');

    } elseif (($pass != '') && (strlen($pass) < $minpass)) {
        $errormsg[] = _fn('Your password must be at least %s character long', 'Your password must be at least %s characters long', $minpass);
    }

    if (!empty($errormsg)) {
        return LogUtil::registerError($errormsg, null, pnModURL('Users', 'admin', 'new', array('userinfo' => $userinfo, 'dynadata' => $dynadata)));
    }

    $registered = pnModAPIFunc('Users', 'user', 'finishnewuser',
                               array('isadmin'       => 1,
                                     'uname'         => $userinfo['add_uname'],
                                     'pass'          => $pass,
                                     'email'         => $userinfo['add_email'],
                                     'moderated'     => false,
                                     'dynadata'      => $dynadata));

    if ($registered) {
        LogUtil::registerStatus(__('Done! Created user account.'));
    } else {
        LogUtil::registerError(__('Error! Could not create the new user account.'));
    }

    return pnRedirect(pnModURL('Users', 'admin', 'main'));
}

/**
 * Shows all items and lists the administration options.
 *
 * @author       The Zikula Development Team
 * @param        startnum     The number of the first item to show
 * @return       output       The main module admin page
 */
function users_admin_view()
{
    // Get parameters from whatever input we need.
    $startnum = FormUtil::getPassedValue('startnum', isset($args['startnum']) ? $args['startnum'] : null, 'GET');
    $letter = FormUtil::getPassedValue('letter', isset($args['letter']) ? $args['letter'] : null, 'GET');

    if (!SecurityUtil::checkPermission('Users::', '::', ACCESS_EDIT)) {
        return LogUtil::registerPermissionError();
    }

    // Create output object
    $pnRender = & pnRender::getInstance('Users', false);

    // we need this value multiple times, so we keep it
    $itemsperpage = pnModGetVar('Users', 'itemsperpage');

    // Get all users
    $items = pnModAPIFunc('Users', 'user', 'getall',
                          array('startnum' => $startnum,
                                'numitems' => $itemsperpage,
                                'letter' => $letter));

    $profileModule = pnConfigGetVar('profilemodule', '');
    $useProfileModule = (!empty($profileModule) && pnModAvailable($profileModule));

    // Loop through each returned item adding in the options that the user has over
    // each item based on the permissions the user has.
    foreach ($items as $key => $item)
    {
        $options = array();
        if (SecurityUtil::checkPermission('Users::', "$item[uname]::$item[uid]", ACCESS_READ) && $item['uid'] != 1) {
            // Options for the item.
            if ($useProfileModule) {
                $options[] = array('url'   => pnModURL($profileModule, 'user', 'view', array('uid' => $item['uid'])),
                                   'image' => 'personal.gif',
                                   'title' => __('View the profile'));
            }
            if (SecurityUtil::checkPermission('Users::', "$item[uname]::$item[uid]", ACCESS_EDIT)) {
                $options[] = array('url'   => pnModURL('Users', 'admin', 'modify', array('userid' => $item['uid'])),
                                   'image' => 'xedit.gif',
                                   'title' => __('Edit'));

                if (SecurityUtil::checkPermission('Users::', "$item[uname]::$item[uid]", ACCESS_DELETE)) {
                    $options[] = array('url'   => pnModURL('Users', 'admin', 'deleteusers', array('userid' => $item['uid'])),
                                       'image' => '14_layer_deletelayer.gif',
                                       'title' => __('Delete'));
                }
            }
        }
        // Add the calculated menu options to the item array
        $items[$key]['options'] = $options;
    }

    // Assign the items to the template
    $pnRender->assign('usersitems', $items);

    // assign the values for the smarty plugin to produce a pager in case of there
    // being many items to display.
    $pnRender->assign('pager', array('numitems'     => pnModAPIFunc('Users', 'user', 'countitems', array('letter' => $letter)),
                                     'itemsperpage' => $itemsperpage));

    // Return the output that has been generated by this function
    return $pnRender->fetch('users_admin_view.htm');
}

/**
 * Shows all the applications and the available options.
 *
 * @author       The Zikula Development Team
 * @param        startnum     The number of the first item to show
 * @return       output       The main module admin page
 */
function users_admin_viewapplications()
{
    // security check
    if (!SecurityUtil::checkPermission('Users::', '::', ACCESS_EDIT)) {
        return LogUtil::registerPermissionError();
    }

    // Get parameters from whatever input we need.
    $startnum = FormUtil::getPassedValue ('startnum');

    // Create output object
    $pnRender = & pnRender::getInstance('Users', false);

    // we need this value multiple times, so we keep it
    $itemsperpage = pnModGetVar('Users', 'itemsperpage');

    // The user API function is called.
    $items = pnModAPIFunc('Users', 'admin', 'getallpendings',
                          array('startnum' => $startnum,
                                'numitems' => $itemsperpage));

    $optItems = pnModGetVar('Users', 'reg_optitems');
    // Loop through each returned item adding in the options that the user has over
    // each item based on the permissions the user has.
    foreach ($items as $key => $item) {
        $options = array();
        $options['optitems'] = $optItems && SecurityUtil::checkPermission('Users::', "$item[uname]::$item[tid]", ACCESS_READ);
        $options['approve'] = SecurityUtil::checkPermission('Users::', "$item[uname]::$item[tid]", ACCESS_ADD);
        $options['deny'] = SecurityUtil::checkPermission('Users::', "$item[uname]::$item[tid]", ACCESS_DELETE);

        // Add the calculated menu options to the item array
        $items[$key]['options'] = $options;
    }

    // Assign the items to the template
    $pnRender->assign('usersitems', $items);

    // assign the values for the smarty plugin to produce a pager in case of there
    // being many items to display.
    $pnRender->assign('pager', array('numitems'     => pnModAPIFunc('Users', 'admin', 'countpending'),
                                     'itemsperpage' => $itemsperpage));

    // Return the output that has been generated by this function
    return $pnRender->fetch('users_admin_viewapplications.htm');
}

/**
 * Shows the information for the temporary user
 *
 * @param  $args
 * @return string HTML string
 */
function users_admin_viewtempuserinfo()
{
    // security check
    if (!SecurityUtil::checkPermission('Users::', '::', ACCESS_ADD)) {
        return LogUtil::registerPermissionError();
    }

    // Get parameters from whatever input we need.
    // (Note that the name of the passed parameter is 'userid' but that it
    // is actually a registration application id.)
    $regid = FormUtil::getPassedValue('userid', null, 'GET');

    if (empty($regid) || !is_numeric($regid)) {
        return LogUtil::registerArgsError();
    }

    $regApplication = pnModAPIFunc('Users', 'admin', 'getapplication', array('userid' => $regid));

    $regApplication = array_merge($regApplication, (array)@unserialize($regApplication['dynamics']));
    if (!$regApplication) {
        // getapplication could fail (return false) because of a nonexistant
        // record, no permission to read an existing record, or a database error
        return LogUtil::registerError(__('Unable to retrieve registration record. The record with the specified id might not exist, or you might not have permission to access that record.'));
    }

    // Create output object
    $pnRender = & pnRender::getInstance('Users', false);

    $pnRender->assign('domprofile', ZLanguage::getModuleDomain($profileModule));

    $pnRender->assign('uname',    $regApplication['uname']);
    $pnRender->assign('userid',   $regid);
    $pnRender->assign('fields',   $items);
    $pnRender->assign('userinfo', $regApplication);

    return $pnRender->fetch('users_admin_viewtempuserdetails.htm');
}

/**
 * Confirm approval of a pending new user application.
 *
 * @return string|bool Rendered output; or false on error.
 */
function users_admin_confirmApproval()
{
    $userid = FormUtil::getPassedValue('userid', null, 'GET');

    $item = pnModAPIFunc('Users', 'admin', 'getapplication', array('userid' => $userid));

    if (!$item) {
        return LogUtil::registerError(__('Sorry! Could not find any matching user account.'),
                                      null,
                                      pnModUrl('Users', 'admin', 'main'));
    }

    // security check
    if (!SecurityUtil::checkPermission('Users::', "{$item['uname']}::{$item['tid']}", ACCESS_ADD)) {
        return LogUtil::registerPermissionError();
    }

    // create the output object
    $pnRender = & pnRender::getInstance('Users', false);

    $pnRender->assign('userid', $userid);
    $pnRender->assign('item',   $item);

    return $pnRender->fetch('users_admin_confirmapproval.htm');
}

/**
 * Approve a pending new user application in response to a confirmApproval operation.
 *
 * @return bool True on success with redirect; otherwise false.
 */
function users_admin_approve()
{
    if (!SecurityUtil::confirmAuthKey('Users')) {
        return LogUtil::registerAuthidError(pnModURL('Users', 'admin'));
    }

    // API call will check security

    $userid = FormUtil::getPassedValue('userid');
    $confirmed = FormUtil::getPassedValue('confirmed');

    if (isset($confirmed) && $confirmed) {
        $return = pnModAPIFunc('Users', 'admin', 'approve', array('userid' => $userid));

        if ($return == true) {
            LogUtil::registerStatus(__('Done! Created the new user account.'));
        } else {
            LogUtil::registerError(__('Error! Could not create the new user account.'));
        }
        return pnRedirect(pnModUrl('Users', 'admin', 'main'));
    } else {
        // User hit the OK button (check mark) but did not check the confirmed box.
        return LogUtil::registerStatus(__('Cancelled! The confirmation check box was not checked, therefore the application was not approved.'),
                pnModUrl('Users', 'admin', 'viewapplications'));
    }
}

/**
 * Confirm denial of a pending new user application.
 *
 * @return string|bool Rendered output; or false on error.
 */
function users_admin_confirmDenial()
{
    $userid = FormUtil::getPassedValue('userid', null, 'GET');

    $item = pnModAPIFunc('Users', 'admin', 'getapplication', array('userid' => $userid));

    if (!$item) {
        return LogUtil::registerError(__('Sorry! Could not find any matching user account.'),
                                      null,
                                      pnModUrl('Users', 'admin', 'main'));
    }

    // security check
    if (!SecurityUtil::checkPermission('Users::', "{$item['uname']}::{$item['tid']}", ACCESS_DELETE)) {
        return LogUtil::registerPermissionError();
    }

    // create the output object
    $pnRender = & pnRender::getInstance('Users', false);

    $pnRender->assign('userid', $userid);
    $pnRender->assign('item',   $item);

    return $pnRender->fetch('users_admin_confirmdenial.htm');
}

/**
 * Deny a pending new user application in response to a confirmDenial operation.
 *
 * @return bool True on success with redirect; otherwise false.
 */
function users_admin_deny()
{
    if (!SecurityUtil::confirmAuthKey('Users')) {
        return LogUtil::registerAuthidError(pnModURL('Users', 'admin'));
    }

    // API call will check security

    $userid = FormUtil::getPassedValue('userid');
    $confirmed = FormUtil::getPassedValue('confirmed');

    if (isset($confirmed) && $confirmed) {
        $return = pnModAPIFunc('Users', 'admin', 'deny', array('userid' => $userid));

        if ($return == true) {
            LogUtil::registerStatus(__('Done! Deleted user account.'));
        } else {
            LogUtil::registerError(__('Error! Could not remove the pending application.'));
        }
        return pnRedirect(pnModUrl('Users', 'admin', 'main'));
    } else {
        // User hit the OK button (check mark) but did not check the confirmed box.
        return LogUtil::registerStatus(__('Cancelled! The confirmation check box was not checked, therefore the application was not denied.'),
                pnModUrl('Users', 'admin', 'viewapplications'));
    }
}

/**
 * user_admin_view()
 * Shows the search box for Edit/Delete
 * Shows Add User Dialog
 *
 * @param  $args
 * @return string HTML string
 */
function users_admin_search($args)
{
    // security check
    if (!SecurityUtil::checkPermission('Users::', '::', ACCESS_EDIT)){
        return LogUtil::registerPermissionError();
    }

    // create output object
    $pnRender = & pnRender::getInstance('Users', false);

    // get group items
    // TODO: move to a call to the groups module
    $groups = pnModAPIFunc('Users', 'admin', 'getusergroups', array());
    $pnRender->assign('groups', $groups);

    return $pnRender->fetch('users_admin_search.htm');
}

/**
 * users_admin_listusers()
 * list users
 *
 * @param  $args
 * @return string HTML string
 */
function users_admin_listusers($args)
{
    $uname         = FormUtil::getPassedValue('uname', null, 'POST');
    $ugroup        = FormUtil::getPassedValue('ugroup', null, 'POST');
    $email         = FormUtil::getPassedValue('email', null, 'POST');
    $regdateafter  = FormUtil::getPassedValue('regdateafter', null, 'POST');
    $regdatebefore = FormUtil::getPassedValue('regdatebefore', null, 'POST');

    $dynadata      = FormUtil::getPassedValue('dynadata', null, 'POST');

    // call the api
    $items = pnModAPIFunc('Users', 'admin', 'findusers',
                          array('uname'         => $uname,
                                'email'         => $email,
                                'ugroup'        => $ugroup,
                                'regdateafter'  => $regdateafter,
                                'regdatebefore' => $regdatebefore,
                                'dynadata'      => $dynadata));

    if (!$items) {
        LogUtil::registerError(__('Sorry! No matching users found.'), 404, pnModURL('Users', 'admin', 'search'));
    }

    // create output object
    $pnRender = & pnRender::getInstance('Users', false);

    $pnRender->assign('mailusers', SecurityUtil::checkPermission('Users::MailUsers', '::', ACCESS_COMMENT));
    $pnRender->assign('deleteusers', SecurityUtil::checkPermission('Users::', '::', ACCESS_ADMIN));

    // assign the matching results
    $pnRender->assign('items', $items);

    return $pnRender->fetch('users_admin_listusers.htm');
}

/**
 * Direct an operation submitted from listusers to the appropriate processing function.
 *
 * @return mixed true successful, false or string otherwise
 */
function users_admin_processusers()
{
    // security check
    if (!SecurityUtil::checkPermission('Users::', '::', ACCESS_EDIT)){
        return LogUtil::registerPermissionError();
    }

    // get the arguments from our input
    $op     = FormUtil::getPassedValue('op', null, 'GETPOST');
    $userid = FormUtil::getPassedValue('userid', null, 'POST');

    if ($op == 'delete' && !empty($userid)) {
        // Handle multi-user delete after a search.
        return pnModFunc('Users', 'admin', 'remove');

    } elseif ($op == 'mail' && !empty($userid)) {
        // Handle multi-user delete after a search.
        return pnModFunc('Users', 'admin', 'sendmail');

    } elseif (empty($userid)) {
        return LogUtil::registerError(__('Error! No users were selected.'), null, pnModURL('Users', 'admin', 'search'));
    } else {
        return LogUtil::registerError(__('Error! Unrecognized operation specified.'), null, pnModURL('Users', 'admin', 'main'));
    }
}

/**
 * Send an e-mail message to one or more users selected on the listusers page.
 *
 * @return bool True on succes with redirect; otherwise false;
 */
function users_admin_sendmail()
{
    if (!SecurityUtil::confirmAuthKey('Users')) {
        return LogUtil::registerAuthidError(pnModURL('Users', 'admin'));
    }

    $uid = FormUtil::getPassedValue('userid', null, 'POST');
    $sendmail = FormUtil::getPassedValue('sendmail', null, 'POST');

    $mailSent = pnModAPIFunc('Users', 'admin', 'sendmail', array(
        'uid'       => $uid,
        'sendmail'  => $sendmail,
    ));

    if (!$mailSent) {
        return pnRedirect(pnModURL('Users', 'admin', 'search'));
    } else {
        return pnRedirect(pnModURL('Users', 'admin', 'main'));
    }
}

/**
 * users_admin_modify()
 *
 * @param  $args
 * @return string HTML string
 */
function users_admin_modify($args)
{
    // security check
    if (!SecurityUtil::checkPermission('Users::', '::', ACCESS_EDIT)){
        return LogUtil::registerPermissionError();
    }

    // get our input
    $userid = FormUtil::getPassedValue('userid', (isset($args['userid']) ? $args['userid'] : null), 'GET');
    $uname  = FormUtil::getPassedValue('uname', (isset($args['uname']) ? $args['uname'] : null), 'GET');

    if (is_null($userid) && !is_null($uname) && !empty($uname)) {
        $userid = pnUserGetIDFromName($uname);
    }

    // get the user vars
    $uservars = pnUserGetVars($userid);
    if ($uservars == false) {
        return LogUtil::registerError(__('Sorry! No items found.'), 404);
    }

    $profileModule = pnConfigGetVar('profilemodule', '');
    $useProfile = (!empty($profileModule) && pnModAvailable($profileModule));

    // create the output oject
    $pnRender = & pnRender::getInstance('Users', false);

    // urls
    $pnRender->assign('userid', $userid);
    $pnRender->assign('userinfo', $uservars);

    // groups
    $permissions_array = array();
    $access_types_array = array();
    $usergroups = pnModAPIFunc('Groups', 'user', 'getusergroups', array('uid' => $userid));
    foreach ($usergroups as $usergroup) {
        $permissions_array[] = (int)$usergroup['gid'];
    }
    $allgroups = pnModAPIFunc('Groups', 'user', 'getall');
    foreach ($allgroups as $group) {
        $access_types_array[$group['gid']] = $group['name'];
    }

    $pnRender->assign('permissions_array', $permissions_array);
    $pnRender->assign('access_types_array', $access_types_array);

    return $pnRender->fetch('users_admin_modify.htm');
}

/**
 * Update a user record after a submit from a modify action.
 *
 * @return bool True and redirected if successful; otherwise false.
 */
function users_admin_update()
{
    if (!SecurityUtil::confirmAuthKey('Users')) {
        return LogUtil::registerAuthidError(pnModURL('Users', 'admin'));
    }

    // API call will check security

    $uid = FormUtil::getPassedValue('userid', null, 'POST');

    $uname              = FormUtil::getPassedValue('uname', null, 'POST');
    $email              = FormUtil::getPassedValue('email', null, 'POST');
    $activated          = FormUtil::getPassedValue('activated', null, 'POST');
    $pass               = FormUtil::getPassedValue('pass', null, 'POST');
    $vpass              = FormUtil::getPassedValue('vpass', null, 'POST');
    $theme              = FormUtil::getPassedValue('theme', null, 'POST');
    $access_permissions = FormUtil::getPassedValue('access_permissions', null, 'POST');
    $dynadata           = FormUtil::getPassedValue('dynadata', null, 'POST');

    $return = pnModAPIFunc('Users', 'admin', 'saveuser',
                           array('uid'                => $uid,
                                 'uname'              => $uname,
                                 'email'              => $email,
                                 'activated'          => $activated,
                                 'pass'               => $pass,
                                 'vpass'              => $vpass,
                                 'theme'              => $theme,
                                 'dynadata'           => $dynadata,
                                 'access_permissions' => $access_permissions));

    if ($return == true) {
        LogUtil::registerStatus(__("Done! Saved user's account information."));
        return pnRedirect(pnModUrl('Users', 'admin', 'main'));
    } else {
        return false;
    }
}

/**
 * users_admin_deleteusers()
 *
 * @param $args
 * @return string HTML string
 **/
function users_admin_deleteusers($args)
{
    // check permissions
    if (!SecurityUtil::checkPermission('Users::', '::', ACCESS_DELETE)){
        return LogUtil::registerPermissionError();
    }

    // get arguments
    $op     = FormUtil::getPassedValue('op', null, 'GET');
    $userid = isset($args['userid']) ? $args['userid'] : FormUtil::getPassedValue('userid', null, 'GET');
    $uname  = isset($args['uname'])  ? $args['uname']  : FormUtil::getPassedValue('uname', null, 'GET');

    // buld the users array
    $users = array();

    if (is_null($userid) && !is_null($uname) && !empty($uname)) {
        $userid = pnUserGetIDFromName($uname);
        $users[$userid] = $uname;

    } elseif (is_null($uname)) {
        if (is_array($userid)) {
            foreach ($userid as $uid) {
                if ($name = pnUserGetVar('uname', $uid)) {
                    $users[$uid] = $name;
                }
            }

        } elseif ($uname = pnUserGetVar('uname', $userid)) {
            $users[$userid] = $uname;
        }
    }

    if ($userid == 1 || empty($users)) {
        return LogUtil::registerArgsError();
    }

    // create the output object
    $pnRender = & pnRender::getInstance('Users', false);

    $pnRender->assign('users', $users);

    // return output
    return $pnRender->fetch('users_admin_deleteusers.htm');
}

/**
 * Remove one or more users in response to a confirmed deleteusers operation.
 *
 * @return bool True and redirected if successful; otherwise false.
 */
function users_admin_remove($args)
{
    if (!SecurityUtil::confirmAuthKey('Users')) {
        return LogUtil::registerAuthidError(pnModURL('Users', 'admin'));
    }

    // API call will check security

    $uid = FormUtil::getPassedValue('userid', null, 'POST');

    if (!isset($uid) || empty($uid)) {
        return LogUtil::registerError(__('Error! No users selected for removal.'));
    }
    
    $usersDeleted = pnModAPIFunc('Users', 'admin', 'deleteuser', array('uid' => $uid));

    if (is_array($uid)) {
        $count = count($uid);
    } else {
        $count = 1;
    }
    if ($usersDeleted) {
        return LogUtil::registerStatus(_fn('Done! Deleted user account.', 'Done! Deleted %s user accounts.', $count, array($count)), pnModUrl('Users', 'admin', 'main'));
    } else {
        return false;
    }
}

/**
 * users_admin_modifyconfig()
 *
 * User configuration settings
 * @author Xiaoyu Huang
 * @see function settings_admin_main()
 * @return string HTML string
 **/
function users_admin_modifyconfig()
{
    // Security check
    if (!(SecurityUtil::checkPermission('Users::', '::', ACCESS_ADMIN))) {
        return LogUtil::registerPermissionError();
    }

    // Create output object
    $pnRender = & pnRender::getInstance('Users', false);

    // assign the module vars
    $pnRender->assign('config', pnModGetVar('Users'));

    // Return the output that has been generated by this function
    return $pnRender->fetch('users_admin_modifyconfig.htm');
}

/**
 * users_admin_updateconfing()
 *
 * Update user configuration settings
 * @author Xiaoyu Huang
 * @see function settings_admin_main()
 * @return string HTML string
 **/
function users_admin_updateconfig()
{
    // security check
    if (!SecurityUtil::checkPermission('Users::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // get our input
    $config = FormUtil::getPassedValue('config', '', 'POST');

    if (!isset($config['reg_noregreasons'])) {
        $config['reg_noregreasons'] = '';
    }

    pnModSetVar('Users', 'itemsperpage', $config['itemsperpage']);
    pnModSetVar('Users', 'accountdisplaygraphics', $config['accountdisplaygraphics']);
    pnModSetVar('Users', 'accountitemsperpage', $config['accountitemsperpage']);
    pnModSetVar('Users', 'accountitemsperrow', $config['accountitemsperrow']);
    pnModSetVar('Users', 'changepassword', $config['changepassword']);
    pnModSetVar('Users', 'changeemail', $config['changeemail']);
    pnModSetVar('Users', 'userimg', $config['userimg']);
    pnModSetVar('Users', 'reg_uniemail', $config['reg_uniemail']);
    pnModSetVar('Users', 'reg_optitems', $config['reg_optitems']);
    pnModSetVar('Users', 'reg_allowreg', $config['reg_allowreg']);
    pnModSetVar('Users', 'reg_noregreasons', $config['reg_noregreasons']);
    pnModSetVar('Users', 'moderation', $config['moderation']);
    pnModSetVar('Users', 'reg_verifyemail', $config['reg_verifyemail']);
    pnModSetVar('Users', 'reg_notifyemail', $config['reg_notifyemail']);
    pnModSetVar('Users', 'reg_Illegaldomains', $config['reg_Illegaldomains']);
    pnModSetVar('Users', 'reg_Illegalusername', $config['reg_Illegalusername']);
    pnModSetVar('Users', 'reg_Illegaluseragents', $config['reg_Illegaluseragents']);
    pnModSetVar('Users', 'minage', $config['minage']);
    pnModSetVar('Users', 'minpass', $config['minpass']);
    pnModSetVar('Users', 'anonymous', $config['anonymous']);
    pnModSetVar('Users', 'savelastlogindate', $config['savelastlogindate']);
    pnModSetVar('Users', 'loginviaoption', $config['loginviaoption']);
    pnModSetVar('Users', 'hash_method', $config['hash_method']);
    pnModSetVar('Users', 'login_redirect', $config['login_redirect']);
    pnModSetVar('Users', 'reg_question', $config['reg_question']);
    pnModSetVar('Users', 'reg_answer', $config['reg_answer']);
    pnModSetVar('Users', 'idnnames', $config['idnnames']);
    pnModSetVar('Users', 'avatarpath', $config['avatarpath']);
    pnModSetVar('Users', 'lowercaseuname', $config['lowercaseuname']);

    // Let any other modules know that the modules configuration has been updated
    pnModCallHooks('module', 'updateconfig', 'Users', array('module' => 'Users'));

    // the module configuration has been updated successfuly
    LogUtil::registerStatus(__('Done! Saved module configuration.'));

    return pnRedirect(pnModURL('Users', 'admin', 'modifyconfig'));
}
