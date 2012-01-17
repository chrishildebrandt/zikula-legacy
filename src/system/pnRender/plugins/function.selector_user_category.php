<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2004, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: function.selector_user_category.php 27274 2009-10-30 13:49:20Z mateo $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @author Robert Gasch
 * @package Zikula_Template_Plugins
 * @subpackage Functions
 */

/**
 * Available parameters:
 *   - btnText:  If set, the results are assigned to the corresponding variable instead of printed out
 *   - cid:      category ID
 *
 * Example
 * <!--[selector_user_category cid="1" assign="category"]-->
 *
 */
function smarty_function_selector_user_category($params, &$smarty)
{
    $field            = isset($params['field'])            ? $params['field']            : 'id';
    $selectedValue    = isset($params['selectedValue'])    ? $params['selectedValue']    : 0;
    $defaultValue     = isset($params['defaultValue'])     ? $params['defaultValue']     : 0;
    $defaultText      = isset($params['defaultText'])      ? $params['defaultText']      : '';
    $lang             = isset($params['lang'])             ? $params['lang']             : ZLanguage::getLanguageCode();
    $name             = isset($params['name'])             ? $params['name']             : 'defautlselectorname';
    $recurse          = isset($params['recurse'])          ? $params['recurse']          : true;
    $relative         = isset($params['relative'])         ? $params['relative']         : true;
    $includeRoot      = isset($params['includeRoot'])      ? $params['includeRoot']      : false;
    $includeLeaf      = isset($params['includeLeaf'])      ? $params['includeLeaf']      : true;
    $all              = isset($params['all'])              ? $params['all']              : false;
    $displayPath      = isset($params['displayPath'])      ? $params['displayPath']      : false;
    $attributes       = isset($params['attributes'])       ? $params['attributes']       : null;
    $assign           = isset($params['assign'])           ? $params['assign']           : null;
    $editLink         = isset($params['editLink'])         ? $params['editLink']         : true;
    $submit           = isset($params['submit'])           ? $params['submit']           : false;
    $multipleSize     = isset($params['multipleSize'])     ? $params['multipleSize']     : 1;
    $doReplaceRootCat = false;

    Loader::loadClass('CategoryUtil');

    $userCats= pnModAPIFunc ('Categories', 'user', 'getusercategories', array('returnCategory'=>1, 'relative'=>$relative));
    $html = CategoryUtil::getSelector_Categories ($userCats, $field, $selectedValue, $name, $defaultValue, $defaultText,
                                                  $submit, $displayPath, $doReplaceRootCat, $multipleSize);

    if ($editLink && $allowUserEdit && pnUserLoggedIn() && SecurityUtil::checkPermission( 'Categories::', "$category[id]::", ACCESS_EDIT)) {
        $url = pnModURL ('Categories', 'user', 'edituser');
        $html .= "&nbsp;&nbsp;<a href=\"$url\">" . __('Edit sub-categories') . '</a>';
    }

    if ($assign) {
        $smarty->assign($assign, $html);
    } else {
        return $html;
    }
}
