<?php
/**
 * Zikula Application Framework
 * @copyright (c) 2002, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: pnversion.php 27268 2009-10-30 10:02:50Z mateo $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula_System_Modules
 * @subpackage Permissions
 */

$modversion['name']           = 'Permissions';
$modversion['displayname']    = __('Permission rules manager');
$modversion['description']    = __("Provides an interface for fine-grained management of accessibility of the site's functionality and content through permission rules.");
//! module name that appears in URL
$modversion['url']            = __('permissions');
$modversion['version']        = '1.1';

$modversion['credits']        = 'pndocs/credits.txt';
$modversion['help']           = 'pndocs/install.txt';
$modversion['changelog']      = 'pndocs/changelog.txt';
$modversion['license']        = 'pndocs/license.txt';
$modversion['official']       = 1;
$modversion['author']         = 'Jim McDonald, M.Maes';
$modversion['contact']        = 'http://www.mcdee.net/, http://www.mmaes.com';

$modversion['securityschema'] = array('Permissions::' => '::');
