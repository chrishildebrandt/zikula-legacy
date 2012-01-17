<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2001, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: pnMod.php 28014 2009-12-28 13:08:45Z drak $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula_Core
 * @subpackage pnAPI
 */

/**
 * pnModInitCoreVars
 *
 * preloads module vars for a number of key modules to reduce sql statements
 * @access private
 * @return void
 */
function pnModInitCoreVars()
{
    global $pnmodvar;

    // don't init vars during the installer
    if (defined('_PNINSTALLVER')) {
        return;
    }

    // if we haven't got vars for this module yet then lets get them
    if (!isset($pnmodvar)) {
        $pnmodvar = array();
        $pntables = pnDBGetTables();
        $col = $pntables['module_vars_column'];

        $where = "$col[modname]='" . PN_CONFIG_MODULE . "' OR $col[modname]='pnRender' OR $col[modname]='Theme' OR $col[modname]='Blocks' OR $col[modname]='Users' OR $col[modname]='Settings'";
        $profileModule = pnConfigGetVar('profilemodule', '');
        if (!empty($profileModule) && pnModAvailable($profileModule)) {
            $where .= " OR $col[modname] = '" . $profileModule . "'";
        }

        $pnmodvars = DBUtil::selectObjectArray('module_vars', $where);
        foreach ($pnmodvars as $var) {
            $pnmodvar[$var['modname']][$var['name']] = @unserialize($var['value']);
        }
    }
}

/**
 * pnModVarExists - check to see if a module variable is set
 * @author Chris Miller
 * @param 'modname' the name of the module
 * @param 'name' the name of the variable
 * @return  true if the variable exists in the database, false if not
 */
function pnModVarExists($modname, $name)
{
    // define input, all numbers and booleans to strings
    $modname = isset($modname) ? ((string) $modname) : '';
    $name = isset($name) ? ((string) $name) : '';

    // make sure we have the necessary parameters
    if (!pnVarValidate($modname, 'mod') || !pnVarValidate($name, 'modvar')) {
        return false;
    }

    // get all module vars for this module
    $modvars = pnModGetVar($modname);

    // return boolean indicator
    // bp 2008-03-01 checking with isset() will prevent variables with Null values to be found!
    // bp 2008-03-03 we revert to the old logic because some other code can't handle Null variables
    return isset($modvars[$name]);

//  if (is_null($modvars[$name]) || isset($modvars[$name])) {
//      return true;
//  }


}

/**
 * pnModGetVar - get a module variable
 *
 * if the name parameter is included then function returns the
 * module variable value.
 * if the name parameter is ommitted then function returns a multi
 * dimentional array of the keys and values for the module vars.
 *
 * @author Jim McDonald <jim@mcdee.net>
 * @param 'modname' the name of the module
 * @param 'name' the name of the variable
 * @param 'default' the value to return if the requested modvar is not set
 * @return  if the name parameter is included then function returns
 *          string - module variable value
 *          if the name parameter is ommitted then function returns
 *          array - multi dimentional array of the keys
 *                  and values for the module vars.
 */
function pnModGetVar($modname, $name = '', $default = false)
{
    // if we don't know the modname then lets assume it is the current
    // active module
    if (!isset($modname)) {
        $modname = pnModGetName();
    }

    global $pnmodvar;

    // if we haven't got vars for this module yet then lets get them
    if (!isset($pnmodvar[$modname])) {
        $pntables = pnDBGetTables();
        $col = $pntables['module_vars_column'];
        $where = "WHERE $col[modname]='" . DataUtil::formatForStore($modname) . "'";
        $sort = ' '; // this is not a mistake, it disables the default sort for DBUtil::selectFieldArray()


        $results = DBUtil::selectFieldArray('module_vars', 'value', $where, $sort, false, 'name');
        foreach ($results as $k => $v) {
            // Be carefull to allow check for unserialize of empty values and boolean false
            $pnmodvar[$modname][$k] = @unserialize($v);
            if ($pnmodvar[$modname][$k] === false && $v != 'b:0;') {
                // backwards compatibility for non-serialized vars
                $pnmodvar[$modname][$k] = $v;
            }
        }
    }

    // if they didn't pass a variable name then return every variable
    // for the specified module as an associative array.
    // array('var1'=>value1, 'var2'=>value2)
    if (empty($name) && isset($pnmodvar[$modname])) {
        return $pnmodvar[$modname];
    }

    // since they passed a variable name then only return the value for
    // that variable
    // bp 2008-03-01 just checking with isset() will prevent Null values to be returned! (bug #15857)
    // bp 2008-03-03 we revert to the old logic because some other code can't handle Null variables
    if (isset($pnmodvar[$modname][$name])) {
        //        if (is_null($pnmodvar[$modname][$name]) || isset($pnmodvar[$modname][$name])) {
        return $pnmodvar[$modname][$name];
    }

    // we don't know the required module var but we established all known
    // module vars for this module so the requested one can't exist.
    // we return the default (which itself defaults to false)
    return $default;
}

/**
 * pnModSetVar - set a module variable
 * @author Jim McDonald <jim@mcdee.net>
 * @link http://www.mcdee.net
 * @param 'modname' the name of the module
 * @param 'name' the name of the variable
 * @param 'value' the value of the variable
 * @return bool true if successful, false otherwise
 */
function pnModSetVar($modname, $name, $value = '')
{
    // define input, all numbers and booleans to strings
    $modname = isset($modname) ? ((string) $modname) : '';

    // validate
    if (!pnVarValidate($modname, 'mod') || !isset($name)) {
        return false;
    }

    // bp 2008-03-03 - workaround for bug bug #15857
    // code removed to fix bugs #15939 and #15968
    /*if (isset($name) && is_null($value)) {
    return LogUtil::registerError (pnML('_ERROR_NONULLVALUEALLOWED', array('modname' => $modname, 'varname' => $name)));
    }*/

    global $pnmodvar;

    $obj = array();

    // This test can be removed after a future 0.8 release - drak
    if (defined('_PNINSTALLVER')) {
        $obj['value'] = DataUtil::is_serialized($value) ? $value : serialize($value);
    } else {
        $obj['value'] = serialize($value);
    }

    if (pnModVarExists($modname, $name)) {
        $pntable = pnDBGetTables();
        $cols = $pntable['module_vars_column'];
        $where = "WHERE $cols[modname] = '" . DataUtil::formatForStore($modname) . "'
                    AND $cols[name] = '" . DataUtil::formatForStore($name) . "'";
        $res = DBUtil::updateObject($obj, 'module_vars', $where);
    } else {
        $obj['name'] = $name;
        $obj['modname'] = $modname;
        $res = DBUtil::insertObject($obj, 'module_vars');
    }

    if ($res)
        $pnmodvar[$modname][$name] = $value;

    return (bool) $res;
}

/**
 * pnModSetVars - set multiple module variables
 * @param 'modname' the name of the module
 * @param 'vars' an associative array of varnames/varvalues.
 * @return bool true if successful, false otherwise
 */
function pnModSetVars($modname, $vars)
{
    $ok = true;
    foreach ($vars as $var => $value)
        $ok = $ok && pnModSetVar($modname, $var, $value);
    return $ok;
}

/**
 * pnModDelVar
 *
 * Delete a module variables. If the optional name parameter is not supplied all variables
 * for the module 'modname' are deleted
 * @author Jim McDonald <jim@mcdee.net>
 * @link http://www.mcdee.net
 * @param 'modname' the name of the module
 * @param 'name' the name of the variable (optional)
 * @return bool true if successful, false otherwise
 */
function pnModDelVar($modname, $name = '')
{
    // define input, all numbers and booleans to strings
    $modname = isset($modname) ? ((string) $modname) : '';

    // validate
    if (!pnVarValidate($modname, 'modvar')) {
        return false;
    }

    global $pnmodvar;
    $val = null;
    if (empty($name)) {
        if (isset($pnmodvar[$modname])) {
            unset($pnmodvar[$modname]);
        }
    } else {
        if (isset($pnmodvar[$modname][$name])) {
            $val = $pnmodvar[$modname][$name];
            unset($pnmodvar[$modname][$name]);
        }
    }

    $pntable = pnDBGetTables();
    $cols = $pntable['module_vars_column'];

    // check if we're deleting one module var or all module vars
    $specificvar = '';
    $name = DataUtil::formatForStore($name);
    $modname = DataUtil::formatForStore($modname);
    if (!empty($name)) {
        $specificvar = " AND $cols[name] = '$name'";
    }

    $where = "WHERE $cols[modname] = '$modname' $specificvar";
    $res = (bool) DBUtil::deleteWhere('module_vars', $where);
    return ($val ? $val : $res);
}

/**
 * pnModGetIDFromName - get module ID given its name
 * @author Jim McDonald <jim@mcdee.net>
 * @link http://www.mcdee.net
 * @param 'module' the name of the module
 * @return int module ID
 */
function pnModGetIDFromName($module)
{
    // define input, all numbers and booleans to strings
    $module = (isset($module) ? strtolower((string) $module) : '');

    // validate
    if (!pnVarValidate($module, 'mod')) {
        return false;
    }

    static $modid;

    if (!is_array($modid) || defined('_PNINSTALLVER')) {
        $modules = pnModGetModsTable();

        if ($modules === false) {
            return;
        }

        foreach ($modules as $mod) {
            $mName = strtolower($mod['name']);
            $modid[$mName] = $mod['id'];
            if (isset($mod['url']) && $mod['url']) {
                $mdName = strtolower($mod['url']);
                $modid[$mdName] = $mod['id'];
            }
        }

        if (!isset($modid[$module])) {
            $modid[$module] = false;
            return false;
        }
    }

    if (isset($modid[$module])) {
        return $modid[$module];
    }

    return false;
}

/**
 * get information on module
 * return array of module information or false if core ( id = 0 )
 * @author Jim McDonald <jim@mcdee.net>
 * @link http://www.mcdee.net
 * @param 'id' module ID
 * @return mixed module information array or false
 */
function pnModGetInfo($modid = 0)
{
    // a $modid of 0 is associated with the core ( pn_blocks.mid, ... ).
    if (!is_numeric($modid)) {
        return false;
    }

    if ($modid == 0) {
        // 0 = the core itself, create a basic dummy module
        $modinfo['name'] = 'zikula';
        $modinfo['id'] = 0;
        $modinfo['displayname'] = 'Zikula Core v' . PN_VERSION_NUM;
        return $modinfo;
    }

    static $modinfo;

    if (!is_array($modinfo) || defined('_PNINSTALLVER')) {
        $modinfo = pnModGetModsTable();

        if (!$modinfo) {
            return null;
        }

        if (!isset($modinfo[$modid])) {
            $modinfo[$modid] = false;
            return $modinfo[$modid];
        }
    }

    if (isset($modinfo[$modid])) {
        return $modinfo[$modid];
    }

    return false;
}

/**
 * get list of user modules
 * @author Jim McDonald <jim@mcdee.net>
 * @author Robert Gasch
 * @link http://www.mcdee.net
 * @return array array of module information arrays
 */
function pnModGetUserMods()
{
    return pnModGetTypeMods('user');
}

/**
 * get list of profile modules
 *
 * @author Axel Guckelsberger
 * @return array array of module information arrays
 */
function pnModGetProfileMods()
{
    return pnModGetTypeMods('profile');
}

/**
 * get list of message modules
 *
 * @author Axel Guckelsberger
 * @return array array of module information arrays
 */
function pnModGetMessageMods()
{
    return pnModGetTypeMods('message');
}

/**
 * get list of administration modules
 * @author Jim McDonald <jim@mcdee.net>
 * @author Robert Gasch
 * @link http://www.mcdee.net
 * @return array array of module information arrays
 */
function pnModGetAdminMods()
{
    return pnModGetTypeMods('admin');
}

/**
 * get list of modules by module type
 * @author Jim McDonald <jim@mcdee.net>
 * @author Robert Gasch
 * @param 'type' the module type to get (either 'user' or 'admin') (optional) (default='user')
 * @return array array of module information arrays
 */
function pnModGetTypeMods($type = 'user')
{
    if ($type != 'user' && $type != 'admin' && $type != 'profile' && $type != 'message')
        $type = 'user';

    static $modcache = array();

    if (!isset($modcache[$type]) || !$modcache[$type]) {
        $modcache[$type] = array();
        $cap = $type . '_capable';
        $mods = pnModGetAllMods();
        $ak = array_keys($mods);
        foreach ($ak as $k) {
            if ($mods[$k][$cap] == '1') {
                $modcache[$type][] = $mods[$k];
            }
        }
    }

    return $modcache[$type];
}

/**
 * get list of all modules
 * @author Mark West <mark@markwest.me.uk>
 * @link http://www.markwest.me.uk
 * @return array array of module information arrays
 */
function pnModGetAllMods()
{
    static $modsarray = array();

    if (empty($modsarray)) {
        $pntable = pnDBGetTables();
        $modulescolumn = $pntable['modules_column'];
        $where = "WHERE $modulescolumn[state] = " . PNMODULE_STATE_ACTIVE . "
                  OR $modulescolumn[name] = 'Modules'";
        $orderBy = "ORDER BY $modulescolumn[displayname]";
        $modsarray = DBUtil::selectObjectArray('modules', $where, $orderBy);
        if ($modsarray === false) {
            return false;
        }
    }

    return $modsarray;
}

/**
 * load datbase definition for a module
 * @author Jim McDonald <jim@mcdee.net>
 * @link http://www.mcdee.net
 * @param 'name' the name of the module to load database definition for
 * @param 'directory' directory that module is in (if known)
 * @param 'force' force table information to be reloaded
 * @return bool true if successful, false otherwise
 */
function pnModDBInfoLoad($modname, $directory = '', $force = false)
{
    // define input, all numbers and booleans to strings
    $modname = (isset($modname) ? strtolower((string) $modname) : '');

    // default return value
    $data = false;

    // validate
    if (!pnVarValidate($modname, 'mod')) {
        return $data;
    }

    static $loaded = array();
    // Check to ensure we aren't doing this twice
    if (isset($loaded[$modname]) && !$force) {
        $result = true;
        return $result;
    }

    // Get the directory if we don't already have it
    if (empty($directory)) {
        // get the module info
        $modinfo = pnModGetInfo(pnModGetIDFromName($modname));
        $directory = $modinfo['directory'];
    }

    // not required since it's done elsewhere
    //$osDirectory = DataUtil::formatForOS($directory);


    // Load the database definition if required
    $files = array();
    $files[] = "config/functions/$directory/pntables.php";
    $files[] = "system/$directory/pntables.php";
    $files[] = "modules/$directory/pntables.php";
    Loader::loadOneFile($files);

    $tablefunc = $modname . '_pntables';
    if (function_exists($tablefunc)) {
        $data = $tablefunc();
        // V4B RNG: added casts to ensure proper behaviour under PHP5
        $GLOBALS['pntables'] = array_merge((array) $GLOBALS['pntables'], (array) $data);
    }
    $loaded[$modname] = true;

    // V4B RNG: return data so we know which tables were loaded by this module
    return $data;
}

/**
 * load a module
 * @author Robert Gasch
 * @param 'name' the name of the module
 * @param 'type' the type of functions to load
 * @param 'force' determines to load Module even if module isn't active
 * @return string name of module loaded, or false on failure
 */
function pnModLoad($modname, $type = 'user', $force = false)
{
    if (strtolower(substr($type, -3)) == 'api') {
        return false;
    }
    return pnModLoadGeneric($modname, $type, $force);
}

/**
 * load an API module
 * @author Robert Gasch
 * @param 'name' the name of the module
 * @param 'type' the type of functions to load
 * @param 'force' determines to load Module even if module isn't active
 * @return string name of module loaded, or false on failure
 */
function pnModAPILoad($modname, $type = 'user', $force = false)
{
    return pnModLoadGeneric($modname, $type, $force, true);
}

/**
 * load a module
 * @access private
 * @author Robert Gasch
 * @author Jim McDonald <jim@mcdee.net>
 * @param 'name' the name of the module
 * @param 'type' the type of functions to load
 * @param 'force' determines to load Module even if module isn't active
 * @param 'api' whether or not to load an API (or regular) module
 * @return string name of module loaded, or false on failure
 */
function pnModLoadGeneric($modname, $type = 'user', $force = false, $api = false)
{
    // define input, all numbers and booleans to strings
    $osapi = ($api ? 'api' : '');
    $modname = isset($modname) ? ((string) $modname) : '';
    $modtype = strtolower("$modname{$type}{$osapi}");

    static $loaded = array();
    if (!empty($loaded[$modtype])) {
        // Already loaded from somewhere else
        return true;
    }

    // check the modules state
    if (!$force && !pnModAvailable($modname) && $modname != 'Modules') {
        return false;
    }

    // get the module info
    $modinfo = pnModGetInfo(pnModGetIDFromName($modname));
    if (!$modinfo) { // check for bad pnVarValidate($modname)
        return false;
    }

    // create variables for the OS preped version of the directory
    $modpath = ($modinfo['type'] == 3) ? 'system' : 'modules';
    $osdirectory = DataUtil::formatForOS($modinfo['directory']);
    $ostype  = DataUtil::formatForOS($type);
    $cosfile = "config/functions/$osdirectory/pn{$ostype}{$osapi}.php";
    $mosfile = "$modpath/$osdirectory/pn{$ostype}{$osapi}.php";
    $mosdir  = "$modpath/$osdirectory/pn{$ostype}{$osapi}";

    if (file_exists($cosfile)) {
        // Load the file from config
        Loader::includeOnce($cosfile);
    } elseif (file_exists($mosfile)) {
        // Load the file from modules
        Loader::includeOnce($mosfile);
    } elseif (is_dir($mosdir)) {
    } else {
        // File does not exist
        return false;
    }
    $loaded[$modtype] = 1;

    // Load the module language files
    if (!isset($loaded[strtolower($modname)])) {
        pnModLangLoad($modname, 'common', false);
        $loaded[strtolower($modname)] = 1;
    }

    if ($modinfo['i18n']) {
        ZLanguage::bindModuleDomain($modname);
    }

    if ($modinfo['type'] == 2 && !$modinfo['i18n']) {
        pnModLangLoad($modname, 'common');
        pnModLangLoad($modname, $type, $api);
    }

    // Load database info
    pnModDBInfoLoad($modname, $modinfo['directory']);

    // add stylesheet to the page vars, this makes the modulestylesheet plugin obsolete,
    // but only for non-api loads as we would pollute the stylesheets
    // not during installation as the Theme engine may not be available yet and not for system themes
    // TO-DO: figure out how to determine if a userapi belongs to a hook module and load the
    //        corresponding css, perhaps with a new entry in modules table?
    if (!defined('_PNINSTALLVER')) {
        if ($api == false) {
            PageUtil::addVar('stylesheet', ThemeUtil::getModuleStylesheet($modname));
            if ($type == 'admin') {
                // load special admin.css for administrator backend
                PageUtil::addVar('stylesheet', ThemeUtil::getModuleStylesheet('Admin', 'admin.css'));
            }
        }
    }

    return $modname;
}

/**
 * run a module function
 * @author Jim McDonald <jim@mcdee.net>
 * @author Robert Gasch
 * @link http://www.mcdee.net
 * @param 'modname' the name of the module
 * @param 'type' the type of function to run
 * @param 'func' the specific function to run
 * @param 'args' the arguments to pass to the function
 * @returns mixed
 */
function pnModFunc($modname, $type = 'user', $func = 'main', $args = array())
{
    return pnModFuncExec($modname, $type, $func, $args);
}

/**
 * run a module API function
 * @author Jim McDonald <jim@mcdee.net>
 * @author Robert Gasch
 * @link http://www.mcdee.net
 * @param 'modname' the name of the module
 * @param 'type' the type of function to run
 * @param 'func' the specific function to run
 * @param 'args' the arguments to pass to the function
 * @returns mixed
 */
function pnModAPIFunc($modname, $type = 'user', $func = 'main', $args = array())
{
    if (empty($type)) {
        $type = 'user';
    } elseif (!pnVarValidate($type, 'api')) {
        return null;
    }

    if (empty($func)) {
        $func = 'main';
    }

    return pnModFuncExec($modname, $type, $func, $args, true);
}

/**
 * run a module function
 * @author Robert Gasch
 * @access private
 * @param 'modname' the name of the module
 * @param 'type' the type of function to run
 * @param 'func' the specific function to run
 * @param 'args' the arguments to pass to the function
 * @param 'api' whether or not to execute an API (or regular) function
 * @returns mixed
 */
function pnModFuncExec($modname, $type = 'user', $func = 'main', $args = array(), $api = false)
{
    // define input, all numbers and booleans to strings
    $modname = isset($modname) ? ((string) $modname) : '';
    $ftype = ($api ? 'api' : '');
    $loadfunc = ($api ? 'pnModAPILoad' : 'pnModLoad');

    // validate
    if (!pnVarValidate($modname, 'mod')) {
        return null;
    }

    $modinfo = pnModGetInfo(pnModGetIDFromName($modname));
    $path = ($modinfo['type'] == '3' ? 'system' : 'modules');

    // Build function name and call function
    $modfunc = "{$modname}_{$type}{$ftype}_{$func}";
    if ($loadfunc($modname, $type)) {
        if (function_exists($modfunc)) {
            return $modfunc($args);
        }
        // get the theme
        if ($GLOBALS['loadstages'] & PN_CORE_THEME) {
            $theme = ThemeUtil::getInfo(ThemeUtil::getIDFromName(pnUserGetTheme()));
            if (file_exists($file = 'themes/' . $theme['directory'] . '/functions/' . $modname . "/pn{$type}{$ftype}/$func.php")) {
                Loader::loadFile($file);
                if (function_exists($modfunc)) {
                    return $modfunc($args);
                }
            }
        }
        if (file_exists($file = "config/functions/$modname/pn{$type}{$ftype}/$func.php")) {
            Loader::loadFile($file);
            if (function_exists($modfunc)) {
                return $modfunc($args);
            }
        }
        if (file_exists($file = "$path/$modname/pn{$type}{$ftype}/$func.php")) {
            Loader::loadFile($file);
            if (function_exists($modfunc)) {
                return $modfunc($args);
            }
        }
    }

// if we get here, the function does not exist - show an error and die()
// to-do: get execptions working for better handling of such errors
// commented out for the minute since the new url scheme calls a module
// api that might not exist (to decode module specific urls) - markwest
/*  include_once 'header.php';
    echo DataUtil::formatForDisplayHTML(_UNKNOWNFUNC) . " " . DataUtil::formatForDisplay($modfunc) . "()<br />\n";
    if (SecurityUtil::checkPermission($modname . '.*', '.*', ACCESS_ADMIN)) {
    foreach($args as $key => $value) {
    echo DataUtil::formatForDisplay($key) . " => " . DataUtil::formatForDisplay($value) . "<br />\n";
    }
    }
    include_once 'footer.php';
    pnShutDown();*/
}

/**
 * generate a module function URL
 *
 * if the module is non-API compliant (type 1) then
 * a) $func is ignored.
 * b) $type=admin will generate admin.php?module=... and $type=user will generate index.php?name=...
 *
 * @author Jim McDonald <jim@mcdee.net>
 * @link http://www.mcdee.net
 * @param 'modname' the name of the module
 * @param 'type' the type of function to run
 * @param 'func' the specific function to run
 * @param 'args' the array of arguments to put on the URL
 * @param 'ssl'  set to constant null,true,false $ssl = true not $ssl = 'true'  null - leave the current status untouched, true - create a ssl url, false - create a non-ssl url
 * @param 'fragment' the framgment to target within the URL
 * @param 'fqurl' Fully Qualified URL. True to get full URL, eg for Redirect, else gets root-relative path unless SSL
 * @param 'forcelongurl' force pnModURL to not create a short url even if the system is configured to do so
 * @return sting absolute URL for call
 */
function pnModURL($modname, $type = 'user', $func = 'main', $args = array(), $ssl = null, $fragment = null, $fqurl = null, $forcelongurl = false, $forcelang=false)
{
    // define input, all numbers and booleans to strings
    $modname = isset($modname) ? ((string) $modname) : '';

    // validate
    if (!pnVarValidate($modname, 'mod')) {
        return null;
    }

    //get the module info
    $modinfo = pnModGetInfo(pnModGetIDFromName($modname));

    // set the module name to the display name if this is present
    if (isset($modinfo['url']) && !empty($modinfo['url'])) {
        $modname = rawurlencode($modinfo['url']);
    }

    // define some statics as this API is likely to be called many times
    static $entrypoint, $host, $baseuri, $https, $shorturlstype, $shorturlsstripentrypoint, $shorturlsdefaultmodule;

    // entry point
    if (!isset($entrypoint)) {
        $entrypoint = pnConfigGetVar('entrypoint');
    }
    // Hostname
    if (!isset($host)) {
        $host = pnServerGetVar('HTTP_HOST');
    }
    if (empty($host))
        return false;
        // Base URI
    if (!isset($baseuri)) {
        $baseuri = pnGetBaseURI();
    }
    // HTTPS Support
    if (!isset($https)) {
        $https = pnServerGetVar('HTTPS');
    }
    // use friendly url setup
    if (!isset($shorturls)) {
        $shorturls = pnConfigGetVar('shorturls');
    }
    if (!isset($shorturlstype)) {
        $shorturlstype = pnConfigGetVar('shorturlstype');
    }
    if (!isset($shorturlsstripentrypoint)) {
        $shorturlsstripentrypoint = pnConfigGetVar('shorturlsstripentrypoint');
    }
    if (!isset($shorturlsdefaultmodule)) {
        $shorturlsdefaultmodule = pnConfigGetVar('shorturlsdefaultmodule');
    }

    // Don't encode URLs with escaped characters, like return urls.
    foreach ($args as $v) {
        if (!is_array($v)) {
            if (strpos($v, '%') !== false) {
                $shorturls = false;
                break;
            }
        } else {
            foreach ($v as $vv) {
                if (strpos($vv, '%') !== false) {
                    $shorturls = false;
                    break;
                }
            }
            break;
        }
    }

    $language = ($forcelang ? $forcelang : ZLanguage::getLanguageCode());

    // Only produce full URL when HTTPS is on or $ssl is set
    $siteRoot = '';
    if ((isset($https) && $https == 'on') || $ssl != null || $fqurl == true) {
        $protocol = 'http' . (($https == 'on' && $ssl !== false) || $ssl === true ? 's' : '');
        $secureDomain = pnConfigGetVar('secure_domain');
        $siteRoot = $protocol . '://' . (($secureDomain != '') ? $secureDomain : ($host . $baseuri)) . '/';
    }

    // Only convert User URLs. Exclude links that append a theme parameter
    if ($shorturls && $shorturlstype == 0 && $type == 'user' && $forcelongurl == false) {
        if (isset($args['theme'])) {
            $theme = $args['theme'];
            unset($args['theme']);
        }
        // Module-specific Short URLs
        $url = pnModAPIFunc($modinfo['name'], 'user', 'encodeurl', array('modname' => $modname, 'type' => $type, 'func' => $func, 'args' => $args));
        if (empty($url)) {
            // depending on the settings, we have generic directory based short URLs:
            // [language]/[module]/[function]/[param1]/[value1]/[param2]/[value2]
            // [module]/[function]/[param1]/[value1]/[param2]/[value2]
            $vars = '';
            foreach ($args as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $k2 => $w) {
                        if (is_numeric($w) || !empty($w)) { // we suppress '', but allow 0 as value (see #193)
                            $vars .= '/' . $k . '[' . $k2 . ']/' . $w; // &$k[$k2]=$w
                        }
                    }
                } elseif (is_numeric($v) || !empty($v)) { // we suppress '', but allow 0 as value (see #193)
                    $vars .= "/$k/$v"; // &$k=$v
                }
            }
            $vars = substr($vars, 1);
            if ((!empty($func) && $func != 'main') || $vars != '') {
                $func = "/$func/";
            } else {
                $func = '/';
            }
            $url = $modname . $func . $vars;
            $url = rtrim($url, '/');
        }

        if ($shorturlsdefaultmodule == $modinfo['name'] && $url != "{$modinfo['url']}/") {
            $url = str_replace("{$modinfo['url']}/", '', $url);
        }
        if (isset($theme)) {
            $url = rawurlencode($theme) . '/' . $url;
        }

        // add language param to short url
        if (ZLanguage::isRequiredLangParam() || $forcelang) {
            $url = "$language/" . $url;
        }
        if (!$shorturlsstripentrypoint) {
            $url = "$entrypoint/$url" . (!empty($query) ? '?' . $query : '');
        } else {
            $url = "$url" . (!empty($query) ? '?' . $query : '');
        }

    } else {
        // Regular URLs

        // The arguments
        if ($modinfo['type'] == 1) {
            if ($type == 'admin') {
                $urlargs[] = "name=$modname";
                $entrypoint = 'admin.php';
            } else {
                $urlargs[] = "name=$modname";
            }
        } else {
            $urlargs = "module=$modname";
            if ((!empty($type)) && ($type != 'user')) {
                $urlargs .= "&type=$type";
            }

            if ((!empty($func)) && ($func != 'main')) {
                $urlargs .= "&func=$func";
            }
        }

        $url = "$entrypoint?$urlargs";

        // <rabbitt> added array check on args
        // April 11, 2003
        if (!is_array($args)) {
            return false;
        } else {
            foreach ($args as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $l => $w) {
                        if (is_numeric($w) || !empty($w)) { // we suppress '', but allow 0 as value (see #193)
                            $url .= "&$k" . "[$l]=$w";
                        }
                    }
                } elseif (is_numeric($v) || !empty($v)) { // we suppress '', but allow 0 as value (see #193)
                    $url .= "&$k=$v";
                }
            }
        }

        // add lang param to URL
        if (ZLanguage::isRequiredLangParam() || $forcelang) {
            $url .= "&lang=$language";
        }
    }

    if (isset($fragment)) {
        $url .= '#' . $fragment;
    }

    return $siteRoot . $url;
}

/**
 * see if a module is available
 * @author Jim McDonald <jim@mcdee.net>
 * @link http://www.mcdee.net
 * @param 'modname' the name of the module
 * @return bool true if the module is available, false if not
 */
function pnModAvailable($modname = null, $force = false)
{
    // define input, all numbers and booleans to strings
    $modname = (isset($modname) ? strtolower((string) $modname) : '');

    // validate
    if (!pnVarValidate($modname, 'mod')) {
        return false;
    }

    static $modstate = array();

    if (!isset($modstate[$modname]) || $force == true) {
        $modinfo = pnModGetInfo(pnModGetIDFromName($modname));
        $modstate[$modname] = $modinfo['state'];
    }

    if ((isset($modstate[$modname]) && $modstate[$modname] == PNMODULE_STATE_ACTIVE) || (preg_match('/(modules|admin|theme|block|groups|permissions|users)/i', $modname) && (isset($modstate[$modname]) && ($modstate[$modname] == PNMODULE_STATE_UPGRADED || $modstate[$modname] == PNMODULE_STATE_INACTIVE)))) {
        return true;
    }

    return false;
}

/**
 * get name of current top-level module
 * @author Jim McDonald <jim@mcdee.net>
 * @link http://www.mcdee.net
 * @return string the name of the current top-level module, false if not in a module
 */
function pnModGetName()
{
    static $module;

    if (!isset($module)) {
        $type = FormUtil::getPassedValue('type', null, 'GETPOST');
        $module = FormUtil::getPassedValue('module', null, 'GETPOST');
        $name = FormUtil::getPassedValue('name', null, 'GETPOST');

        if (empty($name) && empty($module)) {
            $module = pnConfigGetVar('startpage');
        } elseif (empty($module) && !empty($name)) {
            $module = $name;
        }

        // the parameters may provide the module alias so lets get
        // the real name from the db
        $modinfo = pnModGetInfo(pnModGetIDFromName($module));
        if (isset($modinfo['name'])) {
            $module = $modinfo['name'];
            if ($type != 'init' && !pnModAvailable($module)) {
                // anything from user.php is the user module
                // not really - of course but it'll do..... [markwest]
                if (stristr($_SERVER['PHP_SELF'], 'user.php')) {
                    $module = 'Users';
                } else {
                    $module = pnConfigGetVar('startpage');
                }
            }
        }
    }

    return $module;
}

/**
 * register a hook function
 * @author Jim McDonald <jim@mcdee.net>
 * @link http://www.mcdee.net
 * @param 'hookobject' the hook object
 * @param 'hookaction' the hook action
 * @param 'hookarea' the area of the hook (either 'GUI' or 'API')
 * @param 'hookmodule' name of the hook module
 * @param 'hooktype' name of the hook type
 * @param 'hookfunc' name of the hook function
 * @return bool true if successful, false otherwise
 */
function pnModRegisterHook($hookobject, $hookaction, $hookarea, $hookmodule, $hooktype, $hookfunc)
{
    // define input, all numbers and booleans to strings
    $hookmodule = isset($hookmodule) ? ((string) $hookmodule) : '';

    // validate
    if (!pnVarValidate($hookmodule, 'mod')) {
        return false;
    }

    // Insert hook
    $obj = array('object' => $hookobject, 'action' => $hookaction, 'tarea' => $hookarea, 'tmodule' => $hookmodule, 'ttype' => $hooktype, 'tfunc' => $hookfunc);
    return (bool) DBUtil::insertObject($obj, 'hooks', 'id');
}

/**
 * unregister a hook function
 * @author Jim McDonald <jim@mcdee.net>
 * @link http://www.mcdee.net
 * @param 'hookobject' the hook object
 * @param 'hookaction' the hook action
 * @param 'hookarea' the area of the hook (either 'GUI' or 'API')
 * @param 'hookmodule' name of the hook module
 * @param 'hooktype' name of the hook type
 * @param 'hookfunc' name of the hook function
 * @return bool true if successful, false otherwise
 */
function pnModUnregisterHook($hookobject, $hookaction, $hookarea, $hookmodule, $hooktype, $hookfunc)
{
    // define input, all numbers and booleans to strings
    $hookmodule = isset($hookmodule) ? ((string) $hookmodule) : '';

    // validate
    if (!pnVarValidate($hookmodule, 'mod')) {
        return false;
    }

    // Get database info
    $pntable = pnDBGetTables();
    $hookscolumn = $pntable['hooks_column'];
    // Remove hook
    $where = "WHERE $hookscolumn[object] = '" . DataUtil::formatForStore($hookobject) . "'
              AND $hookscolumn[action] = '" . DataUtil::formatForStore($hookaction) . "'
              AND $hookscolumn[tarea] = '" . DataUtil::formatForStore($hookarea) . "'
              AND $hookscolumn[tmodule] = '" . DataUtil::formatForStore($hookmodule) . "'
              AND $hookscolumn[ttype] = '" . DataUtil::formatForStore($hooktype) . "'
              AND $hookscolumn[tfunc] = '" . DataUtil::formatForStore($hookfunc) . "'";

    return (bool) DBUtil::deleteWhere('hooks', $where);
}

/**
 * carry out hook operations for module
 * @author Jim McDonald <jim@mcdee.net>
 * @link http://www.mcdee.net
 * @param 'hookobject' the object the hook is called for - one of 'item', 'category' or 'module'
 * @param 'hookaction' the action the hook is called for - one of 'new', 'create', 'modify', 'update', 'delete', 'transform', 'display', 'modifyconfig', 'updateconfig'
 * @param 'hookid' the id of the object the hook is called for (module-specific)
 * @param 'extrainfo' extra information for the hook, dependent on hookaction
 * @param 'implode' implode collapses all display hooks into a single string - default to true for compatability with .7x
 * @return mixed string output from GUI hooks, extrainfo array for API hooks
 */
function pnModCallHooks($hookobject, $hookaction, $hookid, $extrainfo = array(), $implode = true)
{
    static $modulehooks;

    if (!isset($hookaction)) {
        return null;
    }

    if (isset($extrainfo['module']) && (pnModAvailable($extrainfo['module']) || strtolower($hookobject) == 'module' || strtolower($extrainfo['module']) == 'zikula')) {
        $modname = $extrainfo['module'];
    } else {
        $modname = pnModGetName();
    }

    $render = pnRender::getInstance();
    $domain = $render->renderDomain;

    $lModname = strtolower($modname);
    if (!isset($modulehooks[$lModname])) {
        // Get database info
        $pntable = pnDBGetTables();
        $hookscolumn = $pntable['hooks_column'];
        $where = "WHERE $hookscolumn[smodule] = '" . DataUtil::formatForStore($modname) . "'";
        $orderby = "$hookscolumn[sequence] ASC";
        $hooks = DBUtil::selectObjectArray('hooks', $where, $orderby);
        $modulehooks[$lModname] = $hooks;
    }

    $gui = false;
    $output = array();
    // Call each hook
    foreach ($modulehooks[$lModname] as $modulehook) {
        if (!isset($extrainfo['tmodule']) || (isset($extrainfo['tmodule']) && $extrainfo['tmodule'] == $modulehook['tmodule'])) {
            if (($modulehook['action'] == $hookaction) && ($modulehook['object'] == $hookobject)) {
                if (isset($modulehook['tarea']) && $modulehook['tarea'] == 'GUI') {
                    $gui = true;
                    if (pnModAvailable($modulehook['tmodule'], $modulehook['ttype']) && pnModLoad($modulehook['tmodule'], $modulehook['ttype'])) {
                        $output[$modulehook['tmodule']] = pnModFunc($modulehook['tmodule'], $modulehook['ttype'], $modulehook['tfunc'], array('objectid' => $hookid, 'extrainfo' => $extrainfo));
                    }
                } else {
                    if (isset($modulehook['tmodule']) && pnModAvailable($modulehook['tmodule'], $modulehook['ttype']) && pnModAPILoad($modulehook['tmodule'], $modulehook['ttype'])) {
                        $extrainfo = pnModAPIFunc($modulehook['tmodule'], $modulehook['ttype'], $modulehook['tfunc'], array('objectid' => $hookid, 'extrainfo' => $extrainfo));
                    }
                }
            }
        }
    }

    // check what type of information we need to return
    $hookaction = strtolower($hookaction);
    if ($gui || $hookaction == 'display' || $hookaction == 'new' || $hookaction == 'modify' || $hookaction == 'modifyconfig') {
        if ($implode || empty($output)) {
            $output = implode("\n", $output);
        }

        $render->renderDomain = $domain;

        return $output;
    }

    $render->renderDomain = $domain;
    return $extrainfo;
}

/**
 * Determine if a module is hooked by another module
 * @author Mark West (mark@markwest.me.uk)
 * @link http://www.markwest.me.uk
 * @param 'tmodule' the target module
 * @param 'smodule' the source module - default the current top most module
 * @return bool true if the current module is hooked by the target module, false otherwise
 */
function pnModIsHooked($tmodule, $smodule)
{
    static $hooked = array();

    if (isset($hooked[$tmodule][$smodule])) {
        return $hooked[$tmodule][$smodule];
    }

    // define input, all numbers and booleans to strings
    $tmodule = isset($tmodule) ? ((string) $tmodule) : '';
    $smodule = isset($smodule) ? ((string) $smodule) : '';

    // validate
    if (!pnVarValidate($tmodule, 'mod') || !pnVarValidate($smodule, 'mod')) {
        return false;
    }

    // Get database info
    $pntable = pnDBGetTables();
    $hookscolumn = $pntable['hooks_column'];

    // Get applicable hooks
    $where = "WHERE $hookscolumn[smodule] = '" . DataUtil::formatForStore($smodule) . "'
              AND $hookscolumn[tmodule] = '" . DataUtil::formatForStore($tmodule) . "'";

    $hooked[$tmodule][$smodule] = $numitems = DBUtil::selectObjectCount('hooks', $where);
    $hooked[$tmodule][$smodule] = ($numitems > 0);

    return $hooked[$tmodule][$smodule];
}

/**
 * pnModLangLoad
 * loads the language files for a module
 *
 * @author Mark West
 * @link http://www.markwest.me.uk
 * @param modname - name of the module
 * @param type - type of the language file to load e.g. user, admin
 * @param api - load api lang file or gui lang file
 */
function pnModLangLoad($modname, $type = 'user', $api = false)
{
    // define input, all numbers and booleans to strings
    $modname = isset($modname) ? ((string) $modname) : '';

    // validate
    if (!pnVarValidate($modname, 'mod')) {
        return false;
    }

    // get the module info
    $modinfo = pnModGetInfo(pnModGetIDFromName($modname));
    if (!$modinfo) {
        return false;
    }

    if ($modinfo['i18n']) {
        return ZLanguage::bindModuleDomain($modname);
    }

    // create variables for the OS preped version of the directory
    $directory = $modinfo['directory'];
    $osapi = $api ? 'api' : '';

    $path = ($modinfo['type'] == '3' ? 'system' : 'modules');

    $currentlang = ZLanguage::getLanguageCodeLegacy();
    $defaultlang = ZLanguage::lookupLegacyCode(pnConfigGetVar('language_i18n'));
    $moddir = "$path/$directory/pnlang";
    $osfile = $type . $osapi . '.php';

    $files = array();

    // This directory is for easy of use when developing language packs.
    // See http://community.zikula.org/index.php?module=Wiki&tag=LanguagePack
    if (isset($GLOBALS['PNConfig']['System']['development']) && $GLOBALS['PNConfig']['System']['development']) {
        $files[] = "config/languages/$currentlang/$moddir/$currentlang/$osfile";
    }

    $files[] = "config/languages/$currentlang/$directory/$osfile";
    $files[] = "$moddir/$currentlang/$osfile";
    $files[] = "config/languages/$defaultlang/$directory/$osfile";
    $files[] = "$moddir/$defaultlang/$osfile";

    return Loader::loadOneFile($files);
}

/**
 * Get the base directory for a module
 *
 * Example: If the webroot is located at
 * /var/www/html
 * and the module name is Template and is found
 * in the modules directory then this function
 * would return /var/www/html/modules/Template
 *
 * If the Template module was located in the system
 * directory then this function would return
 * /var/www/html/system/Template
 *
 * This allows you to say:
 * include(pnModGetBaseDir() . '/includes/private_functions.php');
 *
 * @author Chris Miller
 * @param   $modname - name of module to that you want the
 *                     base directory of.
 * @return  string - the path from the root directory to the
 *                   specified module.
 */
function pnModGetBaseDir($modname = '')
{
    if (empty($modname)) {
        $modname = pnModGetName();
    }

    $path = pnGetBaseURI();
    $directory = 'system/' . $modname;
    if ($path != '') {
        $path .= '/';
    }

    $url = $path . $directory;
    if (!is_dir($url)) {
        $directory = 'modules/' . $modname;
        $url = $path . $directory;
    }

    return $url;
}

/**
 * gets the modules table
 *
 * small wrapper function to avoid duplicate sql
 * @access private
 * @return array modules table
 */
function pnModGetModsTable()
{
    static $modstable;

    if (!isset($modstable) || defined('_PNINSTALLVER')) {
        $modstable = DBUtil::selectObjectArray('modules', '', '', -1, -1, 'id');
        foreach ($modstable as $mid => $module)
        {
           if ($module['type'] == 2) {
               $modstable[$mid]['i18n'] = (is_dir("modules/$module[name]/locale") ? 1 : 0);
           } else {
               $modstable[$mid]['i18n'] = 0;
           }
           if (!isset($module['url']) || empty($module['url'])) {
               $modstable[$mid]['url'] = $module['displayname'];
           }
        }
    }

    return $modstable;
}
