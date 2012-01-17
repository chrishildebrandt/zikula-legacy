<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2002, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: config.php 26908 2009-10-12 13:14:24Z drak $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula_Core
 */

// ----------------------------------------------------------------------
// NOTICE
// Zikula includes an install script which can populate the database
// and write this config file automatically.  There is normally no need
// to manually edit this file!
// ----------------------------------------------------------------------

// ----------------------------------------------------------------------
// Database & System Config
//
//      dbtype:      type of database, can be mysql, mysqli, mssql, oci8, or oracle
//      dbtabletype: type of table for MySQL database, MyISAM, INNODB
//      dbhost:      Database Hostname
//      dbuname:     Username
//      dbpass:      Password
//      dbname:      Database Name
//      encoded:     0 for username/password information plaintext
//                   1 for username/password information base64 encoded
//      pconnect:    0 use connect
//                   1 use pconnect
// ----------------------------------------------------------------------
//
// ----------------------------------------------------------------------
// The following define some global settings for the application
// ----------------------------------------------------------------------
global $PNConfig;
$PNConfig['System']['installed']   = 0;         // installer will change this during installation
$PNConfig['System']['tabletype']   = 'myisam';  // installer will change this during installation
$PNConfig['System']['temp']        = 'pnTemp';  // installer will change this during installation
$PNConfig['System']['prefix']      = 'z';      // installer will change this during installation
$PNConfig['System']['development'] = 0;         // should be set to 0/false when cutting a release for production use
$PNConfig['System']['default_timezone'] = 'GMT'; // TZ timezone

// ----------------------------------------------------------------------
// This is the definition for the default Zikula system database.
// It *must* be named 'default'!
// ----------------------------------------------------------------------
$PNConfig['DBInfo']['default']['dbtype']      = 'mysql';
$PNConfig['DBInfo']['default']['dbhost']      = 'localhost';
$PNConfig['DBInfo']['default']['dbuname']     = '';
$PNConfig['DBInfo']['default']['dbpass']      = '';
$PNConfig['DBInfo']['default']['dbname']      = '';
$PNConfig['DBInfo']['default']['encoded']     = 1;
$PNConfig['DBInfo']['default']['pconnect']    = 0;
$PNConfig['DBInfo']['default']['dbtabletype'] = 'innodb';
$PNConfig['DBInfo']['default']['dbcharset']   = 'utf8';     // changed from 'latin1'!

// ----------------------------------------------------------------------
// Please consult the MySQL documentation for valid character set names!
// 'UTF-8' = 'utf8'
// ----------------------------------------------------------------------

// ----------------------------------------------------------------------
// The following define the list of databases the system can access. You
// can define as many as you like provided you give each one a unique
// name (the key value following the DBInfo array element)
// ----------------------------------------------------------------------
$PNConfig['DBInfo']['external1']['dbtype']    = 'mssql';    // sample value
$PNConfig['DBInfo']['external1']['dbhost']    = 'otherhost1';   // sample value
$PNConfig['DBInfo']['external1']['dbuname']   = '';
$PNConfig['DBInfo']['external1']['dbpass']    = '';
$PNConfig['DBInfo']['external1']['dbname']    = 'pntest';   // sample value
$PNConfig['DBInfo']['external1']['encoded']   = 1;
$PNConfig['DBInfo']['external1']['pconnect']  = 0;
$PNConfig['DBInfo']['external1']['dbtabletype'] = 'innodb';
$PNConfig['DBInfo']['external1']['dbcharset']   = 'utf8';

// additional DB can be configured here ...
$PNConfig['DBInfo']['external2']['dbtype']    = 'oracle';   // sample value
$PNConfig['DBInfo']['external2']['dbhost']    = 'otherhost2';   // sample value
$PNConfig['DBInfo']['external2']['dbuname']   = '';
$PNConfig['DBInfo']['external2']['dbpass']    = '';
$PNConfig['DBInfo']['external2']['dbname']    = 'integration';  // sample value
$PNConfig['DBInfo']['external2']['encoded']   = 1;
$PNConfig['DBInfo']['external2']['pconnect']  = 0;
$PNConfig['DBInfo']['external2']['dbtabletype'] = 'innodb';
$PNConfig['DBInfo']['external2']['dbcharset']   = 'utf8';
// and so on ...

// ----------------------------------------------------------------------
// Debugging/Tracing settings
// ----------------------------------------------------------------------
$PNConfig['Debug']['debug']          = 0;   //
$PNConfig['Debug']['pagerendertime'] = 0;   // display page render time, 0 to disable
$PNConfig['Debug']['sql_adodb']      = 0;   // adodb debug flag, generates lots of print output
$PNConfig['Debug']['sql_count']      = 0;   // count sql statements, 0 to disable
$PNConfig['Debug']['sql_time']       = 0;   // time sql statements, 0 to disable
$PNConfig['Debug']['sql_detail']     = 0;   // collect executed sql statements, 0 to disable
$PNConfig['Debug']['sql_data']       = 0;   // collect selected data, 0 to disable
$PNConfig['Debug']['sql_user']       = 0;   // user filter, 0 for all, any other number is a user-id, can also be an array

// ----------------------------------------------------------------------
// Error Reporting
// ----------------------------------------------------------------------
$PNConfig['Debug']['error_reporting_development'] = E_ALL;                           // preconfigured level
$PNConfig['Debug']['error_reporting_production']  = E_ALL & ~E_NOTICE & ~E_WARNING;  // preconfigured level
$PNConfig['Debug']['debug_key']                   = ($PNConfig['System']['development'] ? 'error_reporting_development' : 'error_reporting_production');
error_reporting($PNConfig['Debug'][$PNConfig['Debug']['debug_key']]);                // now set the appropriate level

// ----------------------------------------------------------------------
// Logging Settings
// ----------------------------------------------------------------------
$PNConfig['Log']['log_enabled']          = 0;                                                      // global logging to on/off switch for 'log_dest' (0=off, 1=on)
$PNConfig['Log']['log_dest']             = 'FILE';                                                 // the default logging destination. Can be "FILE", "PRINT", "EMAIL" or "DB".
$PNConfig['Log']['log_dir']              = $PNConfig['System']['temp'] . '/error_logs/';           // the directory containing all log files
$PNConfig['Log']['log_file']             = $PNConfig['Log']['log_dir'] . 'zikula-%s.log';          // %s is where todays date will go
$PNConfig['Log']['log_file_uid']         = 0;                                                      // wether or not a separate log file is used for each user. The filename is derived from $PNConfig['Log']['log_file']
$PNConfig['Log']['log_file_date_format'] = 'Ymd';                                                  // dateformat to be used for the generated log filename
$PNConfig['Log']['log_maxsize']          = 1.0;                                                    // value in MB. Decimal is OK. (Use 0 for no limit)
$PNConfig['Log']['log_user']             = 0;                                                      // user filter for logging, 0 for all, can also be an array
$PNConfig['Log']['log_levels']           = array('CORE', 'DB', 'DEFAULT', 'WARNING', 'FATAL', 'STRICT');     // User defined. To get everything use: $log_level = array("All");
$PNConfig['Log']['log_show_errors']      = true;                                                   // Show php logging errors on screen (Use while developing only)
$PNConfig['Log']['log_date_format']      = "Y-m-d H:i:s";                                          // 2006-07-19 18:41:50
$PNConfig['Log']['log_level_dest']       = array('DB' => 'PRINT');                                 // array of level-specific log destinations
$PNConfig['Log']['log_level_files']      = array('DB' => $PNConfig['System']['temp'] . '/error_logs/zikula-sql-%s.log'); // array of level-specific log files (only used if destination=="FILE")
$PNConfig['Log']['log_keep_days']        = 30;                                                     // amount of days to keep log files for (older files will be erased)
$PNConfig['Log']['log_apache_uname']     = 0;                                                      // log username to apache logs: please see documentation.  Please check you country's local law covering the logging of personally identifiable user data before enabling.

// ----------------------------------------------------------------------
// The following define some data layer settings
// ----------------------------------------------------------------------
$PNConfig['System']['PN_CONFIG_USE_OBJECT_ATTRIBUTION']    = 0;   // enable universal attribution layer, 0 to turn off
$PNConfig['System']['PN_CONFIG_USE_OBJECT_CATEGORIZATION'] = 1;   // categorization/filtering services, 0 to turn off
$PNConfig['System']['PN_CONFIG_USE_OBJECT_LOGGING']        = 0;   // object audit trail logging, 0 to turn off
$PNConfig['System']['PN_CONFIG_USE_OBJECT_META']           = 0;   // meta-data services, 0 to turn off
$PNConfig['System']['PN_CONFIG_USE_TRANSACTIONS']          = 0;   // run request as a transaction, 0 to turn off

// ----------------------------------------------------------------------
// Initialize runtime variables to sane defaults
// ----------------------------------------------------------------------
global $PNRuntime;
$PNRuntime['sql']               = array();
$PNRuntime['sql_count_request'] = 0;
$PNRuntime['sql_time_request']  = 0;

// ----------------------------------------------------------------------
// Multisites configuration
// ----------------------------------------------------------------------
if (is_readable('config/multisites_config.php')) {
    require_once 'config/multisites_config.php';
}


// ----------------------------------------------------------------------
// if there is a personal_config.php in the folder where is config.php
// we add it. (This HAS to be at the end, after all initialization.)
// ----------------------------------------------------------------------
if (is_readable('config/personal_config.php')) {
    require_once 'config/personal_config.php';
}

