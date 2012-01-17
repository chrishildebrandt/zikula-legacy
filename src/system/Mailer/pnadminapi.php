<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2001, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: pnadminapi.php 27363 2009-11-02 16:40:08Z mateo $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @uses PHPMailer http://phpmailer.sourceforge.net
 * @package Zikula_System_Modules
 * @subpackage Mailer
 */

/**
 * get available admin panel links
 *
 * @author Mark West
 * @return array array of admin links
 */
function Mailer_adminapi_getlinks()
{
    $links = array();

    if (SecurityUtil::checkPermission('Mailer::', '::', ACCESS_ADMIN)) {
        $links[] = array('url' => pnModURL('Mailer', 'admin', 'testconfig'), 'text' => __('Test current settings'));
    }
    if (SecurityUtil::checkPermission('Mailer::', '::', ACCESS_ADMIN)) {
        $links[] = array('url' => pnModURL('Mailer', 'admin', 'modifyconfig'), 'text' => __('Settings'));
    }

    return $links;
}
