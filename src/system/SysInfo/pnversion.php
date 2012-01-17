<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2003, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: pnversion.php 27268 2009-10-30 10:02:50Z mateo $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula_System_Modules
 * @subpackage SysInfo
 */

$modversion['name']           = 'SysInfo';
$modversion['displayname']    = __('System info panel');
$modversion['description']    = __('Provides detailed information reports about the system configuration and environment, for tracking and troubleshooting purposes.');
//! module name that appears in URL
$modversion['url']            = __('sysinfo');
$modversion['version']        = '1.1';

$modversion['credits']        = 'pndocs/credits.txt';
$modversion['help']           = 'pndocs/help.txt';
$modversion['changelog']      = 'pndocs/changelog.txt';
$modversion['license']        = 'pndocs/license.txt';
$modversion['official']       = 1;
$modversion['author']         = 'Simon Birtwistle';
$modversion['contact']        = 'hammerhead@zikula.org';

$modversion['securityschema'] = array('SysInfo::' => '::');
