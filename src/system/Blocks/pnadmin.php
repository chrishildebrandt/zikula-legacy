<?php
/**
 * Zikula Application Framework
 * @copyright (c) 2001, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: pnadmin.php 28210 2010-02-05 03:32:15Z drak $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula_System_Modules
 * @subpackage Blocks
 */

/**
 * the main administration function
 *
 * view() function)
 * @author Jim McDonald
 * @return string HTML output string
 */
function blocks_admin_main()
{
    // Security check will be done in view()
    return pnRedirect(pnModURL('Blocks', 'admin', 'view'));
}

/**
 * View all blocks
 * @author Jim McDonald
 * @return string HTML output string
 */
function blocks_admin_view()
{
    // Security check
    if (!SecurityUtil::checkPermission('Blocks::', '::', ACCESS_EDIT)) {
        return LogUtil::registerPermissionError();
    }

    // Create output object
    $pnRender = & pnRender::getInstance('Blocks', false);

    // generate an authorisation key for the links
    $authid = SecurityUtil::generateAuthKey();

    // set some default variables
    $rownum = 1;
    $lastpos = '';

    // Get all blocks
    $blocks = pnModAPIFunc('Blocks', 'user', 'getall');

    // we can easily count the number of blocks using count() rather than
    // calling the api function
    $numrows = count($blocks);

    // create an empty arrow to hold the processed items
    $blockitems = array();

    // get all possible block positions
    $blockspositions = pnModAPIFunc('Blocks', 'user', 'getallpositions');
    // build assoc array for easier usage later on
    foreach($blockspositions as $blocksposition) {
        $allbposarray[$blocksposition['pid']] = $blocksposition['name'];
    }
    // loop round each item calculating the additional information
    foreach ($blocks as $key => $block) {

        // set the module that holds the block
        if ($block['mid'] == 0) {
            $block['modname'] = 'Legacy';
        } else {
            $modinfo = pnModGetInfo($block['mid']);
            $block['modname'] = $modinfo['displayname'];
        }

        // set the blocks language
        if (empty($block['language'])) {
            $block['language'] = __('All');
        } else {
            $block['language'] = ZLanguage::getLanguageName($block['language']);
        }

        $thisblockspositions = pnModAPIFunc('Blocks', 'user', 'getallblockspositions', array('bid' => $block['bid']));
        $bposarray = array();
        foreach($thisblockspositions as $singleblockposition){
            $bposarray[] = $allbposarray[$singleblockposition['pid']];
        }
        $block['positions'] = implode(', ', $bposarray);
        unset($bposarray);

        // calculate what options the user has over this block
        $block['options'] = array();
        if ($block['active']) {
            $block['options'][] = array('url' => pnModURL('Blocks', 'admin', 'deactivate',
                                                 array('bid' => $block['bid'], 'authid' => $authid)),
                                        'image' => 'folder_grey.gif',
                                        'title' => __('Deactivate'),
                                        'noscript' => true);
        } else {
            $block['options'][] = array ('url' => pnModURL('Blocks', 'admin', 'activate',
                                                  array('bid' => $block['bid'], 'authid' => $authid)),
                                         'image' => 'folder_green.gif',
                                         'title' => __('Activate'),
                                         'noscript' => true);
        }

        $block['options'][] = array('url' => pnModURL('Blocks', 'admin', 'modify', array('bid' => $block['bid'])),
                                    'image' => 'xedit.gif',
                                    'title' => __('Edit'),
                                    'noscript' => false);
        $block['options'][] = array('url' => pnModURL('Blocks', 'admin', 'delete', array('bid' => $block['bid'])),
                                    'image' => '14_layer_deletelayer.gif',
                                    'title' => __('Delete'),
                                    'noscript' => false);

        $blocksitems[] = $block;

    }
    $pnRender->assign('blocks', $blocksitems);

    // get the block positions
    $items = pnModAPIFunc('Blocks', 'user', 'getallpositions');

    // Loop through each returned item adding in the options that the user has over the item
    foreach ($items as $key => $item) {
        if (SecurityUtil::checkPermission('Blocks::', "$item[name]::", ACCESS_READ)) {
            $options = array();
            if (SecurityUtil::checkPermission('Blocks::', "$item[name]::$", ACCESS_EDIT)) {
                $options[] = array('url'   => pnModURL('Blocks', 'admin', 'modifyposition', array('pid' => $item['pid'])),
                                   'image' => 'xedit.gif',
                                   'title' => __('Edit'));
                if (SecurityUtil::checkPermission('Blocks::', "$item[name]::", ACCESS_DELETE)) {
                    $options[] = array('url'   => pnModURL('Blocks', 'admin', 'deleteposition', array('pid' => $item['pid'])),
                                       'image' => '14_layer_deletelayer.gif',
                                       'title' => __('Delete'));
                }
            }
                 // Add the calculated menu options to the item array
            $items[$key]['options'] = $options;
        }
    }

    // Assign the items to the template
    ksort($items);
    $pnRender->assign('positions', $items);

    // Return the output that has been generated by this function
    return $pnRender->fetch('blocks_admin_view.htm');
}

/**
 * show all blocks
 * @author Jim McDonald
 * @return string HTML output string
 */
function blocks_admin_showall()
{
    SessionUtil::setVar('blocks_show_all', 1);
    return pnRedirect(pnModURL('Blocks', 'admin', 'view'));
}

/**
 * show active blocks
 * @author Jim McDonald
 * @return string HTML output string
 */
function blocks_admin_showactive()
{
    SessionUtil::delVar('blocks_show_all');
    return pnRedirect(pnModURL('Blocks', 'admin', 'view'));
}

/**
 * deactivate a block
 * @author Jim McDonald
 * @param int $bid block id
 * @return string HTML output string
 */
function blocks_admin_deactivate()
{
    // Get parameters
    $bid = FormUtil::getPassedValue('bid');

    // Confirm authorisation code
    if (!SecurityUtil::confirmAuthKey()) {
        return LogUtil::registerAuthidError(pnModURL('Blocks','admin','view'));
    }

    // Pass to API
    if (pnModAPIFunc('Blocks', 'admin', 'deactivate', array('bid' => $bid))) {
        // Success
        LogUtil::registerStatus(__('Done! Block now inactive.'));
    }

    // Redirect
    return pnRedirect(pnModURL('Blocks', 'admin', 'view'));
}

/**
 * activate a block
 * @author Jim McDonald
 * @param int $bid block id
 * @return string HTML output string
 */
function blocks_admin_activate()
{
    // Get parameters
    $bid = FormUtil::getPassedValue('bid');

    // Confirm authorisation code
    if (!SecurityUtil::confirmAuthKey()) {
        return LogUtil::registerAuthidError(pnModURL('Blocks','admin','view'));
    }

    // Pass to API
    if (pnModAPIFunc('Blocks', 'admin', 'activate', array('bid' => $bid))) {
        // Success
        LogUtil::registerStatus(__('Done! Block now active.'));
    }

    // Redirect
    return pnRedirect(pnModURL('Blocks', 'admin', 'view'));
}

/**
 * modify a block
 * @author Jim McDonald
 * @param int $bid block ind
 * @return string HTML output string
 */
function blocks_admin_modify()
{
    // Get parameters
    $bid = FormUtil::getPassedValue('bid');

    // Get details on current block
    $blockinfo = pnBlockGetInfo($bid);

    // Security check
    if (!SecurityUtil::checkPermission('Blocks::', "$blockinfo[bkey]:$blockinfo[title]:$blockinfo[bid]", ACCESS_EDIT)) {
        return LogUtil::registerPermissionError();
    }

    // check the blockinfo array
    if (empty($blockinfo)) {
        return LogUtil::registerError(__('Sorry! No such block found.'), 404);
    }

    // get the block placements
    $where = "WHERE pn_bid = '" . DataUtil::formatForStore($bid) . "'";
    $placements = DBUtil::selectObjectArray('block_placements', $where, 'pn_order', -1, -1, '', null);
    $blockinfo['placements']  = array();
    foreach ($placements as $placement) {
        $blockinfo['placements'][] = $placement['pid'];
    }

    // Load block
    $modinfo = pnModGetInfo($blockinfo['mid']);
    if (!pnBlockLoad($modinfo['name'], $blockinfo['bkey'])) {
        return LogUtil::registerError(__('Sorry! No such block found.'), 404);
    }

    // Create output object
    $pnRender = & pnRender::getInstance('Blocks', false);
    $pnRender->add_core_data();

    // Title - putting a title ad the head of each page reminds the user what
    // they are doing
    if (!empty($modinfo['name'])) {
        $pnRender->assign('modtitle', "$modinfo[name]/$blockinfo[bkey]");
    } else {
        $pnRender->assign('modtitle', "Core/$blockinfo[bkey]");
        $modinfo['name'] = 'Legacy';
    }

    // Add hidden block id to form
    $pnRender->assign('bid', $bid);

    // check for a valid set of filtering rules
    if (!isset($blockinfo['filter']) || empty($blockinfo['filter'])) {
        $blockinfo['filter']['modules'] = array();
        $blockinfo['filter']['type'] = '';
        $blockinfo['filter']['functions'] = '';
        $blockinfo['filter']['customargs'] = '';
    }

    // invert the filter array so that the output is in a useful form for the template
    if (isset($blockinfo['filter']['modules']) && is_array($blockinfo['filter']['modules'])) {
        $blockinfo['filter']['modules'] = array_flip($blockinfo['filter']['modules']);
    }

    // assign the block
    $pnRender->assign($blockinfo);

    // assign the list of modules
    $pnRender->assign('mods', pnModGetAllMods());

    // assign block positions
    $positions = pnModAPIFunc('Blocks', 'user', 'getallpositions');
    $block_positions = array();
    foreach ($positions as $position) {
        $block_positions[$position['pid']] = $position['name'];
    }
    $pnRender->assign('block_positions', $block_positions);

    // Block-specific

    // New way
    $usname = preg_replace('/ /', '_', $modinfo['name']);
    $modfunc = $usname . '_' . $blockinfo['bkey'] . 'block_modify';
    $blockoutput = '';
    if (function_exists($modfunc)) {
        $blockoutput = $modfunc($blockinfo);
    } else {
        // Old way
        $blocks_modules = $GLOBALS['blocks_modules'][$blockinfo['mid']];
        if (!empty($blocks_modules[$blockinfo['bkey']]) && !empty($blocks_modules[$blockinfo['bkey']]['func_edit'])) {
            if (function_exists($blocks_modules[$blockinfo['bkey']]['func_edit'])) {
                $blockoutput = $blocks_modules[$blockinfo['bkey']]['func_edit'](array_merge($_GET, $_POST, $blockinfo));
            }
        }
    }

    // the blocks will have reset the renderDomain property (bad singleton design) - drak
    $pnRender->renderDomain = null;

    if (!isset($GLOBALS['blocks_modules'][$blockinfo['mid']][$blockinfo['bkey']]['admin_tableless'])) {
        $GLOBALS['blocks_modules'][$blockinfo['mid']][$blockinfo['bkey']]['admin_tableless'] = false;
    }
    $pnRender->assign($GLOBALS['blocks_modules'][$blockinfo['mid']][$blockinfo['bkey']]);
    $pnRender->assign('blockoutput', $blockoutput);

    // Refresh
    $refreshtimes = array( 1800 => __('Half an hour'),
                           3600 => __('One hour'),
                           7200 => __('Two hours'),
                          14400 => __('Four hours'),
                          43200 => __('Twelve hours'),
                          86400 => __('One day'),
                         172800 => __('Two days'),
                         259200 => __('Three days'),
                         345600 => __('Four days'),
                         432000 => __('Five days'),
                         518400 => __('Six days'),
                         604800 => __('Seven days'));
    $pnRender->assign('blockrefreshtimes' , $refreshtimes);

    // Return the output that has been generated by this function
    return $pnRender->fetch('blocks_admin_modify.htm');
}

/**
 * update a block
 * @author Jim McDonald
 * @see blocks_admin_modify()
 * @param int $bid block id to update
 * @param string $title the new title of the block
 * @param array $positions the new position(s) of the block
 * @param array $modules the modules to display the block on
 * @param string $url the new URL of the block
 * @param string $language the new language of the block
 * @param string $content the new content of the block
 * @return bool true if succesful, false otherwise
 */
function blocks_admin_update()
{
    // Get parameters
    $bid           = FormUtil::getPassedValue('bid');
    $title         = FormUtil::getPassedValue('title');
    $language      = FormUtil::getPassedValue('language');
    $collapsable   = FormUtil::getPassedValue('collapsable', 0);
    $defaultstate  = FormUtil::getPassedValue('defaultstate', 1);
    $content       = FormUtil::getPassedValue('content');
    $refresh       = FormUtil::getPassedValue('refresh');
    $positions     = FormUtil::getPassedValue('positions');
    $filter        = FormUtil::getPassedValue('filter', array());
    $returntoblock = FormUtil::getPassedValue('returntoblock');
    // not stored in a block
    $redirect      = FormUtil::getPassedValue('redirect', null);
    $cancel        = FormUtil::getPassedValue('cancel', null);
    if (isset($cancel)) {
        if (isset($redirect) && !empty($redirect)) {
            return pnRedirect(urldecode($redirect));
        }
        return pnRedirect(pnModURL('Blocks', 'admin', 'view'));
    }


    // Fix for null language
    if (!isset($language)) {
        $language = '';
    }

    // Confirm authorisation code
    if (!SecurityUtil::confirmAuthKey()) {
        return LogUtil::registerAuthidError(pnModURL('Blocks','admin','view'));
    }

    // Get and update block info
    $blockinfo = pnBlockGetInfo($bid);
    $blockinfo['title'] = $title;
    $blockinfo['bid'] = $bid;
    $blockinfo['language'] = $language;
    $blockinfo['collapsable'] = $collapsable;
    $blockinfo['defaultstate'] = $defaultstate;
    $blockinfo['content'] = $content;
    $blockinfo['refresh'] = $refresh;
    $blockinfo['positions'] = $positions;
    $blockinfo['filter'] = $filter;

    // Load block
    $modinfo = pnModGetInfo($blockinfo['mid']);
    if (!pnBlockLoad($modinfo['name'], $blockinfo['bkey'])) {
        return LogUtil::registerError(__('Sorry! No such block found.'), 404);
    }

    // Do block-specific update
    if (empty($modinfo['name'])) {
        $modinfo['name'] = 'Legacy';
    }
    $usname = preg_replace('/ /', '_', $modinfo['name']);
    $updatefunc = $usname . '_' . $blockinfo['bkey'] . 'block_update';
    if (function_exists($updatefunc)) {
        $blockinfo = $updatefunc($blockinfo);
        if (!$blockinfo) {
            return pnRedirect(pnModURL('Blocks', 'admin', 'modify', array('bid' => $bid)));
        }
    } else {
        // Old way
        $blocks_modules = $GLOBALS['blocks_modules'][$blockinfo['mid']];
        if (!empty($blocks_modules[$blockinfo['bkey']]) && !empty($blocks_modules[$blockinfo['bkey']]['func_update'])) {
            if (function_exists($blocks_modules[$blockinfo['bkey']]['func_update'])) {
                $blockinfo = $blocks_modules[$blockinfo['bkey']]['func_update'](array_merge($_POST, $blockinfo));
            }
        }
    }

    // Pass to API
    if (pnModAPIFunc('Blocks', 'admin', 'update', $blockinfo)) {
        // Success
        LogUtil::registerStatus(__('Done! Saved blocks.'));
    }

    if (isset($redirect) && !empty($redirect)) {
        return pnRedirect(urldecode($redirect));
    }

    if (!empty($returntoblock)) {
        // load the block config again
        return pnRedirect(pnModURL('Blocks', 'admin', 'modify',
                                   array('bid' => $returntoblock)));
    }
    return pnRedirect(pnModURL('Blocks', 'admin', 'view'));
}

/**
 * display form for a new block
 * @author Jim McDonald
 * @return string HTML output string
 */
function blocks_admin_new()
{
    // Security check
    if (!SecurityUtil::checkPermission('Blocks::', '::', ACCESS_ADD)) {
        return LogUtil::registerPermissionError();
    }

    // Create output object
    $pnRender = & pnRender::getInstance('Blocks', false);
    $pnRender->add_core_data();

    // Block
    // Load all blocks (trickier than it sounds)
    $blocks = pnBlockLoadAll();
    if (!$blocks) {
        return LogUtil::registerError(__('Error! Could not load blocks.'));
    }

    $blockinfo = array();
    foreach ($blocks as $moduleblocks) {
        foreach ($moduleblocks as $block) {
            $modinfo = pnModGetInfo(pnModGetIDFromName($block['module']));
            if (!$modinfo) {
                $modinfo = array('displayname' => __('Legacy'));
            }
            $blockinfo[$block['mid'] . ':' . $block['bkey']] =   $modinfo['displayname'] . '/' . $block['text_type_long'];
        }
    }
    $pnRender->assign('blockids', $blockinfo);

    // assign block positions
    $positions = pnModAPIFunc('Blocks', 'user', 'getallpositions');
    $block_positions = array();
    foreach ($positions as $position) {
        $block_positions[$position['pid']] = $position['name'];
    }
    $pnRender->assign('block_positions', $block_positions);

    // Return the output that has been generated by this function
    return $pnRender->fetch('blocks_admin_new.htm');
}

/**
 * create a new block
 * @author Jim McDonald
 * @see blocks_admin_new()
 * @param string $title the new title of the block
 * @param int $blockid block id to create
 * @param string $language the language to assign to the block
 * @param string $position the position of the block
 * @return bool true if successful, false otherwise
 */
function blocks_admin_create()
{
    // Get parameters
    $title        = FormUtil::getPassedValue('title');
    $blockid      = FormUtil::getPassedValue('blockid');
    $language     = FormUtil::getPassedValue('language');
    $collapsable   = FormUtil::getPassedValue('collapsable', 0);
    $defaultstate  = FormUtil::getPassedValue('defaultstate', 1);
    $positions     = FormUtil::getPassedValue('positions');

    list($mid, $bkey) = explode(':', $blockid);

    // Fix for null language
    if (!isset($language)) {
        $language = '';
    }

    // Confirm authorisation code
    if (!SecurityUtil::confirmAuthKey()) {
        return LogUtil::registerAuthidError(pnModURL('Blocks','admin','view'));
    }

    $blockinfo = array('bkey'         => $bkey,
                       'title'        => $title,
                       'positions'    => $positions,
                       'mid'          => $mid,
                       'language'     => $language,
                       'collapsable'  => $collapsable,
                       'defaultstate' => $defaultstate);

    // Pass to API
    $bid = pnModAPIFunc('Blocks', 'admin', 'create', $blockinfo);
    if ($bid != false) {
        LogUtil::registerStatus(__('Done! Created block.'));
        return pnRedirect(pnModURL('Blocks', 'admin', 'modify', array('bid' => $bid)));
    }

    return pnRedirect(pnModURL('Blocks', 'admin', 'view'));
}

/**
 * delete a block
 * @author Jim McDonald
 * @param int bid the block id
 * @param bool confirm to delete block
 * @return string HTML output string
 */
function blocks_admin_delete()
{
    // Get parameters
    $bid          = FormUtil::getPassedValue('bid');
    $confirmation = FormUtil::getPassedValue('confirmation');

    // Get details on current block
    $blockinfo = pnBlockGetInfo($bid);

    // Security check
    if (!SecurityUtil::checkPermission('Blocks::', "$blockinfo[bkey]:$blockinfo[title]:$blockinfo[bid]", ACCESS_DELETE)) {
        return LogUtil::registerPermissionError();
    }

    if ($blockinfo == false) {
        return LogUtil::registerError(__('Sorry! No such block found.'), 404);
    }

    // Check for confirmation
    if (empty($confirmation)) {
        // No confirmation yet - get one
        // Create output object
        $pnRender = & pnRender::getInstance('Blocks', false);

        // get the module info
        $modinfo = pnModGetInfo($blockinfo['mid']);

        if (!empty($modinfo['name'])) {
            $pnRender->assign('blockname', "$modinfo[name]/$blockinfo[bkey]");
        } else {
            $pnRender->assign('blockname', "Core/$blockinfo[bkey]");
        }

        // add the block id
        $pnRender->assign('bid', $bid);

        // Return the output that has been generated by this function
        return $pnRender->fetch('blocks_admin_delete.htm');
    }

    // Confirm authorisation code
    if (!SecurityUtil::confirmAuthKey()) {
        return LogUtil::registerAuthidError(pnModURL('Blocks','admin','view'));
    }

    // Pass to API
    if (pnModAPIFunc('Blocks', 'admin', 'delete',
                     array('bid' => $bid))) {
        // Success
        LogUtil::registerStatus(__('Done! Deleted block.'));
    }

    return pnRedirect(pnModURL('Blocks', 'admin', 'view'));
}

/**
 * display a form to create a new block position
 *
 * @author Mark West
 */
function blocks_admin_newposition()
{
    // Security check
    if (!SecurityUtil::checkPermission('Blocks::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // Create output object
    $pnRender = & pnRender::getInstance('Blocks', false);

    // Return the output that has been generated by this function
    return $pnRender->fetch('blocks_admin_newposition.htm');
}

/**
 * display a form to create a new block position
 *
 * @author Mark West
 */
function blocks_admin_createposition()
{
    // Security check
    if (!SecurityUtil::checkPermission('Blocks::position', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // Get parameters
    $position = FormUtil::getPassedValue('position');

    // check our vars
    if (!isset($position['name']) || !preg_match('/^[a-z0-9_-]*$/i', $position['name']) || !isset($position['description'])) {
        return LogUtil::registerArgsError(pnModURL('Blocks', 'admin', 'view'));
    }

    // Confirm authorisation code
    if (!SecurityUtil::confirmAuthKey()) {
        return LogUtil::registerAuthidError(pnModURL('Blocks','admin','view'));
    }

    // add the new block position
    if (pnModAPIFunc('Blocks', 'admin', 'createposition', array('name' => $position['name'], 'description' => $position['description']))) {
        LogUtil::registerStatus(__('Done! Created block.'));
    }

    // all done
    return pnRedirect(pnModURL('Blocks', 'admin', 'view'));
}

/**
 * display a form to create a new block position
 *
 * @author Mark West
 */
function blocks_admin_modifyposition()
{
    // get our input
    $pid = FormUtil::getPassedValue('pid');

    // get the block position
    $position = pnModAPIFunc('Blocks', 'user', 'getposition', array('pid' => $pid));

    // Security check
    if (!SecurityUtil::checkPermission("Blocks::$position[name]", '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // Create output object
    $pnRender = & pnRender::getInstance('Blocks', false);

    // assign the item
    $pnRender->assign($position);

    // get all blocks in the position
    $block_placements = pnModAPIFunc('blocks', 'user', 'getblocksinposition', array('pid' => $pid));

    // get all defined blocks
    $allblocks = pnModAPIFunc('Blocks', 'user', 'getall', array('inactive' => true));
    foreach($allblocks as $key => $allblock) {
        // set the module that holds the block
        if ($allblock['mid'] == 0) {
            $allblocks[$key]['modname'] = 'Legacy';
        } else {
            $modinfo = pnModGetInfo($allblock['mid']);
            $allblocks[$key]['modname'] = $modinfo['name'];
        }
    }


    // loop over arrays forming a list of blocks not in the block positon and obtaining
    // full details on those that are
    $blocks = array();
    foreach ($block_placements as $blockplacement) {
        $block = pnBlockGetInfo($blockplacement['bid']);
        $block['order'] = $blockplacement['order'];
        foreach($allblocks as $key => $allblock) {
            if ($allblock['bid'] == $blockplacement['bid']) {
                unset($allblocks[$key]);
                $block['modname'] = $allblock['modname'];
            }
        }
        $blocks[] = $block;
    }

    $pnRender->assign('assignedblocks', $blocks);
    $pnRender->assign('unassignedblocks', $allblocks);

    // Return the output that has been generated by this function
    return $pnRender->fetch('blocks_admin_modifyposition.htm');
}

/**
 * display a form to create a new block position
 *
 * @author Mark West
 */
function blocks_admin_updateposition()
{
    // Get parameters
    $position = FormUtil::getPassedValue('position');

    // check our vars
    if (!isset($position['pid']) || !isset($position['name']) || !isset($position['description'])) {
        return LogUtil::registerArgsError(pnModURL('Blocks', 'admin', 'view'));
    }

    // Confirm authorisation code
    if (!SecurityUtil::confirmAuthKey()) {
        return LogUtil::registerAuthidError(pnModURL('Blocks','admin','view'));
    }

    // update the position
    if (pnModAPIFunc('Blocks', 'admin', 'updateposition',
                     array('pid' => $position['pid'], 'name' => $position['name'], 'description' => $position['description']))) {
        // all done
        LogUtil::registerStatus(__('Done! Saved block.'));
    }

    return pnRedirect(pnModURL('Blocks', 'admin', 'view'));
}

/**
 * delete a block position
 *
 * @author Mark West
 * @param int $args['pid'] the id of the position to be deleted
 * @param int $args['objectid'] generic object id maps to pid if present
 * @param bool $args['confirmation'] confirmation that this item can be deleted
 * @return mixed HTML string if confirmation is null, true if delete successful, false otherwise
 */
function Blocks_admin_deleteposition($args)
{
    $pid = FormUtil::getPassedValue('pid', isset($args['pid']) ? $args['pid'] : null, 'REQUEST');
    $objectid = FormUtil::getPassedValue('objectid', isset($args['objectid']) ? $args['objectid'] : null, 'REQUEST');
    $confirmation = FormUtil::getPassedValue('confirmation', null, 'POST');
    if (!empty($objectid)) {
        $pid = $objectid;
    }

    $item = pnModAPIFunc('Blocks', 'user', 'getposition', array('pid' => $pid));

    if ($item == false) {
        return LogUtil::registerError(__('Error! No such block position found.'), 404);
    }

    if (!SecurityUtil::checkPermission('Blocks::position', "$item[name]::$pid", ACCESS_DELETE)) {
        return LogUtil::registerPermissionError();
    }

    // Check for confirmation.
    if (empty($confirmation)) {
        // No confirmation yet
        $pnRender = & pnRender::getInstance('Blocks', false);
        $pnRender->assign('pid', $pid);
        return $pnRender->fetch('blocks_admin_deleteposition.htm');
    }

    if (!SecurityUtil::confirmAuthKey()) {
        return LogUtil::registerAuthidError(pnModURL('Blocks','admin','view'));
    }

    if (pnModAPIFunc('Blocks', 'admin', 'deleteposition', array('pid' => $pid))) {
        // Success
        LogUtil::registerStatus(__('Done! Deleted block position.'));
    }

    return pnRedirect(pnModURL('Blocks', 'admin', 'view'));
}

/**
 * Any config options would likely go here in the future
 * @author Jim McDonald
 * @return string HTML output string
 */
function blocks_admin_modifyconfig()
{
    // Security check
    if (!SecurityUtil::checkPermission('Blocks::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // Create output object
    $pnRender = & pnRender::getInstance('Blocks', false);

    // assign all the module vars
    $pnRender->assign(pnModGetVar('Blocks'));

    // Return the output that has been generated by this function
    return $pnRender->fetch('blocks_admin_modifyconfig.htm');
}

/**
 * Set config variable(s)
 * @author Jim McDonald
 * @return string bool true if successful, false otherwise
 */
function blocks_admin_updateconfig()
{
    // Security check
    if (!SecurityUtil::checkPermission('Blocks::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    $collapseable = FormUtil::getPassedValue('collapseable');

    if (!SecurityUtil::confirmAuthKey()) {
        return LogUtil::registerAuthidError(pnModURL('Blocks','admin','main'));
    }

    if (!isset($collapseable) || !is_numeric($collapseable)) {
        $collapseable = 0;
    }

    pnModSetVar('Blocks', 'collapseable', $collapseable);

    // Let any other modules know that the modules configuration has been updated
    pnModCallHooks('module','updateconfig','Blocks', array('module' => 'Blocks'));

    // the module configuration has been updated successfuly
    LogUtil::registerStatus(__('Done! Saved module configuration.'));

    return pnRedirect(pnModURL('Blocks', 'admin', 'main'));
}

