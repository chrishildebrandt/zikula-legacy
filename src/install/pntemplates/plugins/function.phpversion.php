<?php
/**
 * pnRender plugin
 *
 * This file is a plugin for pnRender, the Zikula implementation of Smarty
 *
 * @package      Xanthia_Templating_Environment
 * @subpackage   pnRender
 * @version      $Id: function.phpversion.php 24342 2008-06-06 12:03:14Z markwest $
 * @author       The Zikula development team
 * @link         http://www.zikula.org  The Zikula Home Page
 * @copyright    Copyright (C) 2002 by the Zikula Development Team
 * @license      http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

function smarty_function_phpversion($params, &$smarty)
{
    if (isset($params['assign'])) {
        $smarty->assign($params['assign'], phpversion());
    } else {
        return phpversion();
    }
}
