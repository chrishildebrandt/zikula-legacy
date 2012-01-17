<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2001, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: pnajax.php 27363 2009-11-02 16:40:08Z mateo $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @author Frank Schummertz, Frank Chestnut
 * @package Zikula_System_Modules
 * @subpackage Users
 */

/**
 * getusers
 * performs a user search based on the fragment entered so far
 *
 * @author Frank Schummertz
 * @param fragment string the fragment of the username entered
 * @return void nothing, direct ouptut using echo!
 */
function Users_ajax_getusers()
{
    if (!SecurityUtil::checkPermission('Users::', '::', ACCESS_ADMIN)) {
        return true;
    }

    $fragment = FormUtil::getpassedValue('fragment');

    pnModDBInfoLoad('Users');
    $pntable = pnDBGetTables();

    $userscolumn = $pntable['users_column'];

    $where = 'WHERE ' . $userscolumn['uname'] . ' REGEXP \'(' . DataUtil::formatForStore($fragment) . ')\'';
    $results = DBUtil::selectObjectArray('users', $where);

    $out = '<ul>';
    if (is_array($results) && count($results) > 0) {
        foreach($results as $result) {
            $out .= '<li>' . DataUtil::formatForDisplay($result['uname']) .'<input type="hidden" id="' . DataUtil::formatForDisplay($result['uname']) . '" value="' . $result['uid'] . '" /></li>';
        }
    }
    $out .= '</ul>';
    echo DataUtil::convertToUTF8($out);
    return true;
}

/**
 * Check new user information
 *
 * Check whether the user can be validated
 *
 * @author Frank Chestnut
 * @param username
 * @param email
 * @param dynadata - Coming soon
 * @return mixed true or Ajax error
 * errorcodes -1=NoPermission 1=EverythingOK 2=NotaValidatedEmailAddr
 *            3=NotAgreeToTerms 4=InValidatedUserName 5=UserNameTooLong
 *            6=UserNameReserved 7=UserNameIncludeSpace 8=UserNameTaken
 *            9=EmailTaken 10=emails different 11=User Agent Banned
 *            12=Email Domain banned 13=DUD incorrect 14=spam question incorrect
 *            15=Pass too short 16=Pass different 17=No pass
 **/
function Users_ajax_checkuser()
{
    if (!SecurityUtil::confirmAuthKey()) {
        AjaxUtil::error(FormUtil::getPassedValue('authid') . ' : ' . __("Sorry! Invalid authorisation key ('authkey'). This is probably either because you pressed the 'Back' button to return to a page which does not allow that, or else because the page's authorisation key expired due to prolonged inactivity. Please refresh the page and try again."));
    }

    $modvars = pnModGetVar('Users');

    if (!$modvars['reg_allowreg']) {
        AjaxUtil::error(__('Sorry! New user registration is currently disabled.'));
    }

    $uname        = DataUtil::convertFromUTF8(FormUtil::getPassedValue('uname',  null,     'POST'));
    $email        = DataUtil::convertFromUTF8(FormUtil::getPassedValue('email',  null,     'POST'));
    $vemail       = DataUtil::convertFromUTF8(FormUtil::getPassedValue('vemail', null,     'POST'));
    $agreetoterms = DataUtil::convertFromUTF8(FormUtil::getPassedValue('agreetoterms', 0,  'POST'));
    $dynadata     = DataUtil::convertFromUTF8(FormUtil::getPassedValue('dynadata', null,   'POST'));
    $pass         = DataUtil::convertFromUTF8(FormUtil::getPassedValue('pass', null,       'POST'));
    $vpass        = DataUtil::convertFromUTF8(FormUtil::getPassedValue('vpass', null,      'POST'));
    $reg_answer   = DataUtil::convertFromUTF8(FormUtil::getPassedValue('reg_answer', null, 'POST'));

    if ((!$uname) || !(!preg_match("/[[:space:]]/", $uname)) || !pnVarValidate($uname, 'uname')) {
        return array('result' => __('Sorry! The user name you entered is not acceptable. Please correct your entry and try again.'), 'errorcode' => 4);
    }

    if (strlen($uname) > 25) {
        return array('result' => __('Sorry! The user name you entered is too long. The maximum length is 25 characters.'), 'errorcode' => 5);
    }

    $reg_illegalusername = $modvars['reg_Illegalusername'];
    if (!empty($reg_illegalusername)) {
        $usernames = explode(' ', $reg_illegalusername);
        $count = count($usernames);
        $pregcondition = '/((';
        for ($i = 0;$i < $count;$i++) {
            if ($i != $count-1) {
                $pregcondition .= $usernames[$i] . ')|(';
            } else {
                $pregcondition .= $usernames[$i] . '))/iAD';
            }
        }
        if (preg_match($pregcondition, $uname)) {
            return array('result' => __('Sorry! The user name you entered is a reserved name.'), 'errorcode' => 6);
        }
    }

    if (strrpos($uname, ' ') > 0) {
        return array('result' => __('Sorry! A user name cannot contain any space characters.'), 'errorcode' => 7);
    }

    // check existing user
    $ucount = DBUtil::selectObjectCountByID ('users', $uname, 'uname', 'lower');
    if ($ucount) {
        return array('result' => __('Sorry! The user name you entered has already been registered.'), 'errorcode' => 8);
    }

    // check pending user
    $ucount = DBUtil::selectObjectCountByID ('users_temp', $uname, 'uname', 'lower');
    if ($ucount) {
        return array('result' => __('Sorry! The user name you entered has already been registered.'), 'errorcode' => 8);
    }

    if (!pnVarValidate($email, 'email')) {
        return array('result' => __('Sorry! The e-mail address you entered was incorrectly formatted or is unacceptable for other reasons. Please correct your entry and try again.'), 'errorcode' => 2);
    }

    if ($modvars['reg_uniemail']) {
        $ucount = DBUtil::selectObjectCountByID ('users', $email, 'email');
        if ($ucount) {
            return array('result' => __('Sorry! The e-mail address you entered has already been registered.'), 'errorcode' => 9);
        }
    }

    if ($modvars['moderation']) {
        $ucount = DBUtil::selectObjectCountByID ('users_temp', $uname, 'uname');
        if ($ucount) {
            return array('result' => __('Sorry! The user name you entered has already been registered.'), 'errorcode' => 8);
        }

        $ucount = DBUtil::selectObjectCountByID ('users_temp', $email, 'email');
        if (pnModGetVar('Users', 'reg_uniemail')) {
            if ($ucount) {
                return array('result' => __('Sorry! The e-mail address you entered has already been registered.'), 'errorcode' => 9);
            }
        }
    }

    if ($email !== $vemail) {
        return array('result' => __('Sorry! You did not enter the same e-mail address in each box. Please correct your entry and try again.'), 'errorcode' => 10);
    }

    if (!$modvars['reg_verifyemail'] || $modvars['reg_verifyemail'] == 2) {
        if ((isset($pass)) && ("$pass" != "$vpass")) {
            return array('result' => __('Error! You did not enter the same password in each password field. Please enter the same password once in each password field (this is required for verification).'), 'errorcode' => 16);
        } elseif (isset($pass) && (strlen($pass) < $modvars['minpass'])) {
            return array('result' => _fn('Your password must be at least %s character long', 'Your password must be at least %s characters long', $minpass), 'errorcode' => 15);
        } elseif (empty($pass) && !$modvars['reg_verifyemail']) {
            return array('result' => __('Error! Please enter a password.'), 'errorcode' => 17);
        }
    }

    if (pnModAvailable('legal')) {
        $tou_active = pnModGetVar('legal', 'termsofuse', true);
        $pp_active  = pnModGetVar('legal', 'privacypolicy', true);
        if ($tou_active == true && $pp_active == true && $agreetoterms == 0) {
            return array('result' => __('Error! Please click on the checkbox to accept the site\'s \'Terms of use\' and \'Privacy policy\'.'), 'errorcode' => 3);
        }
        if ($tou_active == true && $pp_active == false && $agreetoterms == 0) {
            return array('result' => __('Please click on the checkbox to accept the site\'s \'Terms of use\'.'), 'errorcode' => 3);
        }
        if ($tou_active == false && $pp_active == true && $agreetoterms == 0) {
            return array('result' => __('Please click on the checkbox to accept the site\'s \'Privacy policy\'.'), 'errorcode' => 3);
        }
    }

    $useragent = strtolower(pnServerGetVar('HTTP_USER_AGENT'));
    $illegaluseragents = $modvars['reg_Illegaluseragents'];
    if (!empty($illegaluseragents)) {
        $disallowed_useragents = str_replace(', ', ',', $illegaluseragents);
        $checkdisallowed_useragents = explode(',', $disallowed_useragents);
        $count = count($checkdisallowed_useragents);
        $pregcondition = '/((';
        for ($i = 0;$i < $count;$i++) {
            if ($i != $count-1) {
                $pregcondition .= $checkdisallowed_useragents[$i] . ')|(';
            } else {
                $pregcondition .= $checkdisallowed_useragents[$i] . '))/iAD';
            }
        }
        if (preg_match($pregcondition, $useragent)) {
            return array('result' => __('Sorry! The user agent specified is banned.'), 'errorcode' => 11);
        }
    }

    $illegaldomains = $modvars['reg_Illegaldomains'];
    if (!empty($illegaldomains)) {
        list($foo, $maildomain) = explode('@', $email);
        $maildomain = strtolower($maildomain);
        $disallowed_domains = str_replace(', ', ',', $illegaldomains);
        $checkdisallowed_domains = explode(',', $disallowed_domains);
        if (in_array($maildomain, $checkdisallowed_domains)) {
            return array('result' => __('Sorry! E-mail addresses from the domain you entered are not accepted for registering an account on this site.'), 'errorcode' => 12);
        }
    }

    if (!empty($dynadata) && is_array($dynadata)) {
        $required = Users_ajax_checkrequired($dynadata);
        if (is_array($required) && !empty($required)) {
            return $required;
        }
    }

    if ($modvars['reg_question'] != '' && $modvars['reg_answer'] != '') {
        if ($reg_answer != $modvars['reg_answer']) {
            return array('result' => __('Sorry! You gave the wrong answer to the anti-spam registration question. Please correct your entry and try again.'), 'errorcode' => 14);
        }
    }

    return array('result' => __("Your entries seem to be OK. Please click on 'Submit registration' when you are ready to continue."), 'errorcode' => 1);
}

/**
 * Check required dynamic data
 *
 * @access private
 * @author Frank Chestnut
 * @param dynadata - array of user input
 * @return false or mixed array (errorno and fields)
 **/
function Users_ajax_checkrequired($dynadata = array())
{
    if (empty($dynadata)) {
        return false;
    }

    $profileModule = pnConfigGetVar('profilemodule', '');
    if (empty($profileModule) || !pnModAvailable($profileModule)) {
        return false;
    }

    // Delegate check to the right module
    $result = pnModAPIFunc($profileModule, 'user', 'checkrequired');

    // False: no errors
    if ($result === false) {
        return $result;
    }

    return array('result' => __f('Error! One or more required fields were left blank or incomplete (%s).', $result['translatedFieldsStr']),
                 'errorcode' => 25,
                 'fields' => $result['fields']);
}
