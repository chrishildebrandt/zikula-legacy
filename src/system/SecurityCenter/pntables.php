<?php
/**
 * Zikula Application Framework
 * @copyright (c) 2001, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: pntables.php 24342 2008-06-06 12:03:14Z markwest $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula_System_Modules
 * @subpackage AntiCracker
*/

/**
 * This function is called internally by the core whenever the module is
 * loaded.  It adds in the information
 * @author Mark West
 * @return array pntables array
 */
function securitycenter_pntables()
{
    // Initialise table array
    $pntable = array();

    // Set the table name
    $pntable['sc_anticracker'] = DBUtil::getLimitedTablename('sc_anticracker');

    // Set the column names.  Note that the array has been formatted
    // on-screen to be very easy to read by a user.
    $pntable['sc_anticracker_column'] = array('hid'       => 'pn_hid',
                                           'hacktime'     => 'pn_hacktime',
                                           'hackfile'     => 'pn_hackfile',
                                           'hackline'     => 'pn_hackline',
                                           'hacktype'     => 'pn_hacktype',
                                           'hackinfo'     => 'pn_hackinfo',
                                           'userid'       => 'pn_userid',
                                           'browserinfo'  => 'pn_browserinfo',
                                           'requestarray' => 'pn_requestarray',
                                           'getarray'     => 'pn_gettarray',
                                           'postarray'    => 'pn_postarray',
                                           'serverarray'  => 'pn_serverarray',
                                           'envarray'     => 'pn_envarray',
                                           'cookiearray'  => 'pn_cookiearray',
                                           'filesarray'   => 'pn_filesarray',
                                           'sessionarray' => 'pn_sessionarray');

    $pntable['sc_anticracker_column_def'] = array('hid'       => 'I PRIMARY AUTO',
                                               'hacktime'     => 'C(20) DEFAULT NULL',
                                               'hackfile'     => "C(255) DEFAULT ''",
                                               'hackline'     => 'I DEFAULT NULL',
                                               'hacktype'     => "C(255) DEFAULT ''",
                                               'hackinfo'     => "XL",
                                               'userid'       => 'I DEFAULT NULL',
                                               'browserinfo'  => 'XL',
                                               'requestarray' => 'XL',
                                               'getarray'     => 'XL',
                                               'postarray'    => 'XL',
                                               'serverarray'  => 'XL',
                                               'envarray'     => 'XL',
                                               'cookiearray'  => 'XL',
                                               'filesarray'   => 'XL',
                                               'sessionarray' => 'XL');


    // Log Event Table Name
    $pntable['sc_logevent'] = DBUtil::getLimitedTablename('sc_log_event');
    $pntable['sc_logevent_column'] = array('id'             => 'lge_id',
                                           'date'           => 'lge_date',
                                           'uid'            => 'lge_uid',
                                           'component'      => 'lge_component',
                                           'module'         => 'lge_module',
                                           'type'           => 'lge_type',
                                           'function'       => 'lge_function',
                                           'sec_component'  => 'lge_sec_component',
                                           'sec_instance'   => 'lge_sec_instance',
                                           'sec_permission' => 'lge_sec_permission',
                                           'message'        => 'lge_message');
    $pntable['sc_logevent_column_def'] = array('id'             => 'I PRIMARY AUTO',
                                               'date'           => 'T DEFAULT NULL',
                                               'uid'            => 'I4 DEFAULT NULL',
                                               'component'      => "C(64) DEFAULT NULL",
                                               'module'         => 'C(64) DEFAULT NULL',
                                               'type'           => 'C(64) DEFAULT NULL',
                                               'function'       => 'C(64) DEFAULT NULL',
                                               'sec_component'  => 'C(64) DEFAULT NULL',
                                               'sec_instance'   => 'C(64) DEFAULT NULL',
                                               'sec_permission' => 'C(64) DEFAULT NULL',
                                               'message'        => "C(255) DEFAULT ''");

    // Return the table information
    return $pntable;
}
