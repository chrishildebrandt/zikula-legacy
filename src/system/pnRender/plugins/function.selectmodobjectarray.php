<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2008, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: function.selectmodobjectarray.php 27274 2009-10-30 13:49:20Z mateo $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @author Axel Guckelsberger
 * @package Zikula_Template_Plugins
 * @subpackage Functions
 */

/**
 * render plugin for fetching a list of module objects
 *
 * Examples
 *   <!--[selectmodobjectarray module="AutoCustomer" objecttype="customer" assign="myCustomers"]-->
 *   <!--[selectmodobjectarray module="AutoCocktails" objecttype="recipe" orderby="name desc" assign="myRecipes"]-->
 *
 * @param    module     string              name of the module storing the desired object
 * @param    objecttype string              name of object type
 * @param    where      string              filter value
 * @param    orderby    string              sorting field and direction
 * @param    pos        int                 start offset
 * @param    num        int                 amount of selected objects
 * @param    prefix     string              optional prefix for class names (defaults to PN)
 * @param    assign     string              name of the returned object
 */
function smarty_function_selectmodobjectarray($params, &$smarty)
{
    if (!isset($params['module']) || empty($params['module'])) {
        $smarty->trigger_error(__f('Error! in %1$s: the %2$s parameter must be specified.', array('selectmodobjectarray', 'module')));
    }
    if (!isset($params['objecttype']) || empty($params['objecttype'])) {
        $smarty->trigger_error(__f('Error! in %1$s: the %2$s parameter must be specified.', array('selectmodobjectarray', 'objecttype')));
    }
    if (!isset($params['prefix'])) {
        $params['prefix'] = 'PN';
    }
    if (!isset($params['assign'])) {
        $smarty->trigger_error(__f('Error! in %1$s: the %2$s parameter must be specified.', array('selectmodobjectarray', 'assign')));
    }
    if (!pnModAvailable($params['module'])) {
        $smarty->trigger_error(__f('Invalid %1$s passed to %2$s.', array('module', 'selectmodobjectarray')));
    }

    pnModDBInfoLoad($params['module']);

    // load the object class corresponding to $params['objecttype']
    if (!($class = Loader::loadArrayClassFromModule($params['module'], $params['objecttype'], false, $params['prefix']))) {
        pn_exit(__f('Error! Cannot load module array class %1$s for module %2$s.', array(DataUtil::formatForDisplay($params['module']), DataUtil::formatForDisplay($params['objecttype']))));
    }

    // instantiate the object-array
    $objectArray = new $class();

    // convenience vars to make code clearer
    $where = $sort = '';
    if (isset($params['where']) && !empty($params['where'])) {
        $where = $params['where'];
    }
    // TODO: add FilterUtil support here in 2.0

    if (isset($params['orderby']) && !empty($params['orderby'])) {
        $sort = $params['orderby'];
    }

    $pos = 1;
    if (isset($params['pos']) && !empty($params['pos']) && is_numeric($params['pos'])) {
        $pos = $params['pos'];
    }
    $num = 10;
    if (isset($params['num']) && !empty($params['num']) && is_numeric($params['num'])) {
        $num = $params['num'];
    }

    // get() returns the cached object fetched from the DB during object instantiation
    // get() with parameters always performs a new select
    // while the result will be saved in the object, we assign in to a local variable for convenience.
    $objectData = $objectArray->get($where, $sort, $pos-1, $num);

    $smarty->assign($params['assign'], $objectData);
}
