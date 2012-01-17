<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2002, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: function.adminonlinemanual.php 26980 2009-10-15 06:16:58Z drak $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package      Zikula_System_Modules
 * @subpackage   Admin
 */


/**
 * Smarty function to displaya modules online manual
 *
 * Admin
 * <!--[adminonlinemanual]-->
 *
 * @author       Frank Schummertz
 * @since        16.01.2005
 * @see          function.admincategorymenu.php::smarty_function_admincategoreymenu()
 * @param        array       $params      All attributes passed to this function from the template
 * @param        object      &$smarty     Reference to the Smarty object
 * @param        int         xhtml        if set, the link to the navtabs.css will be xhtml compliant
 * @return       string      the results of the module function
 */
function smarty_function_adminonlinemanual($params, &$smarty)
{
    $lang = ZLanguage::transformFS(ZLanguage::getLanguageCode());
    $modinfo = pnModGetInfo(pnModGetIDFromName(pnModGetName()));
    $modpath = ($modinfo['type'] == 3) ? 'system' : 'modules';
    $file = DataUtil::formatForOS("$modpath/$modinfo[directory]/lang/$lang/manual.html");
    $man_link = '';
    if (file_exists($file) && is_readable($file)) {
        PageUtil::setVar('javascript', 'javascript/openwindow.js');
        $man_link = '<div style="margin-top: 20px; text-align:center">[ <a href="javascript:openwindow(\'' . $file . '\')">'.__('Online manual').'</a> ]</div>'."\n";
    }
    return $man_link;
}
