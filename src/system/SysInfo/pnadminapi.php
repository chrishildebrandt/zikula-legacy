<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2002, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: pnadminapi.php 27319 2009-11-01 06:58:55Z drak $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula_System_Modules
 * @subpackage SysInfo
 * @license http://www.gnu.org/copyleft/gpl.html
*/

/**
 * get available admin panel links
 *
 * @author Simon Birtwistle
 * @return array array of admin links
 */
function SysInfo_adminapi_getlinks()
{
    $links = array();

    if (SecurityUtil::checkPermission('SysInfo::', '::', ACCESS_ADMIN)) {
        $links[] = array('url' => pnModURL('SysInfo', 'admin', 'main'), 'text' => __('System summary'));
    }
    if (SecurityUtil::checkPermission('SysInfo::', '::', ACCESS_ADMIN)) {
        $links[] = array('url' => pnModURL('SysInfo', 'admin', 'phpinfo', array('info' => 4)), 'text' => __('PHP configuration'));
    }
    if (SecurityUtil::checkPermission('SysInfo::', '::', ACCESS_ADMIN)) {
        $links[] = array('url' => pnModURL('SysInfo', 'admin', 'phpinfo', array('info' => 8)), 'text' => __('PHP modules'));
    }
    if (SecurityUtil::checkPermission('SysInfo::', '::', ACCESS_ADMIN)) {
        $links[] = array('url' => pnModURL('SysInfo', 'admin', 'phpinfo', array('info' => 16)), 'text' => __('Server environment'));
    }
    if (SecurityUtil::checkPermission('SysInfo::', '::', ACCESS_ADMIN)) {
        $links[] = array('url' => pnModURL('SysInfo', 'admin', 'phpinfo', array('info' => 32)), 'text' => __('PHP variables'));
    }
    if (SecurityUtil::checkPermission('SysInfo::', '::', ACCESS_ADMIN)) {
        $links[] = array('url' => pnModURL('SysInfo', 'admin', 'adodb'), 'text' => 'ADODB', 'linebreak' => true);
    }
    if (SecurityUtil::checkPermission('SysInfo::', '::', ACCESS_ADMIN)) {
        $links[] = array('url' => pnModURL('SysInfo', 'admin', 'extensions'), 'text' => __('Zikula extensions'));
    }
    if (SecurityUtil::checkPermission('SysInfo::', '::', ACCESS_ADMIN)) {
        $links[] = array('url' => pnModURL('SysInfo', 'admin', 'filesystem'), 'text' => __('Zikula file system'));
    }
    if (SecurityUtil::checkPermission('SysInfo::', '::', ACCESS_ADMIN)) {
        $links[] = array('url' => pnModURL('SysInfo', 'admin', 'pntemp'), 'text' => __('Zikula pnTemp directory'));
    }

    return $links;
}

/**
 * Get a list of all files and folders within the filesystem
 * @author Andreas Krapohl
 * @return string HTML output string
 */
function SysInfo_adminapi_filelist ($args)
{
    if (!isset($args['startdir'])) {
        $args['startdir'] = './';
    }
    if (!isset($args['searchSubdirs']) || !is_numeric($args['searchSubdirs'])) {
        $args['searchSubdirs'] = 1;
    }
    if (!isset($args['directoriesonly']) || !is_numeric($args['directoriesonly'])) {
        $args['directoriesonly'] = 0;
    }
    if (!isset($args['maxlevel'])) {
        $args['maxlevel'] = 'all';
    }
    if (!isset($args['level']) || !is_numeric($args['level'])) {
        $args['level'] = 1;
    }
    if (!isset($args['pntemp']) || !is_numeric($args['pntemp'])) {
        $args['pntemp'] = 0;
    }

    if (!SecurityUtil::checkPermission('SysInfo::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    $startdir = $args['startdir'];
    $searchSubdirs = $args['searchSubdirs'];
    $directoriesonly = $args['directoriesonly'];
    $maxlevel = $args['maxlevel'];
    $level = $args['level'];
    $pntemp = $args['pntemp'];

    // this process could take a while....
    @set_time_limit(0);
    @ini_set('memory_limit', '128M');

    $ignoredDirectory[] = '.';
    $ignoredDirectory[] = '..';
    $ignoredDirectory[] = '.svn';
    $pntemp = DataUtil::formatForOS(CacheUtil::getLocalDir(),true);
    if ($pntemp == 0) {
        $ignoredDirectory[] = $pntemp;
    }

    global $directorylist;
    if (is_dir($startdir)) {
        if ($dh = @opendir($startdir)) {
            while (($file = readdir($dh)) !== false) {
                if (!(array_search($file,$ignoredDirectory) > -1)) {
                    if (filetype($startdir . $file) == 'dir') {
                        $directorylist[$startdir . $file]['dir'] = __('Folder');
                        $directorylist[$startdir . $file]['path'] = $startdir;
                        $directorylist[$startdir . $file]['name'] = $file;
                        $directorylist[$startdir . $file]['writable'] = (bool)is_writable($startdir . $file);
                        if ($searchSubdirs) {
                            if ((($maxlevel) == 'all') or ($maxlevel > $level)) {
                                pnModApiFunc('SysInfo', 'admin', 'filelist',
                                              array ('startdir' => $startdir . $file . '/',
                                                     'searchSubdirs' => $searchSubdirs,
                                                     'directoriesonly' => $directoriesonly,
                                                     'maxlevel' => $maxlevel,
                                                     'level' => $level + 1));
                            }
                        }
                    } else {
                        if (!$directoriesonly) {
                            $directorylist[$startdir . $file]['dir'] = __('File');
                            $directorylist[$startdir . $file]['path'] = $startdir;
                            $directorylist[$startdir . $file]['name'] = $file;
                            $directorylist[$startdir . $file]['writable'] = (bool)is_writable($startdir . $file);
                        }
                    }
                }
            }
            closedir($dh);
        }
    }
    return($directorylist);
}
