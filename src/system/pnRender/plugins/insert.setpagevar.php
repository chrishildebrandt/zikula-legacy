<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2008, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: insert.setpagevar.php 27274 2009-10-30 13:49:20Z mateo $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula_Template_Plugins
 * @subpackage Functions
 */

/**
 * Smarty function to set a single value page variable
 *
 * This function sets a page-specific variable from the Zikula system. Only single value pagevars are supported by
 * this insert!
 *
 * Available parameters:
 *   - var:     The name of the single value page variable to set
 *   - value:    The value of the page variable to set
 *
 * Zikula doesn't impose any restriction on the page variable's name except for duplicate
 * and reserved names. As of this writing, the list of supported names consists of
 * <ul>
 * <li>title</li>
 * <li>description</li>
 * </ul>
 *
 * Example
 *   <!--[insert name='setpagevar' var='title' value='mytitle']-->
 *
 *
 * @author       Mark West
 * @since        14/07/08
 * @param        array       $params      All attributes passed to this function from the template
 * @param        object      &$smarty     Reference to the Smarty object
 * @param        string      $var         Name of the page variable to set
 * @param        string      $value       Value of  he page variable to set
 */
function smarty_insert_setpagevar($params, &$smarty)
{
    $var   = isset($params['var'])  ? $params['var']  : null;
    $value = isset($params['value']) ? $params['value'] : null;

    if (!$var) {
        $smarty->trigger_error(__f('Error! in %1$s: the %2$s parameter must be specified.', array('setpagevar', 'var')));
        return false;
    }
    if (!$value) {
        $smarty->trigger_error(__f('Error! in %1$s: the %2$s parameter must be specified.', array('setpagevar', 'value')));
        return false;
    }

    PageUtil::setVar($var, $value);
    return;
}
