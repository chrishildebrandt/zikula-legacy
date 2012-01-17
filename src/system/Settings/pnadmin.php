<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2002, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: pnadmin.php 27274 2009-10-30 13:49:20Z mateo $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @author Simon Wunderlin
 * @package Zikula_System_Modules
 * @subpackage Settings
 */

/**
 * entry point for the module
 *
 * @author Zikula development team
 * @return string html output
 */
function settings_admin_main($args)
{
    // security check
    if (!SecurityUtil::checkPermission('Settings::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }
    // create a new output object
    $pnRender = & pnRender::getInstance('Settings', false);

    return $pnRender->fetch('settings_admin_main.htm');
}

/**
 * display the main site settings form
 *
 * @author Zikula development team
 * @return string html output
 */
function settings_admin_modifyconfig($args)
{
    // security check
    if (!SecurityUtil::checkPermission('Settings::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // create a new output object
    $pnRender = & pnRender::getInstance('Settings', false);

    // get all config vars and assign them to the template
    $configvars = pnModGetVar(PN_CONFIG_MODULE);
    // since config vars are serialised and module vars aren't we
    // need to unserialise each config var in turn before assigning
    // them to the template
    foreach ($configvars as $key => $configvar) {
        $configvars[$key] = $configvar;
    }

    $pnRender->assign('settings', $configvars);

    return $pnRender->fetch('settings_admin_modifyconfig.htm');
}

/**
 * update main site settings
 *
 * @author Zikula development team
 * @return mixed true if successful, false if unsuccessful, error string otherwise
 */
function settings_admin_updateconfig($args) {

    // security check
    if (!SecurityUtil::checkPermission('Settings::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // get settings from form - do before authid check
    $settings = FormUtil::getPassedValue('settings', null, 'POST');

    // if this form wasnt posted to redirect back
    if ($settings === NULL) {
        return pnRedirect(pnModURL('Settings', 'admin', 'modifyconfig'));
    }

    // confirm the forms auth key
    if (!SecurityUtil::confirmAuthKey()) {
        return LogUtil::registerAuthidError();
    }

    // validate the entry point
    $falseEntryPoints = array('admin.php', 'ajax.php', 'backend.php', 'error.php', 'footer.php',
                              'header.php', 'install.php', 'modules.php', 'print.php', 'upgrade76.php',
                              'upgrade.php', 'user.php');
    $entryPointExt = pathinfo($settings['entrypoint'], PATHINFO_EXTENSION);

    if (in_array($settings['entrypoint'], $falseEntryPoints) || !file_exists($settings['entrypoint'])
        || strtolower($entryPointExt) != 'php') {
        LogUtil::registerError(__("Error! Either you entered an invalid entry point, or else the file specified as being the entry point was not found in the Zikula root directory."));
        $settings['entrypoint'] = pnConfigGetVar('entrypoint');
    }

    $permachecks = true;
    $settings['permasearch'] = mb_ereg_replace(' ', '', $settings['permasearch']);
    $settings['permareplace'] = mb_ereg_replace(' ', '', $settings['permareplace']);
    if (mb_ereg(',$', $settings['permasearch'])) {
        LogUtil::registerError(__("Error! In your permalink settings, strings cannot be terminated with a comma."));
        $permachecks = false;
    }

    if (mb_strlen($settings['permasearch']) == 0) {
        $permasearchCount = 0;
    } else {
        $permasearchCount = (!mb_ereg(',', $settings['permasearch']) && mb_strlen($settings['permasearch'] > 0) ? 1 : count(explode(',', $settings['permasearch'])));
    }

    if (mb_strlen($settings['permareplace']) == 0) {
        $permareplaceCount = 0;
    } else {
        $permareplaceCount = (!mb_ereg(',', $settings['permareplace']) && mb_strlen($settings['permareplace'] > 0) ? 1 : count(explode(',', $settings['permareplace'])));
    }

    if ($permareplaceCount !== $permasearchCount) {
        LogUtil::registerError(__("Error! In your permalink settings, the search list and the replacement list for permalink cleansing have a different number of comma-separated elements. If you have 3 elements in the search list then there must be 3 elements in the replacement list."));
        $permachecks = false;
    }

    if (!$permachecks) {
        unset($settings['permasearch']);
        unset($settings['permareplace']);
    }

    // Write the vars
    $configvars = pnModGetVar(PN_CONFIG_MODULE);
    foreach($settings as $key => $value) {
        $oldvalue = pnConfigGetVar($key);
        if ($value != $oldvalue) {
            pnConfigSetVar($key, $value);
        }
    }

    // clear all cache and compile directories
    pnModAPIFunc('Settings', 'admin', 'clearallcompiledcaches');

    LogUtil::registerStatus(__('Done! Saved module configuration.'));

    // Let any other modules know that the modules configuration has been updated
    pnModCallHooks('module','updateconfig','Settings', array('module' => 'Settings'));

    return pnRedirect(pnModURL('Settings', 'admin', 'modifyconfig'));
}

/**
 * display the ML settings form
 *
 * @author Zikula development team
 * @return string html output
 */
function settings_admin_multilingual($args)
{
    // security check
    if (!SecurityUtil::checkPermission('Settings::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // create a new output object
    $pnRender = & pnRender::getInstance('Settings', false);

    // get all config vars and assign them to the template
    $configvars = pnModGetVar(PN_CONFIG_MODULE);
    foreach ($configvars as $key => $configvar) {
        $configvars[$key] = $configvar;
    }

    // get the server timezone - we should not allow to change this
    $configvars['timezone_server'] = DateUtil::getTimezone();
    $configvars['timezone_server_abbr'] = DateUtil::getTimezoneAbbr();
    $pnRender->assign($configvars);

    return $pnRender->fetch('settings_admin_multilingual.htm');
}

/**
 * update ML settings
 *
 * @author Zikula development team
 * @return mixed true if successful, false if unsuccessful, error string otherwise
 */
function settings_admin_updatemultilingual($args)
{
    // security check
    if (!SecurityUtil::checkPermission('Settings::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    $url = pnModURL('Settings', 'admin', 'multilingual');

    // confirm the forms auth key
    if (!SecurityUtil::confirmAuthKey()) {
        LogUtil::registerAuthidError();
        return pnRedirect($url);
    }

    $settings = array('mlsettings_language_i18n'   => 'language_i18n',
                      'mlsettings_timezone_offset' => 'timezone_offset',
                      'mlsettings_timezone_server' => 'timezone_server',
                      'mlsettings_multilingual'    => 'multilingual',
                      'mlsettings_language_detect' => 'language_detect',
                      'mlsettings_language_bc'     => 'language_bc',
                      'mlsettings_languageurl'     => 'languageurl');

    // we can't detect language if multilingual feature is off so reset this to false
    if (FormUtil::getPassedValue('mlsettings_multilingual', null, 'POST') == 0) {
        if (pnConfigGetVar('language_detect')) {
            pnConfigSetVar('language_detect', 0);
            unset($settings['mlsettings_language_detect']);
            LogUtil::registerStatus(__('Notice: Language detection is automatically disabled when multi-lingual features are disabled.'));
        }

        $deleteLangUrl = true;
    }

    if (FormUtil::getPassedValue('mlsettings_language_bc', null, 'POST') == 0) {
        $lang = pnConfigGetVar('language_i18n');
        $newvalue = substr($lang, 0, (strpos($lang, '-') ? strpos($lang, '-') : strlen($lang)));
        if ($lang != $newvalue) {
            pnConfigSetVar('language_i18n', $newvalue);
            unset($settings['mlsettings_language_i18n']);
            LogUtil::registerStatus(__('Warning! The system language has been changed because language variations have been disabled.'));
            $deleteLangUrl = true;
        }
    }

    if (isset($deleteLangUrl)) {
        // reset language settings
        SessionUtil::delVar('language');
        $url = preg_replace('#(.*)(&lang=[a-z-]{2,5})(.*)#i', '$1$3', $url);
    }

    // Write the vars
    $configvars = pnModGetVar(PN_CONFIG_MODULE);
    foreach($settings as $formname => $varname) {
        $newvalue = FormUtil::getPassedValue($formname, null, 'POST');
        $oldvalue = pnConfigGetVar($varname);
        if ($newvalue != $oldvalue) {
            pnConfigSetVar($varname, $newvalue);
        }
    }

    // clear all cache and compile directories
    pnModAPIFunc('Settings', 'admin', 'clearallcompiledcaches');

    // all done successfully
    LogUtil::registerStatus(__('Done! Saved localisation settings.'));

    return pnRedirect($url);
}

/**
 * display the error handling settings form
 *
 * @author Zikula development team
 * @return string html output
 */
function settings_admin_errorhandling($args)
{
    // security check
    if (!SecurityUtil::checkPermission('Settings::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // create a new output object
    $pnRender = & pnRender::getInstance('Settings');

    // get all config vars and assign them to the template
    $configvars = pnModGetVar(PN_CONFIG_MODULE);
    // since config vars are serialised and module vars aren't we
    // need to unserialise each config var in turn before assigning
    // them to the template
    foreach ($configvars as $key => $configvar) {
        $configvars[$key] = $configvar;
    }
    // add the development flag
    $configvars['development'] = pnConfigGetVar('development');
    $pnRender->assign($configvars);

    return $pnRender->fetch('settings_admin_errorhandling.htm');
}

/**
 * update error handling settings
 *
 * @author Zikula development team
 * @return mixed true if successful, false if unsuccessful, error string otherwise
 */
function settings_admin_updateerrorhandling($args) {

    // security check
    if (!SecurityUtil::checkPermission('Settings::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // confirm the forms auth key
    if (!SecurityUtil::confirmAuthKey()) {
        return LogUtil::registerAuthidError();
    }

    $settings = array('errorsettings_errordisplay' => 'errordisplay',
                      'errorsettings_errorlog'     => 'errorlog',
                      'errorsettings_errormailto'  => 'errormailto',
                      'errorsettings_errorlogtype' => 'errorlogtype');
    // Write the vars
    $configvars = pnModGetVar(PN_CONFIG_MODULE);
    foreach($settings as $formname => $varname) {
        $newvalue = FormUtil::getPassedValue($formname, null, 'POST');
        $oldvalue = pnConfigGetVar($varname);
        if ($newvalue != $oldvalue) {
            pnConfigSetVar($varname, $newvalue);
        }
    }

    // clear all cache and compile directories
    pnModAPIFunc('Settings', 'admin', 'clearallcompiledcaches');

    // all done successfully
    LogUtil::registerStatus(__('Done! Saved module configuration.'));

    return pnRedirect(pnModURL('Settings', 'admin', 'errorhandling'));
}
