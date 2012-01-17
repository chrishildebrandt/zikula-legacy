<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2001, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: pnadminapi.php 28060 2010-01-07 08:31:22Z drak $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @author Jim McDonald
 * @package Zikula_System_Modules
 * @subpackage Modules
 */

/**
 * update module information
 * @param int $args ['id'] the id number of the module
 * @return array associative array
 */
function modules_adminapi_modify($args)
{
    return DBUtil::selectObjectByID('modules', $args['id'], 'id');
}

/**
 * update module information
 * @author Jim McDonald
 * @param int $args ['id'] the id number of the module to update
 * @param string $args ['displayname'] the new display name of the module
 * @param string $args ['description'] the new description of the module
 * @return bool true on success, false on failure
 */
function modules_adminapi_update($args)
{
    // Argument check
    if (!isset($args['id']) || !is_numeric($args['id']) ||
        !isset($args['displayname']) ||
        !isset($args['description']) ||
        !isset($args['url'])) {
        return LogUtil::registerArgsError();
    }
    // Security check
    if (!SecurityUtil::checkPermission('Modules::', "::$args[id]", ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // check for duplicate display names
    // get the module info for the module being updated
    $moduleinforeal = pnModGetInfo($args['id']);
    // validate URL
    $moduleinfourl = pnModGetInfo(pnModGetIDFromName($args['url']));
    // If the two real module name don't match then the new display name can't be used
    if ($moduleinfourl && $moduleinfourl['name'] != $moduleinforeal['name']) {
        return LogUtil::registerError(__('Error! Could not save the module URL information. A duplicate module URL was detected.'));
    }

    if (empty($args['url'])) {
        return LogUtil::registerError(__('Error! Module URL is a required field, please enter a unique name.'));
    }

    if (empty($args['displayname'])) {
        return LogUtil::registerError(__('Error! Module URL is a required field, please enter a unique name.'));
    }

    // Rename operation
    $obj = array('id'          => $args['id'],
                 'displayname' => $args['displayname'],
                 'description' => $args['description'],
                 'url'         => $args['url']);
    if (!DBUtil::updateObject($obj, 'modules')) {
        return LogUtil::registerError(__('Error! Could not save your changes.'));
    }
    return true;
}

/**
 * update module hook information
 * @author Jim McDonald
 * @param int $args ['id'] the id number of the module to update
 * @return bool true on success, false on failure
 */
function modules_adminapi_updatehooks($args)
{
    // Argument check
    if (!isset($args['id']) || !is_numeric($args['id'])) {
        return LogUtil::registerArgsError();
    }
    // Security check
    if (!SecurityUtil::checkPermission('Modules::', "::$args[id]", ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }
    // Rename operation
    $pntable = pnDBGetTables();

    $modulescolumn = $pntable['modules_column'];
    $hookscolumn = $pntable['hooks_column'];

    // Hooks
    // Get module name
    $modinfo = pnModGetInfo($args['id']);

    // Delete hook regardless
    $where = "WHERE $hookscolumn[smodule] = '" . DataUtil::formatForStore($modinfo['name']) . "'
              AND $hookscolumn[tmodule] <> ''";
    DBUtil::deleteWhere('hooks', $where);

    $where = "WHERE $hookscolumn[smodule] = ''";
    $orderBy = "ORDER BY $hookscolumn[tmodule],
                         $hookscolumn[smodule] DESC";
    $objArray = DBUtil::selectObjectArray('hooks', $where, $orderBy);
    if ($objArray === false) {
        return false;
    }

    $ak = array_keys($objArray);
    foreach($ak as $v) {
        // Get selected value of hook
        $hookvalue = FormUtil::getPassedValue('hooks_'.$objArray[$v]['tmodule']);
        // See if this is checked and isn't in the database
        if (isset($hookvalue) && empty($objArray[$v]['smodule'])) {
            $objArray[$v]['smodule'] = $modinfo['name'];
            if (DBUtil::insertObject($objArray[$v], 'hooks') === false) {
                return false;
            }
        }
    }
    return true;
}

/**
 * update module hook information, extended version
 * @author Jim McDonald
 * @author Frank Schummertz
 * @param int $args ['id'] the id number of the module to update
 * @return bool true on success, false on failure
 */
function modules_adminapi_extendedupdatehooks($args)
{
    // Argument check
    if (!isset($args['id']) || !is_numeric($args['id'])) {
        return LogUtil::registerArgsError();
    }
    // Security check
    if (!SecurityUtil::checkPermission('Modules::', "::$args[id]", ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }
    // Rename operation
    $pntable = pnDBGetTables();

    $modulescolumn = $pntable['modules_column'];
    $hookscolumn = $pntable['hooks_column'];

    // Hooks
    // Get module information
    $modinfo = pnModGetInfo($args['id']);

    // Delete hook regardless
    $where = "WHERE $hookscolumn[smodule] = '" . DataUtil::formatForStore($modinfo['name']) . "'
              AND $hookscolumn[tmodule] <> ''";
    DBUtil::deleteWhere('hooks', $where);

    $where = "WHERE $hookscolumn[smodule] = ''";
    $orderBy = "ORDER BY $hookscolumn[tmodule],
                         $hookscolumn[smodule] DESC";
    // read the hooks themselves - the entries in the database that are not connected
    // with a module
    $objArray = DBUtil::selectObjectArray('hooks', $where, $orderBy);
    if ($objArray === false) {
        return false;
    }

    // sort the hooks by action
    $grouped_hooks = array();
    foreach($objArray as $hookobject) {
        if (!array_key_exists($hookobject['action'], $grouped_hooks)) {
            $grouped_hooks[$hookobject['action']] = array();
        }
        $grouped_hooks[$hookobject['action']][$hookobject['tmodule']] = $hookobject;
    }

    // get hookvalues. This is an array of hookactions with each one
    // containing an array of hooks where the checkbox has been set
    // in short: hookvalues only contains the hooks the that the user
    // want s to activate for the selected module. As a side effect
    // the hooks are sorted :-)
    $hookvalues = FormUtil::getPassedValue('hooks');

    // cycle throught the hookvalues
    foreach($hookvalues as $action => $actionarray) {
        // reset the sequence
        $sequence = 1;
        foreach($actionarray as $smodule => $value) {
            $hookobject = $grouped_hooks[$action][$smodule];
            $hookobject['sequence'] = $sequence;
            $hookobject['smodule'] = $modinfo['name'];
            if (DBUtil::insertObject($hookobject, 'hooks') === false) {
                return false;
            }
            $sequence++;
        }
    }
    return true;
}

/**
 * obtain list of modules
 * @author Jim McDonald
 * @return array associative array of known modules
 */
function modules_adminapi_list($args)
{
    // Security check
    if (!defined('_PNINSTALLVER')) {
        if (!SecurityUtil::checkPermission('Modules::', '::', ACCESS_ADMIN)) {
            return LogUtil::registerPermissionError();
        }
    }

    // Optional arguments.
    $startnum = (empty($args['startnum']) || $args['startnum'] < 0)  ?  1 : (int)$args['startnum'];
    $numitems = (empty($args['numitems']) || $args['numitems'] < 0)  ? -1 : (int)$args['numitems'];
    if($GLOBALS['PNConfig']['Multisites']['multi'] == 1){
        $state    = (empty($args['state']) || $args['state']<-1 || $args['state']>6)  ? 0 : (int)$args['state'];
    } else {
        $state    = (empty($args['state']) || $args['state']<-1 || $args['state']>5)  ? 0 : (int)$args['state'];
    }
    $type    = (empty($args['type']) || $args['type']<0 || $args['type']>3)  ? 0 : (int)$args['type'];
    $sort    = empty($args['sort']) ? null : (string)$args['sort'];

    SessionUtil::setVar('state', $state);
    SessionUtil::setVar('sort', $sort);

    // Obtain information
    $pntable = pnDBGetTables();
    $modulescolumn = $pntable['modules_column'];

    // filter my first letter of module
    if (isset($args['letter']) && !empty($args['letter'])) {
        $where[] = "$modulescolumn[name] LIKE '" . DataUtil::formatForStore($args['letter']) . "%' OR "
                 . "$modulescolumn[name] LIKE '" . DataUtil::formatForStore(strtolower($args['letter'])) . "%'";
        // why reset startnum here? This prevents moving to a second page within
        // a lettered filter - markwest
        //$startnum = 1;
    }

    if ($type != 0) {
        $where[] = "$modulescolumn[type] = '" . (int)DataUtil::formatForStore($type) . "'";
    }

    // filter by module state
    switch ($state) {
        case PNMODULE_STATE_UNINITIALISED:
        case PNMODULE_STATE_INACTIVE:
        case PNMODULE_STATE_ACTIVE:
        case PNMODULE_STATE_MISSING:
        case PNMODULE_STATE_UPGRADED:
        case PNMODULE_STATE_NOTALLOWED:
        case PNMODULE_STATE_INVALID:
            $where[] = "$modulescolumn[state] = '" . DataUtil::formatForStore($state) . "'";
            break;
    }

    // generate where clause
    $wheresql = '';
    if (isset($where) && is_array($where)) {
        $wheresql = 'WHERE ' . implode(' AND ', $where);
    }

    if ($sort == 'displayname') {
        $orderBy = "ORDER BY UPPER($modulescolumn[displayname])";
    } else {
        $orderBy = "ORDER BY UPPER($modulescolumn[name])";
    }

    $objArray = DBUtil::selectObjectArray('modules', $wheresql, $orderBy, $startnum-1, $numitems);

    if ($objArray === false) {
        return LogUtil::registerError(__('Error! Could not load data.'));
    }

    return $objArray;
}

/**
 * set the state of a module
 * @author Jim McDonald
 * @param int $args ['id'] the module id
 * @param int $args ['state'] the state
 * @return bool true if successful, false otherwise
 */
function modules_adminapi_setstate($args)
{
    // Argument check
    if (!isset($args['id']) || !is_numeric($args['id']) ||
        !isset($args['state'])) {
        return LogUtil::registerArgsError();
    }

    // Security check
    if (!defined('_PNINSTALLVER')) {
        if (!SecurityUtil::checkPermission('Modules::', '::', ACCESS_EDIT)) {
            return LogUtil::registerPermissionError();
        }
    }

    // Set state
    $result = DBUtil::selectObjectByID('modules', $args['id'], 'id', null, null, false);
    if (empty($result)) {
        return false;
    }

    if ($result === false) {
        return LogUtil::registerPermissionError();
    }

    list($name, $directory, $oldstate) = array($result['name'],
                                               $result['directory'],
                                               $result['state']);

    $modinfo = pnModGetInfo($args['id']);
    // Check valid state transition
    switch ($args['state']) {
        case PNMODULE_STATE_UNINITIALISED:
            if($GLOBALS['PNConfig']['Multisites']['multi'] == 1){
                if(!SecurityUtil::checkPermission('Modules::', '::', ACCESS_ADMIN)){
                    return LogUtil::registerError(__('Error! Invalid module state transition.'));
                }
            } else {
                return LogUtil::registerError(__('Error! Invalid module state transition.'));
            }
            break;
        case PNMODULE_STATE_INACTIVE:
            break;
        case PNMODULE_STATE_ACTIVE:
            // allow new style modules to transition ditectly from upgraded to active state
            if ((($oldstate == PNMODULE_STATE_UNINITIALISED) ||
                 ($oldstate == PNMODULE_STATE_MISSING) ||
                 ($oldstate == PNMODULE_STATE_UPGRADED)) && $modinfo['type'] == 1) {
                return LogUtil::registerError(__('Error! Invalid module state transition.'));
            }
            break;
        case PNMODULE_STATE_MISSING:
            break;
        case PNMODULE_STATE_UPGRADED:
            if ($oldstate == PNMODULE_STATE_UNINITIALISED) {
                return LogUtil::registerError(__('Error! Invalid module state transition.'));
            }
            break;
    }

    $obj = array('id'    => $args['id'],
                 'state' => $args['state']);
    if (!DBUtil::updateObject($obj, 'modules')) {
        return false;
    }

    // State change, so update the pnModAvailable-info for this module.
    pnModAvailable($modinfo['name'], true);

    return true;
}

/**
 * remove a module
 * @author Jim McDonald
 * @param int $args ['id'] the id of the module
 * @param bool $args ['removedependents'] remove any modules dependent on this module (default: false)
 * @param int $args['interactive_remove'] true if in interactive upgrade mode, otherwise false
 * @return bool true on success, false on failure
 */
function modules_adminapi_remove($args)
{
    // Argument check
    if (!isset($args['id']) || !is_numeric($args['id'])) {
        return LogUtil::registerArgsError();
    }

    if (!isset($args['removedependents']) || !is_bool($args['removedependents'])) {
        $removedependents = false;
    } else {
        $removedependents = true;
    }

    // Security check
    if (!SecurityUtil::checkPermission('Modules::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // Get module information
    $modinfo = pnModGetInfo($args['id']);
    if (empty($modinfo)) {
        return LogUtil::registerError(__('Error! No such module ID exists.'));
    }

    $osdir = DataUtil::formatForOS($modinfo['directory']);
    if ($modinfo['type'] == 2) {
        $dir = "modules/$osdir/locale";
        if (is_dir($dir)) {
            ZLanguage::bindModuleDomain($modinfo['name']);
        }
    }

    // call any module delete hooks
    pnModCallHooks('module', 'remove', $modinfo['name'], array('module' => $modinfo['name']));

    // Get module database info
    pnModDBInfoLoad($modinfo['name'], $modinfo['directory']);

    // Module deletion function. Only execute if the module hasn't been initialised.
    if ($modinfo['state'] != PNMODULE_STATE_UNINITIALISED) {
        $modpath = ($modinfo['type'] == 3) ? 'system' : 'modules';
        if (file_exists($file = "$modpath/$osdir/pninit.php")) {
            if (!Loader::includeOnce($file)) {
                LogUtil::registerError(__f("Error! Could not load a required file: '%s'.", $file));
            }
       }

        // load the module info language files
        $currentlang = DataUtil::formatForOS(ZLanguage::getLanguageCodeLegacy());
        $langfiles = array("$modpath/$osdir/pnlang/$currentlang/init.php",
                           "$modpath/$osdir/lang/$currentlang/init.php",
                           "$modpath/$osdir/lang/$currentlang/version.php",
                           "$modpath/$osdir/pnlang/$currentlang/version.php");
        foreach ($langfiles as $langfile) {
            if (file_exists($langfile)) {
                if (!Loader::includeOnce($langfile)) {
                    LogUtil::registerError(__f("Error! Could not load a required file: '%s'.", $langfile));
                }
            }
        }

        // perform the actual deletion of the module
        $func = $modinfo['name'] . '_delete';
        $interactive_func = $modinfo['name'] . '_init_interactivedelete';

        // allow bypass of interactive removal during a new installation only.
        if (defined('_PNINSTALLVER') && function_exists($interactive_func) && !function_exists($func)) {
            return; // return void here
        }

        if ((isset($args['interactive_remove']) && $args['interactive_remove'] == false) && function_exists($interactive_func)) {
            SessionUtil::setVar('interactive_remove', true);
            return $interactive_func();
        }

        if (function_exists($func)) {
            if ($func() != true) {
                return false;
            }
        }
    }

    // Remove variables and module
    // Delete any module variables that the module cleanup function might
    // have missed
    DBUtil::deleteObjectByID('module_vars', $modinfo['name'], 'modname');

    // clean up any hooks activated for this module
    DBUtil::deleteObjectByID('hooks', $modinfo['name'], 'smodule');

    // remove the entry from the modules table
    if($GLOBALS['PNConfig']['Multisites']['multi'] == 1){
        // who can access to the mainSite can delete the modules in any other site
        $canDelete = ($GLOBALS['PNConfig']['Multisites']['mainSiteURL'] == FormUtil::getPassedValue('siteDNS', null, 'GET')) ? 1 : 0;
        //delete the module infomation only if it is not allowed, missign or invalid
        if($canDelete == 1 || $modinfo['state'] == PNMODULE_STATE_NOTALLOWED || $modinfo['state'] == PNMODULE_STATE_MISSING || $modinfo['state'] == PNMODULE_STATE_INVALID){
            // remove the entry from the modules table
            DBUtil::deleteObjectByID('modules', $args['id'], 'id');
        } else {
            //set state as uninnitialised
            pnModAPIFunc('modules','admin','setstate', array('id' => $args['id'], 'state' => PNMODULE_STATE_UNINITIALISED));
        }
    } else {
        DBUtil::deleteObjectByID('modules', $args['id'], 'id');
    }
    return true;
}

/**
 * scan the file system for modules
 *
 * This function scans the file system for modules and returns an
 * array with all (potential) modules found.
 * This information is used to regenerate the module list.
 *
 * @author Jim McDonald
 * @author J�rg Napp
 * @return array Array of modules found in the file system
 */
function modules_adminapi_getfilemodules()
{
    // Security check
    if (!defined('_PNINSTALLVER')) {
        if (!SecurityUtil::checkPermission('Modules::', '::', ACCESS_ADMIN)) {
            return LogUtil::registerPermissionError();
        }
    }

    // Get all modules on filesystem
    $filemodules = array();

    // set the paths to search
    $rootdirs = array('system' => 3, 'modules' => 2);

    foreach ($rootdirs as $rootdir => $moduletype)
    {
        if (is_dir($rootdir)) {
            $dirs = FileUtil::getFiles($rootdir, false, true, null, 'd');
            foreach ($dirs as $dir)
            {
                $name = preg_replace('/^NS-/', '', $dir);
                // we'll use $modversion['name'] rather than the directory name here - markwest
                //$displayname = preg_replace('/_/', ' ', $name);

                // Work out if admin-capable
                if (file_exists("$rootdir/$dir/pnadmin.php") || is_dir("$rootdir/$dir/pnadmin")) {
                    $adminCapable = PNYES;
                    $modtype = $moduletype;
                } elseif (file_exists("$rootdir/$dir/admin.php")) {
                    $adminCapable = PNYES;
                    $modtype = 1;
                } else {
                    $adminCapable = PNNO;
                }

                // Work out if user-capable
                if (file_exists("$rootdir/$dir/pnuser.php")  || is_dir("$rootdir/$dir/pnuser")) {
                    $userCapable = PNYES;
                    if (!isset($modtype)) {
                        $modtype = $moduletype;
                    }
                } elseif (file_exists("$rootdir/$dir/index.php")) {
                    $userCapable = PNYES;
                    if (!isset($modtype)) {
                        $modtype = 1;
                    }
                } else {
                    $userCapable = PNNO;
                }

                if (empty($modtype)) {
                    $modtype = $moduletype;
                }

                // include language file for non-gettext modules
                $defaultlang = ZLanguage::lookupLegacyCode(pnConfigGetVar('language_i18n'));
                $currentlang = DataUtil::formatForOS(ZLanguage::getLanguageCodeLegacy());
                $possiblelanguagefiles = array("$rootdir/$dir/pnlang/$currentlang/version.php",
                                               "$rootdir/$dir/lang/$currentlang/version.php",
                                               "$rootdir/$dir/pnlang/$defaultlang/version.php",
                                               "$rootdir/$dir/lang/$defaultlang/version.php",
                                               "$rootdir/$dir/pnlang/eng/version.php",
                                               "$rootdir/$dir/lang/eng/version.php");

                // load the first file which can be included properly
                Loader::loadOneFile($possiblelanguagefiles);

                // loads the gettext domain if needed
                if (is_dir("modules/$dir/locale")) {
                    ZLanguage::bindModuleDomain($dir);
                }

                if (file_exists($file = "$rootdir/$dir/Version.php")) {
                    if (!include($file)) {
                        LogUtil::registerError(__f("Error! Could not load a required file: '%s'.", $file));
                    }
                }

                if (file_exists($file = "$rootdir/$dir/pnversion.php")) {
                    if (!include($file)) {
                        LogUtil::registerError(__f("Error! Could not load a required file: '%s'.", $file));
                    }
                }

                // Get the module version
                $modversion['version'] = '0';
                $modversion['description'] = '';
                $modversion['name'] = preg_replace('/_/', ' ', $name);

                if (file_exists($file ="$rootdir/$dir/Version.php")) {
                    if (!include($file)) {
                        LogUtil::registerError(__f("Error! Could not load a required file: '%s'.", $file));
                    }
                }

                if (file_exists($file = "$rootdir/$dir/pnversion.php")) {
                    if (!include($file)) {
                        LogUtil::registerError(__f("Error! Could not load a required file: '%s'.", $file));
                    }
                }

                $version = $modversion['version'];
                $description = $modversion['description'];

//                if (isset($modversion['name']) && !empty($modversion['name'])) {
//                    $name = $modversion['name'];
//                }

                if (isset($modversion['displayname']) && !empty($modversion['displayname'])) {
                    $displayname = $modversion['displayname'];
                } else {
                    $displayname = $modversion['name'];
                }

                $profileCapable = (isset($modversion['profile']) && $modversion['profile']);
                $messageCapable = (isset($modversion['message']) && $modversion['message']);

                // get the correct regid
                if (isset($modversion['id']) && !empty($modversion['id'])) {
                    $regid = (int)$modversion['id'];
                } else {
                    $regid = 0;
                }

                // bc for urls
                if (isset($modversion['url']) && !empty($modversion['url'])) {
                    $url = $modversion['url'];
                } else {
                    $url = $displayname;
                }

                if (isset($modversion['securityschema']) && is_array($modversion['securityschema'])) {
                    $securityschema = serialize($modversion['securityschema']);
                } else {
                    $securityschema = serialize(array());
                }

                if (isset($modversion['moddependencies']) && is_array($modversion['moddependencies'])) {
                    $moddependencies = serialize($modversion['moddependencies']);
                } else {
                    $moddependencies = serialize(array());
                }

                $filemodules["$rootdir/$dir"] = array(
                                           'directory'       => $dir,
                                           'name'            => $name,
                                           'oldnames'        => (isset($modversion['oldnames']) ? $modversion['oldnames'] : array()),
                                           'type'            => $modtype,
                                           'displayname'     => $displayname,
                                           'url'             => $url,
                                           'regid'           => $regid,
                                           'version'         => $version,
                                           'description'     => $description,
                                           'admin_capable'   => $adminCapable,
                                           'user_capable'    => $userCapable,
                                           'profile_capable' => $profileCapable,
                                           'message_capable' => $messageCapable,
                                           'official'        => (isset($modversion['official']) ? $modversion['official'] : 0),
                                           'author'          => (isset($modversion['author']) ? $modversion['author'] : ''),
                                           'contact'         => (isset($modversion['contact']) ? $modversion['contact'] : ''),
                                           'credits'         => (isset($modversion['credits']) ? $modversion['credits'] : ''),
                                           'help'            => (isset($modversion['help']) ? $modversion['help'] : ''),
                                           'changelog'       => (isset($modversion['changelog']) ? $modversion['changelog'] : ''),
                                           'license'         => (isset($modversion['license']) ? $modversion['license'] : ''),
                                           'securityschema'  => $securityschema,
                                           'moddependencies' => $moddependencies
                                          );

                // important: unset modversion and modtype, otherwise the
                // following modules will have some values not defined in
                // the next pnversion.php files to be read
                unset($modversion);
                unset($modtype);
            }
        }
    }

    return $filemodules;
}

/**
 * regenerate modules list
 * @author Jim McDonald
 * @param array args['filemodules'] array of modules in the filesystem, as returned by modules_adminapi_getfilemodules defaults to modules_adminapi_getfilemodules()
 * @return bool true on success, false on failure
 * @see modules_adminapi_getfilemodules()
 */
function modules_adminapi_regenerate($args)
{
    // Security check
    if (!defined('_PNINSTALLVER')) {
        if (!SecurityUtil::checkPermission('Modules::', '::', ACCESS_ADMIN)) {
            return LogUtil::registerPermissionError();
        }
    }

    // Argument check
    if (isset($args['filemodules']) && !is_array($args['filemodules'])) {
        return LogUtil::registerArgsError();
    }

    // default action
    $filemodules = (isset($args['filemodules']) ? $args['filemodules'] : modules_adminapi_getfilemodules());
    $defaults = (isset($args['defaults']) ? $args['defaults'] : false);

    // Get all modules in DB
    $dbmodules = DBUtil::selectObjectArray('modules', '','', -1, -1, 'name');

    if (!$dbmodules) {
        return LogUtil::registerError(__('Error! Could not load data.'));
    }

    // build a list of found modules and dependencies
    $module_names = array();
    $moddependencies = array();
    foreach ($filemodules as $modinfo) {
        $module_names[] = $modinfo['name'];
        if (isset($modinfo['moddependencies']) && !empty($modinfo['moddependencies'])) {
            $moddependencies[$modinfo['name']] = unserialize($modinfo['moddependencies']);
        }
    }

    // see if any modules have changed name since last generation
    foreach ($filemodules as $modinfo) {
        $name = $modinfo['name'];
        if (isset($modinfo['oldnames']) || !empty($modinfo['oldnames'])) {
            foreach ($dbmodules as $dbname => $dbmodinfo) {
                if (in_array($dbmodinfo['name'], $modinfo['oldnames'])) {
                    // merge the two modinfo arrays overwriting new with old
                    $dbmodinfo = array_merge($dbmodinfo, $modinfo);
                    // insert the new module info into the result set so it looks as if the new module name was always there
                    $dbmodules[$dbmodinfo['name']] = $dbmodinfo;
                    // use the version number of old module name for upgrade purposes
                    $dbmodules[$dbmodinfo['name']]['version'] = $dbmodules[$dbname]['version'];
                    // ensure the old module name doesn't get listed as missing and the new name as uninitialised
                    unset($dbmodules[$dbname]);
                    if ($dbmodules[$dbmodinfo['name']]['state'] != PNMODULE_STATE_UNINITIALISED &&
                        $dbmodules[$dbmodinfo['name']]['state'] != PNMODULE_STATE_INVALID) {
                        unset($dbmodinfo['version']);
                    }
                    // update the db with the new module info
                    DBUtil::updateObject($dbmodinfo, 'modules');
                }
            }
        }

        if (isset($dbmodules[$name]['id'])) {
            $modinfo['id'] = $dbmodules[$name]['id'];
            if ($dbmodules[$name]['state'] != PNMODULE_STATE_UNINITIALISED &&
                $dbmodules[$name]['state'] != PNMODULE_STATE_INVALID) {
                unset($modinfo['version']);
            }
            if (!$defaults) {
                unset($modinfo['displayname']);
                unset($modinfo['description']);
                unset($modinfo['url']);
            }
            DBUtil::updateObject($modinfo, 'modules');
        }
    }

    // See if we have lost any modules since last generation
    foreach ($dbmodules as $name => $modinfo) {
        if (!in_array($name, $module_names)) {
            // Old module
            // Get module ID
            $result = DBUtil::selectObjectByID('modules', $name, 'name');

            if ($result === false) {
                return LogUtil::registerError(__('Error! Could not load data.'));
            }

            // Ouch! jn
            if (empty($result)) {
                die(__('Error! Could not retrieve module ID.'));
            }

            // Set state of module to 'missing'
            modules_adminapi_setstate(array('id' => $result['id'], 'state' => PNMODULE_STATE_MISSING));
            unset($dbmodules[$name]);
        }
    }

    // See if we have gained any modules since last generation,
    // or if any current modules have been upgraded
    foreach ($filemodules as $modinfo) {
        $name = $modinfo['name'];
        if (empty($dbmodules[$name])) {
            // New module
            // RNG: set state to invalid if we can't determine an ID
            $modinfo['state'] = PNMODULE_STATE_UNINITIALISED;
            if (!$modinfo['version']) {
                $modinfo['state'] = PNMODULE_STATE_INVALID;
            }
            if ($GLOBALS['PNConfig']['Multisites']['multi'] == 1) {
                // only the main site can regenerate the modules list
                if ($GLOBALS['PNConfig']['Multisites']['mainSiteURL'] == FormUtil::getPassedValue(siteDNS, null, 'GET')) {
                    DBUtil::insertObject($modinfo, 'modules');
                }
            } else {
                DBUtil::insertObject($modinfo, 'modules');
            }
        } else {
            // module is in the db already
            if ($dbmodules[$name]['state'] == PNMODULE_STATE_MISSING) {
                // module was lost, now it is here again
                modules_adminapi_setstate(array('id'   => $dbmodules[$name]['id'],
                                                'state' => PNMODULE_STATE_INACTIVE));
            }
            if ($dbmodules[$name]['version'] != $modinfo['version']) {
                if ($dbmodules[$name]['state'] != PNMODULE_STATE_UNINITIALISED &&
                    $dbmodules[$name]['state'] != PNMODULE_STATE_INVALID) {
                    modules_adminapi_setstate(array('id'   => $dbmodules[$name]['id'],
                                                    'state' => PNMODULE_STATE_UPGRADED));
                }
            }
        }
    }

    // now clear re-load the dependencies table with all current dependencies
    DBUtil::truncateTable('module_deps');
    // loop round dependences adding the module id - we do this now rather than
    // earlier since we won't have the id's for new modules at that stage
    $dependencies = array();
    foreach ($moddependencies as $modname => $moddependency) {
        $modid = pnModGetIDFromName($modname);
         // each module may have multiple dependencies
        foreach ($moddependency as $dependency) {
            $dependency['modid'] = $modid;
            $dependencies[] = $dependency;
        }
    }
    DBUtil::insertObjectArray($dependencies, 'module_deps');

    return true;
}

/**
 * initialise a module
 * @author Jim McDonald, changed by Frank Schummertz for interactive init
 * @param int args['id'] module ID
 * @param int args['interactive_mode'] boolean that tells us if we are in interactive mode or not
 * @return bool true on success, false on failure or void when we bypassed the installation
 */
function modules_adminapi_initialise($args)
{
    // Argument check
    if (!isset($args['id']) || !is_numeric($args['id'])) {
        return LogUtil::registerArgsError();
    }

    // Get module information
    $modinfo = pnModGetInfo($args['id']);
    if (empty($modinfo)) {
        return LogUtil::registerError(__('Error! No such module ID exists.'));
    }

    // Get module database info
    pnModDBInfoLoad($modinfo['name'], $modinfo['directory']);
    $osdir = DataUtil::formatForOS($modinfo['directory']);
    if ($modinfo['type'] == 2) {
        if (is_dir("modules/$osdir/locale")) {
            ZLanguage::bindModuleDomain($modinfo['name']);
        }
    }

    // load module maintainence functions
    $modpath = ($modinfo['type'] == 3) ? 'system' : 'modules';
    if (file_exists($file = "$modpath/$osdir/pninit.php")) {
        if (!Loader::includeOnce($file)) {
            LogUtil::registerError(__f("Error! Could not load a required file: '%s'.", $file));
        }
    }


    // load the module info language files
    $userlang = DataUtil::formatForOS(ZLanguage::getLanguageCodeLegacy());
    $langfiles = array("$modpath/$osdir/pnlang/$userlang/init.php",
                       "$modpath/$osdir/lang/$userlang/init.php",
                       "$modpath/$osdir/lang/$userlang/version.php",
                       "$modpath/$osdir/pnlang/$userlang/version.php");
    foreach ($langfiles as $langfile) {
        if (file_exists($langfile)) {
            if (!Loader::includeOnce($langfile)) {
                LogUtil::registerError(__f("Error! Could not load a required file: '%s'.", $langfile));
            }
        }
    }

    // perform the actual upgrade of the module
    $func = $modinfo['name'] . '_init';
    $interactive_func = $modinfo['name'] . '_init_interactiveinit';

    // allow bypass of interactive install during a new installation only.
    if (defined('_PNINSTALLVER') && function_exists($interactive_func) && !function_exists($func)) {
        return; // return void here
    }

    if (!defined('_PNINSTALLVER') && isset($args['interactive_init']) && ($args['interactive_init'] == false) && function_exists($interactive_func)) {
        SessionUtil::setVar('interactive_init', true);
        return $interactive_func();
    }

    if (function_exists($func)) {
        if ($func() != true) {
            return false;
        }
    }

    // Update state of module
    if (!modules_adminapi_setstate(array('id' => $args['id'],
                                         'state' => PNMODULE_STATE_ACTIVE))) {
        return LogUtil::registerError(__('Error! Could not change module state.'));
    }

    // call any module initialisation hooks
    pnModCallHooks('module', 'initialise', $modinfo['name'], array('module' => $modinfo['name']));

    // Success
    return true;
}

/**
 * upgrade a module
 * @author Jim McDonald
 * @param int $args['id'] module ID
 * @param int $args['interactive_upgrade'] true if in interactive upgrade mode, otherwise false
 * @return bool true on success, false on failure
 */
function modules_adminapi_upgrade($args)
{
    // Argument check
    if (!isset($args['id']) || !is_numeric($args['id'])) {
        return LogUtil::registerArgsError();
    }

    // Get module information
    $modinfo = pnModGetInfo($args['id']);
    if (empty($modinfo)) {
        return LogUtil::registerError(__('Error! No such module ID exists.'));
    }

    // Get module database info
    pnModDBInfoLoad($modinfo['name'], $modinfo['directory']);
    $osdir = DataUtil::formatForOS($modinfo['directory']);
    if ($modinfo['type'] == 2) {
        $dir = "modules/$osdir/locale";
        if (is_dir($dir)) {
            ZLanguage::bindModuleDomain($modinfo['name']);
        }
    }

    // load module maintainence functions
    $modpath = ($modinfo['type'] == 3) ? 'system' : 'modules';
    if (file_exists($file = "$modpath/$osdir/pninit.php")) {
        if (!Loader::includeOnce($file)) {
            LogUtil::registerError(__f("Error! Could not load a required file: '%s'.", $file));
        }
    }

    // load the third party module info language files
    if ($modinfo['type'] != 3 && !$modinfo['i18n']) {
        $currentlang = DataUtil::formatForOS(ZLanguage::getLanguageCodeLegacy());
        $langfiles = array("$modpath/$osdir/pnlang/$currentlang/init.php",
                           "$modpath/$osdir/lang/$currentlang/init.php",
                           "$modpath/$osdir/pnlang/$currentlang/version.php",
                           "$modpath/$osdir/lang/$currentlang/version.php");

        Loader::loadOneFile($langfiles);
    }

    // perform the actual upgrade of the module
    $func = $modinfo['name'] . '_upgrade';
    $interactive_func = $modinfo['name'] . '_init_interactiveupgrade';

    // allow bypass of interactive upgrade during a new installation only.
    if (defined('_PNINSTALLVER') && function_exists($interactive_func) && !function_exists($func)) {
        return; // return void here
    }

    if (isset($args['interactive_upgrade']) && $args['interactive_upgrade'] == false && function_exists($interactive_func)) {
        SessionUtil::setVar('interactive_upgrade', true);
        return $interactive_func(array('oldversion' => $modinfo['version']));
    }

    if (function_exists($func)) {
        $result = $func($modinfo['version']);
        if (is_string($result)) {
            if ($result != $modinfo['version']) {
                // update the last successful updated version
                $modinfo['version'] = $result;
                $obj = DBUtil::updateObject($modinfo, 'modules', '', 'id', true);
            }
            return false;
        } elseif ($result != true) {
            return false;
        }
    }

    // Update state of module
    $result = modules_adminapi_setstate(array('id' => $args['id'], 'state' => PNMODULE_STATE_ACTIVE));
    if ($result) {
        LogUtil::registerStatus(__("Done! Module has been upgraded. Its status is now 'Active'."));
    } else {
        return false;
    }

    // BEGIN bugfix (561802) - cmgrote
    // Get the new version information...
    $modversion['version'] = '0';
    if (file_exists($file="$modpath/$osdir/Version.php")) {
        include $file;
    }
    if (file_exists($file="$modpath/$osdir/pnversion.php")) {
        include $file;
    }
    $version = $modversion['version'];

    // Note the changes in the database...
    // Get module database info
    pnModDBInfoLoad('Modules');

    $obj = array('id'            => $args['id'],
                 'version'       => $version);
    DBUtil::updateObject($obj, 'modules');
    // END bugfix (561802) - cmgrote

    // call any module upgrade hooks
    pnModCallHooks('module', 'upgrade', $modinfo['name'], array('module' => $modinfo['name']));

    // Success
    return true;
}

/**
 * utility function to count the number of items held by this module
 * @author Mark West
 * @since 1.16
 * @returns integer number of items held by this module
 */
function modules_adminapi_countitems($args)
{
    $pntable = pnDBGetTables();
    $modulescolumn = $pntable['modules_column'];

    // filter my first letter of module
    if (isset($args['letter']) && !empty($args['letter'])) {
        $where[] = "$modulescolumn[name] LIKE '" . DataUtil::formatForStore($args['letter']) . "%'";
        $startnum = 1;
    }

    // filter by module state
    switch ($args['state']) {
        case PNMODULE_STATE_UNINITIALISED:
        case PNMODULE_STATE_INACTIVE:
        case PNMODULE_STATE_ACTIVE:
        case PNMODULE_STATE_MISSING:
        case PNMODULE_STATE_UPGRADED:
        case PNMODULE_STATE_INVALID:
            $where[] = "$modulescolumn[state] = '" . DataUtil::formatForStore($args['state']) . "'";
            break;
    }

    // generate where clause
    $wheresql = '';
    if (isset($where) && is_array($where)) {
        $wheresql = 'WHERE ' . implode(' AND ', $where);
    }

    $count = DBUtil::selectObjectCount('modules', $wheresql);
    if ($count === false) {
        return LogUtil::registerError(__('Error! Could not load data.'));
    }

    return $count;
}

/**
 * Get a list of modules calling a particular hook module
 *
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @author Xaraya Team
 * @link http://www.xaraya.com
 * @param $args['hookmodname'] hook module we're looking for
 * @param $args['hookobject'] the object of the hook (item, module, ...) (optional)
 * @param $args['hookaction'] the action on that object (transform, display, ...) (optional)
 * @param $args['hookarea'] the area we're dealing with (GUI, API) (optional)
 * @return array of modules calling this hook module
 */
function modules_adminapi_gethookedmodules($args)
{
    // Argument check
    if (empty($args['hookmodname'])) {
        return LogUtil::registerArgsError();
    }

    $pntable = pnDBGetTables();
    $hookscolumn = $pntable['hooks_column'];

    $where = "WHERE $hookscolumn[tmodule]='".DataUtil::formatForStore($args['hookmodname'])."'";
    if (!empty($args['hookobject'])) {
        $where .= " AND $hookscolumn[object]='".DataUtil::formatForStore($args['hookobject'])."'";
    }
    if (!empty($args['hookaction'])) {
        $where .= " AND $$hookscolumn[action]='".DataUtil::formatForStore($args['hookaction'])."'";
    }
    if (!empty($args['hookarea'])) {
        $where .= " AND $hookscolumn[tarea]='".DataUtil::formatForStore($args['hookarea'])."'";
    }

    $objArray = DBUtil::selectObjectArray ('hooks', $where);

    // Check for an error with the database
    if ($objArray === false) {
        return false;
    }

    // modlist will hold the hooked modules
    static $modlist = array();
    foreach($objArray as $obj) {
        $smod = $obj['smodule'];
        if (empty($smod)) continue;

        $styp = $obj['stype'];
        if (empty($styp)) {
            $styp = 0;
        }

        $modlist[$smod][$styp] = 1;
    }

    return $modlist;
}

/**
 * Enable hooks between a caller module and a hook module
 *
 * @param $args['callermodname'] caller module
 * @param $args['hookmodname'] hook module
 * @return bool true if successful
 *
 * @author Xaraya Team
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 */
function modules_adminapi_enablehooks($args)
{
    // Argument check
    if (empty($args['callermodname']) || empty($args['hookmodname'])) {
        return LogUtil::registerArgsError();
    }

    $pntable = pnDBGetTables();
    $hookscolumn = $pntable['hooks_column'];

    // Rename operation
    // Delete hooks regardless
    $where = "WHERE $hookscolumn[smodule] = '".DataUtil::formatForStore($args['callermodname'])."'
                AND $hookscolumn[tmodule] = '".DataUtil::formatForStore($args['hookmodname'])."'";

    if (!DBUtil::deleteWhere('hooks', $where)) {
        return false;
    }

    $where = "WHERE $hookscolumn[smodule] = ''
                AND $hookscolumn[tmodule] = '".DataUtil::formatForStore($args['hookmodname'])."'";
    $objArray = DBUtil::selectObjectArray('hooks', $where, '', -1, -1, 'id');
    if (!$objArray) {
        return false;
    }

    $newHooks = array();
    foreach ($objArray as $hook) {
        unset($hook['id']);
        $hook['smodule'] = $args['callermodname'];
        $newHooks[] = $hook;
    }
    $result = DBUtil::insertObjectArray($newHooks, 'hooks');
    if (!$result) {
        return false;
    }

    return true;
}

/**
 * Disable hooks between a caller module and a hook module
 *
 * @param $args['callermodname'] caller module
 * @param $args['hookmodname'] hook module
 * @return bool true if successful
 *
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @author Xaraya Team
 */
function modules_adminapi_disablehooks($args)
{
    // Argument check
    if (empty($args['callermodname']) || empty($args['hookmodname'])) {
        return LogUtil::registerArgsError();
    }
    if (empty($args['calleritemtype'])) {
        $args['calleritemtype'] = '';
    }

    // Rename operation
    $pntable = pnDBGetTables();
    $hookscolumn = $pntable['hooks_column'];

    // Delete hooks regardless
    $where = "WHERE $hookscolumn[smodule] = '".DataUtil::formatForStore($args['callermodname'])."'
                AND $hookscolumn[stype]   = '".DataUtil::formatForStore($args['calleritemtype'])."'
                AND $hookscolumn[tmodule] = '".DataUtil::formatForStore($args['hookmodname'])."'";

    return DBUtil::deleteWhere('hooks', $where);
}

/**
 * Get a list of hooks for a given module
 *
 * @author Frank Schummertz
 * @param $args['modid'] the modules id
 * @return array array of hooks attached the module
 */
function modules_adminapi_getmoduleshooks($args)
{
    // Argument check
    if (!isset($args['modid']) || !is_numeric($args['modid'])) {
        return LogUtil::registerArgsError();
    }

    // check if module id is valid
    $modinfo = pnModGetInfo($args['modid']);
    if ($modinfo == false) {
        return LogUtil::registerError(__('Error! No such module ID exists.'));
    }

    $pntable = pnDBGetTables();
    $hookscolumn = $pntable['hooks_column'];

    $where = "WHERE $hookscolumn[smodule] = ''
                 OR $hookscolumn[smodule] = '" . DataUtil::formatForStore($modinfo['name']) . "'";
    $orderBy = "ORDER BY $hookscolumn[tmodule],
                         $hookscolumn[smodule] DESC";
    $objArray = DBUtil::selectObjectArray('hooks', $where, $orderBy);

    if ($objArray === false) {
        return false;
    }

    $displayed = array();
    $ak = array_keys($objArray);
    $myArray = array();
    foreach ($ak as $v) {
        if (isset($displayed[$objArray[$v]['tmodule']])) {
            continue;
        }
        $displayed[$objArray[$v]['tmodule']] = true;

        if (!empty($objArray[$v]['smodule'])) {
            $objArray[$v]['hookvalue'] = 1;
        } else {
            $objArray[$v]['hookvalue'] = 0;
        }
        array_push($myArray, $objArray[$v]);
    }

    return $myArray;
}

/**
 * Get a extended list of hooks for a given module
 *
 * @author Frank Schummertz
 * @param $args['modid'] the modules id
 * @return array array of hooks attached the module
 */
function modules_adminapi_getextendedmoduleshooks($args)
{
    // Argument check
    if (!isset($args['modid']) || !is_numeric($args['modid'])) {
        return LogUtil::registerArgsError();
    }

    // check if module id is valid
    $modinfo = pnModGetInfo($args['modid']);
    if ($modinfo == false) {
        return LogUtil::registerError(__('Error! No such module ID exists.'));
    }

    $pntable = pnDBGetTables();
    $hookscolumn = $pntable['hooks_column'];

    $where = "WHERE $hookscolumn[smodule] = ''";
    $orderBy = "ORDER BY $hookscolumn[action],
                         $hookscolumn[sequence] ASC";
    $hooksArray = DBUtil::selectObjectArray('hooks', $where, $orderBy);
    // sort the hooks by action
    $grouped_hooks = array();
    foreach($hooksArray as $hookobject) {
        if (!array_key_exists($hookobject['action'], $grouped_hooks)) {
            $grouped_hooks[$hookobject['action']] = array();
        }
        $hookobject['hookvalue'] = 0;
        $grouped_hooks[$hookobject['action']][$hookobject['tmodule']] = $hookobject;
    }
    if ($grouped_hooks === false) {
        return false;
    }

    $where = "WHERE $hookscolumn[smodule] = '" . DataUtil::formatForStore($modinfo['name']) . "'";
    $orderBy = "ORDER BY $hookscolumn[action],
                         $hookscolumn[sequence] ASC";
    $objArray = DBUtil::selectObjectArray('hooks', $where, $orderBy);
    if ($objArray === false) {
        return false;
    }

    $displayed = array();
    $ak = array_keys($objArray);
    foreach ($ak as $v) {
        unset($grouped_hooks[$objArray[$v]['action']][$objArray[$v]['tmodule']]);
        $objArray[$v]['hookvalue'] = 1;
        $grouped_hooks[$objArray[$v]['action']][$objArray[$v]['tmodule']] = $objArray[$v];
    }

    return $grouped_hooks;
}

/**
 * get available admin panel links
 *
 * @author Mark West
 * @return array array of admin links
 */
function modules_adminapi_getlinks()
{
    $links = array();

    // assign variables from input
    $startnum = (int)FormUtil::getPassedValue('startnum', null, 'REQUEST');
    $letter = FormUtil::getPassedValue('letter', null, 'REQUEST');

    if (SecurityUtil::checkPermission('Modules::', '::', ACCESS_READ)) {
        $links[] = array('url' => pnModURL('Modules', 'admin', 'view'), 'text' => __('Modules list'));
    }
    if (SecurityUtil::checkPermission('Modules::', '::', ACCESS_ADD)) {
        $links[] = array('url' => pnModURL('Modules', 'admin', 'regenerate', array('startnum' => $startnum, 'letter' => $letter, 'authid' => SecurityUtil::generateAuthKey('Modules'))), 'text' => __('Regenerate modules list'));
    }
    if (SecurityUtil::checkPermission('Modules::', '::', ACCESS_ADMIN)) {
        $links[] = array('url' => pnModURL('Modules', 'admin', 'hooks', array('id' => 0)), 'text' => __('System hooks'));
        $links[] = array('url' => pnModURL('Modules', 'admin', 'modifyconfig'), 'text' => __('Settings'));
    }

    return $links;
}

/**
 * get all module dependencies
 *
 * @author Mark West
 * @return array of dependencies
 */
function modules_adminapi_getdallependencies($args)
{
    return DBUtil::selectObjectArray('module_deps', '', 'modid');
}

/**
 * get dependencies for a module
 *
 * @author Mark West
 * @param modid - id of module to get dependencies for
 * @return mixed array of dependencies e or false otherwise
 */
function modules_adminapi_getdependencies($args)
{
    // Argument check
    if (!isset($args['modid']) || empty($args['modid']) || !is_numeric($args['modid'])) {
        return LogUtil::registerArgsError();
    }

    $where = "pn_modid = '".DataUtil::formatForStore($args['modid'])."'";
    return DBUtil::selectObjectArray('module_deps', $where, 'modname');
}

/**
 * get dependencies for a module
 *
 * @author Mark West
 * @param modid - id of module to get dependencies for
 * @return mixed array of dependencies e or false otherwise
 */
function modules_adminapi_getdependents($args)
{
    // Argument check
    if (!isset($args['modid']) || empty($args['modid']) || !is_numeric($args['modid'])) {
        return LogUtil::registerArgsError();
    }
    $modinfo = pnModGetInfo($args['modid']);
    $where = "pn_modname = '".DataUtil::formatForStore($modinfo['name'])."'";
    return DBUtil::selectObjectArray('module_deps', $where, 'modid');
}

/**
 * check modules for consistency
 * @author J�rg Napp
 * @param array args['filemodules'] array of modules in the filesystem, as returned by modules_adminapi_getfilemodules
 * @return array an array of arrays with links to inconsistencies
 * @see modules_adminapi_getfilemodules()
 */
function modules_adminapi_checkconsistency($args)
{
    // Security check
    if (!defined('_PNINSTALLVER')) {
        if (!SecurityUtil::checkPermission('Modules::', '::', ACCESS_ADMIN)) {
            return LogUtil::registerPermissionError();
        }
    }

    // Argument check
    if (!isset($args['filemodules']) || !is_array($args['filemodules'])) {
        return LogUtil::registerArgsError();
    }

    $filemodules = $args['filemodules'];

    $modulenames = array();
    $displaynames = array();

    $errors_modulenames = array();
    $errors_displaynames = array();

    // check for duplicate names or display names
    foreach($filemodules as $dir=>$modinfo){
        if (isset($modulenames[strtolower($modinfo['name'])])) {
            $errors_modulenames[] = array('name' => $modinfo['name'],
                                          'dir1' => $modulenames[strtolower($modinfo['name'])],
                                          'dir2' => $dir);
        }

        if (isset($displaynames[strtolower($modinfo['displayname'])])) {
            $errors_displaynames[] = array('name' => $modinfo['displayname'],
                                           'dir1' => $displaynames[strtolower($modinfo['displayname'])],
                                           'dir2' => $dir);
        }

        if (isset($displaynames[strtolower($modinfo['url'])])) {
                    $errors_displaynames[] = array('name' => $modinfo['url'],
                                                   'dir1' => $displaynames[strtolower($modinfo['url'])],
                                                   'dir2' => $dir);
        }

        $modulenames[strtolower($modinfo['name'])] = $dir;
        $displaynames[strtolower($modinfo['displayname'])] = $dir;
    }

    // do we need to check for duplicate oldnames as well?

    return array('errors_modulenames'  => $errors_modulenames,
                 'errors_displaynames' => $errors_displaynames);

}