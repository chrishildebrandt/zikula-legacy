<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2004, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: function.pnmodfunc.php 28078 2010-01-09 07:11:03Z drak $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula_Template_Plugins
 * @subpackage Functions
 */

/**
 * Smarty function to to execute a module function
 *
 * This function calls a specific module function.  It returns whatever the return
 * value of the resultant function is if it succeeds.
 * Note that in contrast to the API function pnModFunc you need not to load the
 * module with pnModLoad.
 *
 *
 * Available parameters:
 *   - modname:  The well-known name of a module to execute a function from (required)
 *   - type:     The type of function to execute; currently one of 'user' or 'admin' (default is 'user')
 *   - func:     The name of the module function to execute (default is 'main')
 *   - assign:   If set, the results are assigned to the corresponding variable instead of printed out
 *   - all remaining parameters are passed to the module function
 *
 * Example
 * <!--[pnmodfunc modname='News' type='user' func='view']-->
 *
 * @author       Andreas Stratmann
 * @author       J�rg Napp
 * @since        03/05/23
 * @see          function.pnmodapifunc.php::smarty_function_pnmodapifunc()
 * @param        array       $params      All attributes passed to this function from the template
 * @param        object      &$smarty     Reference to the Smarty object
 * @return       string      the results of the module function
 */
function smarty_function_pnmodfunc($params, &$smarty)
{
    $saveDomain = $smarty->renderDomain;
    $assign  = isset($params['assign'])                  ? $params['assign']  : null;
    $func    = isset($params['func']) && $params['func'] ? $params['func']    : 'main';
    $modname = isset($params['modname'])                 ? $params['modname'] : null;
    $type    = isset($params['type']) && $params['type'] ? $params['type']    : 'user';
    $return  = isset($params['return'])                  ? $params['return']  : null;

    // avoid passing these to pnModFunc
    unset($params['modname']);
    unset($params['type']);
    unset($params['func']);
    unset($params['assign']);

    if (!$modname) {
        $smarty->trigger_error(__f('Error! in %1$s: the %2$s parameter must be specified.', array('pnmodfunc', 'modname')));
        return false;
    }

    if (isset($params['modnamefunc'])) {
        $params['modname'] = $params['modnamefunc'];
        unset($params['modnamefunc']);
    }

    $result = pnModFunc($modname, $type, $func, $params);
    if (is_array($result)) {
        $pnRender = & pnRender::getInstance($modname);
        $pnRender->assign($result);
        if (isset($return['template'])) {
            echo $pnRender->fetch($return['template']);
        } else {
            $modname = strtolower($modname);
            $type = strtolower($type);
            $func = strtolower($func);
            $result = $pnRender->fetch("{$modname}_{$type}_{$func}.htm");
        }
    }

    // ensure the renderDomain wasnt overwritten
    $smarty->renderDomain = $saveDomain;

    if ($assign) {
        $smarty->assign($assign, $result);
    } else {
        return $result;
    }
}
