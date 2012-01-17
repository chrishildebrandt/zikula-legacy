<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2004, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: function.userlogin.php 27906 2009-12-16 16:50:05Z aperezm $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula_Template_Plugins
 * @subpackage Functions
 */

/**
 * Smarty function to display the login box
 *
 * Example
 * <!--[userlogin size=14 maxlength=25 maxlengthpass=20]-->
 *
 * @author       Mark West
 * @since        23/10/03
 * @see          function.userlogin.php::smarty_function_userlogin()
 * @param        array       $params         All attributes passed to this function from the template
 * @param        object      &$smarty        Reference to the Smarty object
 * @param        integer     $size           Size of text boxes (default=14)
 * @param        integer     $maxlength      Maximum length of text box for unamees (default=25)
 * @param        integer     $maxlengthpass  Maximum length of text box for password (default=20)
 * @param        string      $class          Name of class  assigned to the login form
 * @param        string      $value          The default value of the username input box
 * @param        bool        $js             Use javascript to automatically clear the default value (defaults to true)
 * @return       string      the welcome message
 */
function smarty_function_userlogin($params, &$smarty)
{
    if (!pnUserLoggedIn()) {
        // set some defaults
        $size          = isset($params['size'])          ? $params['size']         : 14;
        $maxlength     = isset($params['maxlength'])     ? $params['maxlength']    : 25;
        $maxlengthpass = isset($params['maxlenthpass'])  ? $params['maxlenthpass'] : 20;
        $class         = isset($params['class'])         ? ' class="'.$params['class'].'"' : '';
        if(pnModGetVar('Users','loginviaoption') == 0) {
            $value = isset($params['value']) ? DataUtil::formatForDisplay($params['value']) : __('User name');
            $userNameLabel = __('User name');
            $inputName = 'uname';
        } else {
            $value = '';
            $userNameLabel = __('E-mail address');
            $inputName = 'email';
        }
        if (!isset($params['js']) || $params['js']) {
            $js = ' onblur="if (this.value==\'\')this.value=\''.$value.'\';" onfocus="if (this.value==\''.$value.'\')this.value=\'\';"';
        } else {
            $js = '';
        }

        // determine the current url so we can return the user to the correct place after login
        $returnurl = pnGetCurrentURI();
	
        // b.plagge 20070821 - authkey is required
        $authkey = SecurityUtil::generateAuthKey('Users');

        $loginbox = '<form'.$class.' style="display:inline" action="'.DataUtil::formatForDisplay(pnModURL('Users', 'user', 'login')).'" method="post"><div>'."\n"
                   .'<input type="hidden" name="authid" value="' . DataUtil::formatForDisplay($authkey) .'" />'."\n"
                   .'<label for="userlogin_plugin_uname">' . $userNameLabel . '</label>&nbsp;'."\n"
                   .'<input type="text" name="' . $inputName . '" id="userlogin_plugin_uname" size="'.$size.'" maxlength="'.$maxlength.'" value="'.$value.'"'.$js.' />'."\n"
                   .'<label for="userlogin_plugin_pass">' . __('Password') . '</label>&nbsp;'."\n"
                   .'<input type="password" name="pass" id="userlogin_plugin_pass" size="'.$size.'" maxlength="'.$maxlengthpass.'" />'."\n";

        if (pnConfigGetVar('seclevel') <> 'high') {
            $loginbox .= '<input type="checkbox" value="1" name="rememberme" id="userlogin_plugin_rememberme" />'."\n"
                        .'<label for="userlogin_plugin_rememberme">' . __('Remember me') . '</label>&nbsp;'."\n";
        }

        $loginbox .= '<input type="hidden" name="url" value="' . DataUtil::formatForDisplay($returnurl) .'" />'."\n"
                    .'<input type="submit" value="' . __('Log in') . '" />'."\n"
                    .'</div></form>'."\n";
    } else {
        $loginbox = '';
    }

    return $loginbox;
}
