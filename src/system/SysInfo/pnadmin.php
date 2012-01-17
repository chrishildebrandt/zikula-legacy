<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2002, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: pnadmin.php 27274 2009-10-30 13:49:20Z mateo $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula_System_Modules
 * @subpackage SysInfo
 * @license http://www.gnu.org/copyleft/gpl.html
*/

/**
 * Show general installation information
 * @author Simon Birtwistle
 * @return string HTML output string
 */
function sysinfo_admin_main()
{
    if (!SecurityUtil::checkPermission('SysInfo::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // Index page, display general information
    $pnRender = & pnRender::getInstance('SysInfo');

    $pnRender->assign('pnversionnum', PN_VERSION_NUM);
    $pnRender->assign('pnversionid', PN_VERSION_ID);
    $pnRender->assign('pnversionsub', PN_VERSION_SUB);
    $serversig = pnServerGetVar('SERVER_SIGNATURE');
    if (!isset($serversig) || empty($serversig)) {
        $serversig = pnServerGetVar('SERVER_SOFTWARE');
    }
    $pnRender->assign('serversig', $serversig);
    $pnRender->assign('phpversion', phpversion());

    // Mess around with PHP functions for the various databases
    $serverInfo = DBUtil::serverInfo();
    $connectionInfo = DBConnectionStack::getConnectionInfo();
    switch ($connectionInfo['dbtype']) {
        case 'mysql':
            $dbinfo = 'MySQL ' . $serverInfo['description'];
            break;
        case 'mysqli':
            $dbinfo = 'MySQL (improved driver) ' . $serverInfo['description'];
            break;
        default:
            $dbinfo = $serverInfo['description'];
            break;
    }

    // Extensions checking
    $mysql = array('name' => 'mysql', 'reason' => __('Zikula can operate with a database of the associated type if this extension is loaded.'));
    $mysqli = array('name' => 'mysqli', 'reason' => __('Zikula can operate with a database of the associated type if this extension is loaded.'));
    $adodb = array('name' => 'ADOdb', 'reason' => __('The <a href="http://adodb.sourceforge.net/#extension">ADOdb-ext extension</a> can considerably boost a site\'s performance, by replacing parts of ADODB with C code.'));
    $suhosin_extension = array('name' => 'suhosin', 'reason' => __('The <a href="http://www.suhosin.org">Suhosin extension</a> is an advanced protection system for PHP installations. It can be used separately from the Suhosin patch or used in association with it.'));
    $suhosin_patch = array('name' => 'SUHOSIN_PATCH', 'text' => __('Suhosin'), 'reason' => __('The <a href="http://www.suhosin.org">Suhosin patch</a> is an advanced protection system for PHP installations. It can be used separately from the Suhosin extension or used in association with it.'));
    $required_extensions = array();
    $optional_extensions = array($adodb, $mysql, $mysqli, $suhosin_extension);
    $optional_patches = array($suhosin_patch);
    $extensions = array();
    $opt_extensions = array();
    $opt_patches = array();

    foreach ($required_extensions as $ext) {
        if (extension_loaded($ext['name'])) {
            $ext['loaded'] = 'greenled.gif';
            $ext['status'] = __('Loaded');
        } else {
            $ext['loaded'] = 'redled.gif';
            $ext['status'] = __('Not loaded');
        }
        $extensions[] = $ext;
    }

    foreach ($optional_extensions as $ext) {
        if (extension_loaded($ext['name'])) {
            $ext['loaded'] = 'greenled.gif';
            $ext['status'] = __('Loaded');
        } else {
            $ext['loaded'] = 'redled.gif';
            $ext['status'] = __('Not loaded');
        }
        $opt_extensions[] = $ext;
    }

    foreach ($optional_patches as $ext) {
        if (defined($ext['name'])) {
            $ext['loaded'] = 'greenled.gif';
            $ext['status'] = __('Loaded');
        } else {
            $ext['loaded'] = 'redled.gif';
            $ext['status'] = __('Not loaded');
        }
        $opt_patches[] = $ext;
    }

    $mod_security = false;
    if (function_exists('apache_get_modules')) {
        // we have an apache2
        $apache_modules = apache_get_modules();
        if (in_array("mod_security", $apache_modules)) {
            // modsecurity is installed
            $mod_security = true;
        }
    }

    $pnRender->assign('extensions', $extensions);
    $pnRender->assign('opt_extensions', $opt_extensions);
    $pnRender->assign('opt_patches', $opt_patches);
    $pnRender->assign('dbinfo', $dbinfo);

    $pnRender->assign('php_display_errors', DataUtil::getBooleanIniValue('display_errors'));
    $pnRender->assign('php_display_startup_errors', DataUtil::getBooleanIniValue('display_startup_errors'));
    $pnRender->assign('php_expose_php', DataUtil::getBooleanIniValue('expose_php'));
    $pnRender->assign('php_register_globals', DataUtil::getBooleanIniValue('register_globals'));
    $pnRender->assign('php_magic_quotes_gpc', DataUtil::getBooleanIniValue('magic_quotes_gpc'));
    $pnRender->assign('php_magic_quotes_runtime', DataUtil::getBooleanIniValue('magic_quotes_runtime'));
    $pnRender->assign('php_allow_url_fopen', DataUtil::getBooleanIniValue('allow_url_fopen'));
    $pnRender->assign('php_allow_url_include', DataUtil::getBooleanIniValue('allow_url_include'));
    $pnRender->assign('php_disable_functions', DataUtil::getBooleanIniValue('disable_functions'));
    $pnRender->assign('mod_security', (bool)$mod_security);

    return $pnRender->fetch('sysinfo_admin_main.htm');
}

/**
 * Show PHP information
 * @param int 'info' The part of phpinfo to display
 * @author Simon Birtwistle
 * @return string HTML output string
 */
function sysinfo_admin_phpinfo()
{
    if (!SecurityUtil::checkPermission('SysInfo::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    $info = FormUtil::getPassedValue('info', empty($info) ? 4 : $info, 'REQUEST');

    // PHP Configuration stuff
    $pnRender = & pnRender::getInstance('SysInfo');

    // Output buffering appears to be the only way to do this...
    ob_start();
    phpinfo($info);
    $phpinfo = ob_get_contents();
    ob_end_clean();

    // Borrowed from Xaraya, credit to Jason Judge
    // This is all for formatting
    $phpinfo = preg_replace(array('/^.*<body[^>]*>/is', '/<\/body[^>]*>.*$/is'), '', $phpinfo, 1);

    // get rid of hard rules
    $phpinfo = str_replace('<hr />', '', $phpinfo);

    // Remove pixel table widths.
    $phpinfo = preg_replace('/width="[0-9]+"/i', 'width="80%"', $phpinfo);

    // change the table into our standard admin table format
    $phpinfo = str_replace('<table border="0" cellpadding="3" width="80%">', '<table class="z-admintable">', $phpinfo);
    $phpinfo = str_replace('<tr class="h">', '<tr>', $phpinfo);
    $phpinfo = str_replace('</th></tr>', '</th></tr>', $phpinfo);
    $phpinfo = str_replace('</tr></table>', '</tr></table>', $phpinfo);
    $phpinfo = str_replace('<a name=', '<a id=', $phpinfo);
    $phpinfo = str_replace('<font', '<span', $phpinfo);
    $phpinfo = str_replace('</font', '</span', $phpinfo);

    // match class "v" td cells an pass them to callback function
    $phpinfo = preg_replace_callback('%(<td class="v">)(.*?)(</td>)%i', '_sysinfo_phpinfo_v_callback', $phpinfo);

    // add the relevant row classes
    // we have to break the output into an array so that the starting class can be reset each time
    $phpinfo = explode('<tbody>', $phpinfo);
    foreach ($phpinfo as $key => $source) {
        $GLOBALS['class'] = '_sysinfo_phpinfo_class';
        $phpinfo[$key] = preg_replace_callback('/<tr>/', '_sysinfo_phpinfo_callback', $source);
    }
    $phpinfo = implode('', $phpinfo);

    //$pnRender->assign('title', $title);
    $pnRender->assign('phpinfo', $phpinfo);

    return $pnRender->fetch('sysinfo_admin_phpinfo.htm');
}

/**
 * callback function to add PN table tow classes to phpinfo report
 *
 */
function _sysinfo_phpinfo_callback()
{
    $GLOBALS['_sysinfo_phpinfo_class'] = (!isset($GLOBALS['_sysinfo_phpinfo_class']) || $GLOBALS['_sysinfo_phpinfo_class'] == 'z-odd') ? 'z-even' : 'z-odd';
    return '<tr class="'.$GLOBALS['_sysinfo_phpinfo_class'].'">';
}

/**
 * callback function to eventually add an extra space in passed <td class="v">...</td>
 * after a ";" or "@" char to let the browser split long lines nicely
 * see patch #5343 - credits go to mrunreal
 */
function _sysinfo_phpinfo_v_callback($matches)
{
    $matches[2] = preg_replace('%(?<!\s)([;@])(?!\s)%',"$1 ",$matches[2]);
    return $matches[1].$matches[2].$matches[3];
}

/**
 * Show version information for installed Zikula modules
 * @author Simon Birtwistle
 * @return string HTML output string
 */
function sysinfo_admin_extensions()
{
    if (!SecurityUtil::checkPermission('SysInfo::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // Zikula Modules and Themes versions
    $pnRender = & pnRender::getInstance('SysInfo');
    Loader::loadClass('ModuleUtil');
    $pnRender->assign('mods', ModuleUtil::getModules());
    $pnRender->assign('themes', ThemeUtil::getAllThemes());

    return $pnRender->fetch('sysinfo_admin_extensions.htm');
}

/**
 * Show writable files and folders within the filesystem
 * @author Andreas Krapohl
 * @return string HTML output string
 */
function sysinfo_admin_filesystem()
{
    if (!SecurityUtil::checkPermission('SysInfo::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // Zikula Modules versions
    $pnRender = & pnRender::getInstance('SysInfo');

    $pntemp = DataUtil::formatForOS(CacheUtil::getLocalDir(),true);
    $filelist = pnModAPIFunc('SysInfo', 'admin', 'filelist');

    $pnRender->assign('filelist', $filelist);
    $pnRender->assign('pntemp', $pntemp);

    return $pnRender->fetch('sysinfo_admin_filesystem.htm');
}

/**
 * Show writable files and folders within pntemp
 * @author Andreas Krapohl
 * @return string HTML output string
 */
function sysinfo_admin_pntemp()
{
    if (!SecurityUtil::checkPermission('SysInfo::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // Zikula Modules versions
    $pnRender = & pnRender::getInstance('SysInfo');

    //$pntemp = DataUtil::formatForOS(CacheUtil::getLocalDir());
    $pntemp = DataUtil::formatForOS(CacheUtil::getLocalDir(),true);
    $filelist = pnModApiFunc('SysInfo', 'admin', 'filelist',
                             array ('startdir' => $pntemp . '/',
                                    'pntemp' => 1));
    $pnRender->assign('filelist', $filelist);
    $pnRender->assign('pntemp', $pntemp);

    return $pnRender->fetch('sysinfo_admin_filesystem.htm');
}

/**
 * This is a standard function to modify the configuration parameters of the
 * module
 * @author Mark West
 * @return string HTML string
 */
function sysinfo_admin_adodb()
{
    // Security check
    if (!SecurityUtil::checkPermission('SysInfo::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    $pnRender = & pnRender::getInstance('SysInfo', false);

    // database type
    $pnRender->assign('version', $GLOBALS['ADODB_vers']);
    // Cache directory
    $pnRender->assign('cachedir', $GLOBALS['ADODB_CACHE_DIR']);

    // assign the db connection and server info
    $pnRender->assign('dbconn', $dbconn = DBConnectionStack::getConnection());
    $pnRender->assign('dbserverinfo', $dbconn->ServerInfo());

    // Return the output that has been generated by this function
    return $pnRender->fetch('sysinfo_admin_adodb.htm');
}

/**
 * Flush ADODB cache
 * @author Mark West
 * @param bool $args['confirmation'] confirmation of deletion
 * @return mixed HTML string if no confirmation, true if sccuessful, false otherwise
 */
function sysinfo_admin_flushdbcache($args)
{
    // Security check
    if (!SecurityUtil::checkPermission('SysInfo::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    $confirmation = FormUtil::getPassedValue('confirmation', null, 'POST');

    // Check for confirmation.
    if (empty($confirmation)) {
        // No confirmation yet - display a suitable form to obtain confirmation
        // of this action from the user

        // Create output object
        $pnRender = & pnRender::getInstance('SysInfo', false);

        // Return the output that has been generated by this function
        return $pnRender->fetch('sysinfo_admin_flushdbcache.htm');
    }

    if (!SecurityUtil::confirmAuthKey()) {
        return LogUtil::registerAuthidError(pnModURL('sysinfo', 'admin', 'view'));
    }

    $dbconn = pnDBGetConn(true);
    $result = $dbconn->CacheFlush();
    LogUtil::registerStatus(__('Done! Flushed the database cache.'));

    return pnRedirect(pnModURL('SysInfo', 'admin', 'main'));
}
