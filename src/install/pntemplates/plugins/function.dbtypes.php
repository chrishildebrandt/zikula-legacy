<?php
/**
 * pnRender plugin
 *
 * This file is a plugin for pnRender, the Zikula implementation of Smarty
 *
 * @package      Xanthia_Templating_Environment
 * @subpackage   pnRender
 * @version      $Id: function.dbtypes.php 27064 2009-10-21 17:04:13Z drak $
 * @author       The Zikula development team
 * @link         http://www.zikula.org  The Zikula Home Page
 * @copyright    Copyright (C) 2002 by the Zikula Development Team
 * @license      http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */


/**
 * Smarty function to display a drop down list of database engines
 *
 * Available parameters:
 *   - assign:   If set, the results are assigned to the corresponding variable instead of printed out
 *   - name:     Name for the control
 *   - selected: Selected value
 *
 * Example
 *   <!--[dbtypes name=dbtype selectedValue=value]-->
 *
 *
 * @author       Mark West
 * @since        17 March 2006
 * @param        array       $params      All attributes passed to this function from the template
 * @param        object      &$smarty     Reference to the Smarty object
 * @return       string      the value of the last status message posted, or void if no status message exists
 */
function smarty_function_dbtypes($params, &$smarty)
{
    if (!isset($params['name'])) {
        $smarty->trigger_error("dbtypes: parameter 'name' required");
        return false;
    }
    if (!isset($params['id'])) {
        $params['id'] = $params['name'];
    }

    $name = DataUtil::formatForDisplay($params['name']);
    $id = DataUtil::formatForDisplay($params['id']);
    $sv   = isset($params['selectedValue']) ? $params['selectedValue'] : 'mysql';

    $dbtypesdropdown = "<select name=\"$name\" id=\"$id\" onchange=\"dbtype_onchange()\">\n";
    if (function_exists('mysql_connect')) {
        $sel = $sv=='mysql' ? 'selected' : '';
        $dbtypesdropdown .= "<option value=\"mysql\" $sel>" . __('MySQL') . "</option>\n";
    }
    if (function_exists('mysqli_connect')) {
        $sel = $sv=='mysqli' ? 'selected' : '';
        $dbtypesdropdown .= "<option value=\"mysqli\" $sel>" . __('MySQL Improved') . "</option>\n";
    }
    if (function_exists('mssql_connect')) {
        $sel = $sv=='mssql' ? 'selected' : '';
        $dbtypesdropdown .= "<option value=\"mssql\" $sel>" . __('MSSQL (alpha)') . "</option>\n";
    }
    if (function_exists('OCIPLogon')) {
        $sel = $sv=='oci8' ? 'selected' : '';
        $dbtypesdropdown .= "<option value=\"oci8\" $sel>" . __('Oracle (alpha) via OCI8 driver') . "</option>\n";
        $sel = $sv=='oracle' ? 'selected' : '';
        $dbtypesdropdown .= "<option value=\"oracle\" $sel>" . __('Oracle (alpha) via Oracle driver') . "</option>\n";
    }
    if (function_exists('pg_connect')) {
        $sel = $sv=='postgres' ? 'selected' : '';
        $dbtypesdropdown .= "<option value=\"postgres\" $sel>" . __('PostgreSQL') . "</option>\n";
    }
    $dbtypesdropdown .= "</select>\n";

    if (isset($params['assign'])) {
        $smarty->assign($params['assign'], $dbtypesdropdown);
    } else {
        return $dbtypesdropdown;
    }
}
