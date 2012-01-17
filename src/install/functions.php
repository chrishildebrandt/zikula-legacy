<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2001, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: functions.php 28319 2010-02-23 06:09:36Z drak $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @author Gregor J. Rothfuss
 */

function install()
{
    // configure our installation environment
    // no time limit since installation might take a while
    // error reporting level for debugging
    ini_set('max_execution_time', 86400);

    define('_PNINSTALLVER', '1.2.8');

    $installbySQL = false;

    // start the basics of Zikula
    require_once 'install/modify_config.php';
    require_once 'includes/pnAPI.php';
    $GLOBALS['PNConfig']['System']['language_bc'] = true;
    pnInit(PN_CORE_ALL & ~PN_CORE_THEME & ~PN_CORE_MODS & ~PN_CORE_LANGS & ~PN_CORE_DECODEURLS & ~PN_CORE_SESSIONS & ~PN_CORE_TOOLS & ~PN_CORE_AJAX);
    Loader::loadClass('ThemeUtil');
    
    // get our input
    $vars = array('lang', 'installtype', 'dbhost', 'dbusername', 'dbpassword', 'dbname', 'dbprefix', 'dbtype', 'dbtabletype', 'createdb', 'username', 'password', 'repeatpassword', 'email', 'action', 'loginuser', 'loginpassword', 'defaultmodule', 'defaulttheme');

    foreach ($vars as $var) {
        // in the install we're sure we don't wany any html so we can be stricter than
        // the FormUtil::getPassedValue API
        $$var = strip_tags(stripslashes(FormUtil::getPassedValue($var, '', 'GETPOST')));
    }

    // login to supplied admin credentials
    if (empty($action) && check_installed()) { // need auth because Zikula is already installed.
        pnInit(PN_CORE_SESSIONS);
        if (pnUserLoggedIn()) {
            if (!SecurityUtil::checkPermission('.*', '.*', ACCESS_ADMIN)) {
                pnUserLogOut(); // unpriv user so boot them.
                $action = 'login';
            }
        } else { // login failed
            $action = 'login';
        }
    }

    // check for an empty action - if so then show the first installer  page
    if (empty($action)) {
        $action = 'lang';
        $lang = 'en';
    }

    // Power users might have moved the temp folder out of the root and changed the config.php
    // accordingly. Make sure we respect this security related settings
    $tempDir = (isset($GLOBALS['PNConfig']['System']['temp']) ? $GLOBALS['PNConfig']['System']['temp'] : 'pnTemp');

    // define our smarty object
    $smarty = new Smarty();
    $smarty->left_delimiter = '<!--[';
    $smarty->right_delimiter = ']-->';
    $smarty->compile_dir = $tempDir . '/pnRender_compiled';
    $smarty->template_dir = 'install/pntemplates';
    $smarty->plugins_dir = array('plugins', 'install/pntemplates/plugins', 'system/pnRender/plugins', 'system/Theme/plugins');

    // load the installer language files
    if (empty($lang)) {
        $available = ZLanguage::getInstalledLanguages();
        $detector = new ZLanguageBrowser($available);
        $lang = $detector->discover();
    }

    // setup multilingual
    $GLOBALS['PNConfig']['System']['language_i18n'] = $lang;
    $GLOBALS['PNConfig']['System']['multilingual'] = true;
    $GLOBALS['PNConfig']['System']['languageurl'] = true;
    $GLOBALS['PNConfig']['System']['language_detect'] = false;

    $_lang = & ZLanguage::getInstance();
    $_lang->setup();

    $lang = ZLanguage::getLanguageCode();

    $smarty->assign('lang', $lang);
    $smarty->assign('installbySQL', $installbySQL);
    $smarty->assign('langdirection', ZLanguage::getDirection());
    $smarty->assign('charset', ZLanguage::getEncoding());

    // assign the values from config.php
    $smarty->assign($GLOBALS['PNConfig']['System']);

    // perform tasks based on our action
    switch ($action) {
        case 'installtype':
            $dbname = trim($dbname);
            // protection again anonymous login's (will connect by install will fail) - drak
            if (empty($dbname) || empty($dbusername)) {
                $action = 'dbinformation';
                $smarty->assign('dbconnectmissing', true);
            } elseif (preg_match('/\W/', $dbprefix)) {
                $action = 'dbinformation';
                $smarty->assign('dbinvalidprefix', true);
            } elseif (!preg_match('/^[\w-]*$/', $dbname) ||
                      strlen($dbname) > 64) {
                $action = 'dbinformation';
                $smarty->assign('dbinvalidname', true);
            } else {
                update_config_php($dbhost, $dbusername, $dbpassword, $dbname, $dbprefix, $dbtype, $dbtabletype);

                // Must reinitialize the database since settings have changed as a result of the install process.
                // We do this manually because the API doesn't allow for pnInit to be called multiple times with different info in config.php
                // Probably a better way of doing this?
                $pnconfig = array();
                $pndebug = array();

                require 'config/config.php';
                $GLOBALS['PNConfig'] = $PNConfig;

                // Decode encoded new DB parameters
                $ak = array_keys($GLOBALS['PNConfig']['DBInfo']);
                foreach ($ak as $key) {
                    if ($GLOBALS['PNConfig']['DBInfo'][$key]['encoded']) {
                        $GLOBALS['PNConfig']['DBInfo'][$key]['dbuname'] = base64_decode($GLOBALS['PNConfig']['DBInfo'][$key]['dbuname']);
                        $GLOBALS['PNConfig']['DBInfo'][$key]['dbpass'] = base64_decode($GLOBALS['PNConfig']['DBInfo'][$key]['dbpass']);
                        $GLOBALS['PNConfig']['DBInfo'][$key]['encoded'] = 0;
                    }
                }

                if ($createdb) {
                    // TODO A [these are unused: $charset, $collation - investigate] (drak)
                    //$result = makedb($dbtype, $dbhost, $dbusername, $dbpassword, $dbname, $charset, $collation);
                    $result = makedb($dbtype, $dbhost, $dbusername, $dbpassword, $dbname); //, $charset, $collation);
                    // check if we've successfully made the db
                    if ($result != 2) {
                        $action = 'dbinformation';
                        $smarty->assign('dbcreatefailed', true);
                    }
                } else {
                    // connect to the database
                    pnDBInit();
                    $dbconn = pnDBGetConn(true);
                    if (!$dbconn) {
                        $action = 'dbinformation';
                        $smarty->assign('dbconnectfailed', true);
                    }
                }

                // if not connected to the data base the connexion it connects
                if (!$dbconn) {
                    // connect to the database
                    foreach ($ak as $key) {
                        $GLOBALS['PNConfig']['DBInfo'][$key]['dbname'] = $dbname;
                    }
                    pnDBInit();
                    $dbconn = pnDBGetConn(true);
                    if(!$dbconn) {
                        $action = 'dbinformation';
                        $smarty->assign('dbconnectfailed', true);
                    }
                }
                // make sure that the database is set as utf8
                if ($dbtype == 'mysql' || $dbtype == 'mysqli') {
                    $sql = "ALTER DATABASE `$dbname` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
                } else {
                    $sql = "ALTER DATABASE $dbname DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
                }

                if (!DBUtil::executeSQL($sql)) {
                    $action = 'dbinformation';
                    $smarty->assign('dbcreatefailed', true);
                }
                // if it is the distribution and the process have not failed in a previous step
                if($installbySQL && $action != 'dbinformation'){
                    // checks if exists a previous installation with the same prefix
                    $proceed = true;
                    if ($dbtype == 'mysql' || $dbtype == 'mysqli') {
                        $exec = "SHOW TABLES FROM `$dbname` LIKE '".$dbprefix."_%'";
                    } else {
                        $exec = "SHOW TABLES FROM $dbname LIKE '".$dbprefix."_%'";
                    }
                    $tables = DBUtil::executeSQL($exec);
                    if($tables->_numOfRows > 0) {
                        $proceed = false;
                        $action = 'dbinformation';
                        $smarty->assign('dbexists', true);
                    }
                    if($proceed){
                        // create the database
                        // set sql dump file path
                        $fileurl = 'install/sql/zikulacms.sql';
                        // checks if file exists
                        if (!file_exists($fileurl)) {
                            $action = 'dbinformation';
                            $smarty->assign('dbdumpfailed', true);
                        } else {
                        	// execute the SQL dump
                            $installed = true;
                            $lines = file($fileurl);
                            $exec = '';
                            foreach ($lines as $line_num => $line) {
                                $line = trim($line);
                                if (empty($line) || strpos($line, '--') === 0)
                                    continue;
                                $exec .= $line;
                                if (strrpos($line, ';') === strlen($line) - 1) {
                                    if (!DBUtil::executeSQL(str_replace('z_', $dbprefix . '_', $exec))) {
                                        $installed = false;
                                        $action = 'dbinformation';
                                        $smarty->assign('dbdumpfailed', true);
                                        break;
                                    }
                                    $exec = '';
                                }
                            }
                        }
                    }
                    if($installed) {
                        $action = 'createadmin';
                    }
                }
            }
            break;
        case 'createadmin':
            installmodules('basic', $lang);
            if ($installtype != 'basic') {
                installmodules($installtype, $lang);
            }
            break;
        case 'login':
            if (empty($loginuser) && empty($loginpassword)) {
            } elseif (pnUserLogIn($loginuser, $loginpassword, false)) {
                if (!SecurityUtil::checkPermission('.*', '.*', ACCESS_ADMIN)) {
                    // not admin user so boot
                    pnUserLogOut();
                    $action = 'login';
                    $smarty->assign(array('loginstate' => 'notadmin'));
                } else {
                    $action = 'lang';
                }
            } else {
                // not a valid user
                $smarty->assign(array('loginstate' => 'failed'));
            }
            break;
        case 'selecttheme':
            pnConfigSetVar('startpage', $defaultmodule);
            break;
        case 'selectmodule':
            if ($password !== $repeatpassword) {
                $action = 'createadmin';
                $smarty->assign('passwordcomparefailed', true);
                $smarty->assign(array('username' => $username, 'password' => $password, 'repeatpassword' => $repeatpassword, 'email' => $email));
            } elseif (!pnVarValidate($email, 'email')) {
                $action = 'createadmin';
                $smarty->assign('emailvalidatefailed', true);
                $smarty->assign(array('username' => $username, 'password' => $password, 'repeatpassword' => $repeatpassword, 'email' => $email));
            } elseif ((!$username) || !(!preg_match("/[[:space:]]/", $username)) || !pnVarValidate($username, 'uname')) {
                $action = 'createadmin';
                $smarty->assign('uservalidatefailed', true);
                $smarty->assign(array('username' => $username, 'password' => $password, 'repeatpassword' => $repeatpassword, 'email' => $email));
            } else {
                // create our new site admin
                // TODO test the call the users module api to create the user
                //pnModAPIFunc('Users', 'user', 'finishnewuser', array('uname' => $username, 'email' => $email, 'pass' => $password));
                createuser($username, $password, $email);
                SessionUtil::requireSession();
                pnUserLogin($username, $password);

                // add admin email as site email
                pnConfigSetVar('adminmail', $email);

                update_installed_status();
                if($installbySQL){
                    $action = 'gotosite';
                }
            }
            if(!$installbySQL){
                break;
            }
        case 'gotosite':
            if(!$installbySQL){
                pnInit(PN_CORE_THEME);
                pnConfigSetVar('Default_Theme', $defaulttheme);
                pnModAPIFunc('Theme', 'admin', 'regenerate');
            }
            $smarty->assign('credits', file_get_contents('docs/CREDITS'));
            SessionUtil::requireSession();
            if (!pnUserLoggedIn()) {
                $url = sprintf('<a href="%s">%s</a>', pnGetBaseURL(), DataUtil::formatForDisplay(pnConfigGetVar('sitename')));
                $smarty->assign('link', __f('Go to the startpage for %s', $url));
            } else {
                $url = sprintf('<a href="%s">%s</a>', pnModURL('Admin', 'admin', 'adminpanel'), DataUtil::formatForDisplay(pnConfigGetVar('sitename')));
                $smarty->assign('link', __f('Go to the admin panel for %s', $url));
            }
            break;
    }

    // assign some generic variables
    $smarty->assign(compact($vars));

    // check our action template exists
    $action = DataUtil::formatForOS($action);
    if ($smarty->template_exists("installer_$action.htm")) {
        $smarty->assign('action', $action);
        $templatename = "install/pntemplates/installer_$action.htm";
    } else {
        $smarty->assign('action', 'error');
        $templatename = 'install/pntemplates/installer_error.htm';
    }

    // at this point we now have all the information requried to display
    // the output. We don't use normal smarty functions here since we
    // want to avoid the need for a template compilation directory
    // TODO: smarty kicks up some odd errors when eval'ing templates
    // this way so the evaluation is suppressed.


    // get and evaluate the action specific template and assign to our
    // main smarty object as a new template variable
    $template = file_get_contents($templatename);
    $smarty->_compile_source('evaluated template', $template, $_var_compiled);
    ob_start();
    @$smarty->_eval('?>' . $_var_compiled);
    $_includecontents = ob_get_contents();
    ob_end_clean();
    $smarty->assign('maincontent', $_includecontents);

    // get and evaluate the page template
    $template = file_get_contents('install/pntemplates/installer_page.htm');
    $smarty->_compile_source('evaluated template', $template, $_var_compiled);
    ob_start();
    @$smarty->_eval('?>' . $_var_compiled);
    $_contents = ob_get_contents();
    ob_end_clean();

    // echo our final result - the combination of the two templates
    echo $_contents;
}

/**
 * Creates the DB on new install
 *
 * This function creates the DB on new installs
 *
 * @param string $dbconn Database connection
 * @param string $dbname Database name
 */
function makedb($dbtype, $dbhost, $dbusername, $dbpassword, $dbname)
{
    //function makedb($dbtype, $dbhost, $dbusername, $dbpassword, $dbname, $charset, $collation) {
    // make a new database - the adodb way
    $dbconn = ADONewConnection($dbtype);
    // note adodb's use of mysql_connect returns a warning so checking
    // $dbh afterwards won't prevent a warning being displayed
    // so for ease we suppress errors here
    $dbh = @$dbconn->NConnect($dbhost, $dbusername, $dbpassword);
    if (!$dbh) {
        return false;
    }
    $sql = array();
    if ($dbtype == 'mysql' || $dbtype == 'mysqli') {
        $sql[] = "CREATE DATABASE `$dbname` DEFAULT CHARACTER SET utf8";
    } else if ($dbtype == 'postgres') {
        $sql[] = "CREATE DATABASE $dbname ENCODING='utf8'";
    } else if ($dbtype == 'oci') {
        $sql[] = "CREATE DATABASE $dbname national character set utf8";
    } else {
        $sql = false;
    }

    $dict = NewDataDictionary($dbconn);
    $sql = (!$sql ? $dict->CreateDatabase($dbname) : $sql);
    $return = $dict->ExecuteSQLArray($sql);
    return $return;
}

/**
 * This function inserts the default data on new installs
 */
function createuser($username, $password, $email)
{
    // get the database connection
    pnModDBInfoLoad('Users', 'Users');
    pnModDBInfoLoad('Modules', 'Modules');
    $dbconn = pnDBGetConn(true);
    $pntable = pnDBGetTables();

    // create the password hash
    $password = DataUtil::hash($password, pnModGetVar('Users', 'hash_method'));

    // prepare the data
    $username = DataUtil::formatForStore($username);
    $password = DataUtil::formatForStore($password);
    $email = DataUtil::formatForStore($email);

    // create the admin user
    $sql = "UPDATE $pntable[users]
            SET    pn_uname        = '$username',
                   pn_email        = '$email',
                   pn_pass         = '$password',
                   pn_activated    = '1',
                   pn_user_regdate = '" . date("Y-m-d H:i:s", time()) . "',
                   pn_lastlogin    = '" . date("Y-m-d H:i:s", time()) . "'
            WHERE  pn_uid   = 2";

    $result = $dbconn->Execute($sql);

    return ($result) ? true : false;
}

function installmodules($installtype = 'basic', $lang = 'en')
{
    static $modscat;

    // Lang validation
    $lang = DataUtil::formatForOS($lang);

    // load our installation configuration
    $installtype = DataUtil::formatForOS($installtype);
    if ($installtype == 'complete') {
    } elseif (file_exists("install/pninstalltypes/$installtype.php")) {
        include "install/pninstalltypes/$installtype.php";
        $func = "installer_{$installtype}_modules";
        $modules = $func();
    } else {
        return false;
    }

    // create a result set
    $results = array();

    if ($installtype == 'basic') {
        $coremodules = array('Modules', 'Admin', 'Permissions', 'Groups', 'Blocks', 'ObjectData', 'Users', 'Theme', 'Settings');
        // manually install the modules module
        foreach ($coremodules as $coremodule) {
            // sanity check - check if module is already installed
            if ($coremodule != 'Modules' && pnModAvailable($coremodule)) {
                continue;
            }
            pnModDBInfoLoad($coremodule, $coremodule);
            Loader::requireOnce("system/$coremodule/pninit.php");
            $modfunc = "{$coremodule}_init";
            if ($modfunc()) {
                $results[$coremodule] = true;
            }
        }

        pnUserLogin('Admin', 'Password', false);
        // regenerate modules list
        $filemodules = pnModAPIFunc('Modules', 'admin', 'getfilemodules');
        pnModAPIFunc('Modules', 'admin', 'regenerate', array('filemodules' => $filemodules));

        // set each of the core modules to active
        reset($coremodules);
        foreach ($coremodules as $coremodule) {
            $mid = pnModGetIDFromName($coremodule, true);
            pnModAPIFunc('Modules', 'admin', 'setstate', array('id' => $mid, 'state' => PNMODULE_STATE_INACTIVE));
            pnModAPIFunc('Modules', 'admin', 'setstate', array('id' => $mid, 'state' => PNMODULE_STATE_ACTIVE));
        }
        // Add them to the appropriate category
        reset($coremodules);

        $coremodscat = array('Modules' => __('System'), 'Permissions' => __('Users'), 'Groups' => __('Users'), 'Blocks' => __('Layout'), 'ObjectData' => __('System'), 'Users' => __('Users'), 'Theme' => __('Layout'), 'Admin' => __('System'), 'Settings' => __('System'));

        $categories = pnModAPIFunc('Admin', 'admin', 'getall');
        $modscat = array();
        foreach ($categories as $category) {
            $modscat[$category['catname']] = $category['cid'];
        }
        foreach ($coremodules as $coremodule) {
            $category = $coremodscat[$coremodule];
            pnModAPIFunc('Admin', 'admin', 'addmodtocategory', array('module' => $coremodule, 'category' => $modscat[$category]));
        }
        // create the default blocks.
        Loader::requireOnce('system/Blocks/pninit.php');
        blocks_defaultdata();
    }

    if ($installtype == 'complete') {
        $modules = array();
        $mods = pnModAPIFunc('Modules', 'admin', 'list', array('state' => PNMODULE_STATE_UNINITIALISED));
        foreach ($mods as $mod) {
            if (!pnModAvailable($mod['name'])) {
                $modules[] = $mod['name'];
            }
        }
        foreach ($modules as $module) {
            ZLanguage::bindModuleDomain($module);

            $mid = pnModGetIDFromName($module);
            // No need to specify 'interactive_init' => false here because defined('_PNINSTALLVER') evals to true in modules_pnadminapi_initialise
            $initialise = pnModAPIFunc('Modules', 'admin', 'initialise', array('id' => $mid));
            if ($initialise === true) {
                // activate it
                if (pnModAPIFunc('Modules', 'admin', 'setstate', array('id' => $mid, 'state' => PNMODULE_STATE_ACTIVE))) {
                    $results[$module] = true;
                }
            } else if ($initialise === false) {
                $results[$module] = false;
            } else {
                unset($results[$module]);
            }
        }
    } else {
        foreach ($modules as $module) {
        	ZLanguage::bindModuleDomain($module);
            // sanity check - check if module is already installed
            if (pnModAvailable($module['module'])) {
                continue;
            }

            $results[$module['module']] = false;

            // #6048 - prevent trying to install modules which are contained in an install type, but are not available physically
            if (!file_exists('system/' . $module['module'] . '/') && !file_exists('modules/' . $module['module'] . '/')) {
                continue;
            }

            $mid = pnModGetIDFromName($module['module']);

            // init it
            if (pnModAPIFunc('Modules', 'admin', 'initialise', array('id' => $mid)) == true) {
                // activate it
                if (pnModAPIFunc('Modules', 'admin', 'setstate', array('id' => $mid, 'state' => PNMODULE_STATE_ACTIVE))) {
                    $results[$module['module']] = true;
                }
                // Set category
                pnModAPIFunc('Admin', 'admin', 'addmodtocategory', array('module' => $module['module'], 'category' => $modscat[$module['category']]));
            }
        }
    }
    pnConfigSetVar('language_i18n', $lang);

    // run any post-install routines
    $func = "installer_{$installtype}_post_install";
    if (function_exists($func)) {
        $func();
    }

    return $results;
}

function check_installed()
{
    // See if installed flag is set.


    // set default
    $isinstalled = false;

    $lines = file('config/config.php');
    if ($lines != false) {
        foreach ($lines as $line) {
            $line = str_replace(' ', '', $line);
            $isinstalled = stristr($line, '$PNConfig[\'System\'][\'installed\']=1;');
            if ($isinstalled !== false) {
                break;
            }
        }
    }
    return $isinstalled;
}
