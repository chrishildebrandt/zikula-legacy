<?php
/**
 * Zikula Application Framework
 * @copyright (c) 2001, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: pnadminapi.php 27363 2009-11-02 16:40:08Z mateo $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula_System_Modules
 * @subpackage Blocks
 */

/**
 * update attributes of a block
 * @author Jim McDonald
 * @author Robert Gasch
 * @param int $args ['bid'] the ID of the block to update
 * @param string $args ['title'] the new title of the block
 * @param string $args ['positions'] the new positions of the block
 * @param string $args ['url'] the new URL of the block
 * @param string $args ['language'] the new language of the block
 * @param string $args ['content'] the new content of the block
 * @return bool true on success, false on failure
 */
function blocks_adminapi_update($args)
{
    // Optional arguments
    if (!isset($args['url'])) {
        $args['url'] = '';
    }
    if (!isset($args['content'])) {
        $args['content'] = '';
    }

    // Argument check
    if (!isset($args['bid'])          ||
        !is_numeric($args['bid'])     ||
        !isset($args['content'])      ||
        !isset($args['title'])        ||
        !isset($args['language'])     ||
        !isset($args['collapsable'])  ||
        !isset($args['defaultstate'])) {
        return LogUtil::registerArgsError();
    }

    $block = DBUtil::selectObjectByID ('blocks', $args['bid'], 'bid');

    // Security check
    // this function is called durung the init process so we have to check in _PNINSTALLVER
    // is set as alternative to the correct permission check
    if (!defined('_PNINSTALLVER') && !SecurityUtil::checkPermission('Blocks::', "$block[bkey]:$block[title]:$block[bid]", ACCESS_EDIT)) {
        return LogUtil::registerPermissionError();
    }

    $item = array('bid' => isset($args['bid']) ? $args['bid'] : $block['bid'],
                  'content' => isset($args['content']) ? $args['content'] : $block['content'],
                  'title' => isset($args['title']) ? $args['title'] : $block['title'],
                  'filter' => isset($args['filter']) ? serialize($args['filter']) : $block['filter'],
                  'url' => isset($args['url']) ? $args['url'] : $block['url'],
                  'refresh' => isset($args['refresh']) ? $args['refresh'] : $block['refresh'],
                  'language' => isset($args['language']) ? $args['language'] : $block['language'],
                  'collapsable' => isset($args['collapsable']) ? $args['collapsable'] : $block['collapsable'],
                  'defaultstate' => isset($args['defaultstate']) ? $args['defaultstate'] : $block['defaultstate']);

    $res = DBUtil::updateObject ($item, 'blocks', '', 'bid');
    if (!$res) {
        return LogUtil::registerError(__('Error! Could not save your changes.'));
    }

    // leave unchanged positions as is, delete removed positions from placements table
    // and add placement for new positions
    if (isset($args['positions'])) {
        // Get all existing block positions. We do not use the userapi function here because we need
        // an associative array for the next steps: key = pid (position id)
        $allblockspositions = DBUtil::selectObjectArray('block_positions', null, 'pid', -1, -1, 'pid', null);
        foreach ($allblockspositions as $positionid => $blockposition) {
            if(in_array($positionid, $args['positions'])) {
                // position name is present in the array submitted from the user
                $where = "WHERE pn_pid = '" . DataUtil::formatForStore($positionid) . '\'';
                $blocksinposition = DBUtil::selectObjectArray('block_placements', $where, 'pn_order', -1, -1, 'bid');
                if(array_key_exists($item['bid'], $blocksinposition)) {
                    // block is already in this position, placement did not change, this means we do nothing
                } else {
                    // add the block to the given position as last entry (max(pn_order) +1
                    $newplacement = array('pid'   => $blockposition['pid'],
                                          'bid'   => $item['bid'],
                                          'order' => count($blocksinpositions));
                    $res = DBUtil::insertObject($newplacement, 'block_placements', 'bid', true);
                    if (!$res) {
                        return LogUtil::registerError(__('Error! Could not perform the insertion.'));
                    }
                }
            } else {
                // position name is NOT present in the array submitted from the user
                // delete the block id from the placements table for this position
                $where = '(pn_bid = \'' . DataUtil::formatForStore($item['bid']) . '\' AND pn_pid = \'' . DataUtil::formatForStore($blockposition['pid']) . '\')';
                $res = DBUtil::deleteWhere('block_placements', $where);
                if (!$res) {
                    return LogUtil::registerError(__('Error! Could not save your changes.'));
                }
            }
        }
    }

    // call update hooks
    pnModCallHooks('item', 'update', $args['bid'], array('module' => 'Blocks'));

    return true;
}

/**
 * create a new block
 * @author Jim McDonald
 * @author Robert Gasch
 * @param string $block ['title'] the title of the block
 * @param int $block ['mid'] the module ID of the block
 * @param string $block ['language'] the language of the block
 * @param int $block ['bkey'] the key of the block
 * @return mixed block Id on success, false on failure
 */
function blocks_adminapi_create($args)
{
    // Argument check
    if ((!isset($args['title']))        ||
        (!isset($args['mid']))          ||
        (!isset($args['language']))     ||
        (!isset($args['collapsable']))  ||
        (!isset($args['defaultstate'])) ||
        (!isset($args['bkey']))) {
        return LogUtil::registerArgsError();
    }

    // Security check
    if (!defined('_PNINSTALLVER') && !SecurityUtil::checkPermission('Blocks::', "$args[bkey]:$args[title]:", ACCESS_ADD)) {
        return LogUtil::registerPermissionError();
    }

    // optional arguments
    if (!isset($args['content']) || !is_string($args['content'])) {
        $args['content'] = '';
    }

    $block = array('title' => $args['title'], 'language' => $args['language'], 'collapsable' => $args['collapsable'],
                   'mid' => $args['mid'], 'defaultstate' => $args['defaultstate'], 'bkey' => $args['bkey'],
                   'content' => $args['content']);

    $block['url']         = '';
    $block['filter']      = '';
    $block['active']      = 1;
    $block['refresh']     = 3600;
    $block['last_update'] = date('Y-m-d H:i:s');
    $block['active']      = 1;
    $res = DBUtil::insertObject ($block, 'blocks', 'bid');

    if (!$res) {
        return LogUtil::registerError(__('Error! Could not create the new item.'));
    }

    // empty block positions for this block
    if (isset($args['positions'])) {
        // add new block positions
        $blockplacments = array();
        foreach ($args['positions'] as $position) {
            $blockplacments[] = array('bid' => $block['bid'], 'pid' => $position);
        }
        $res = DBUtil::insertObjectArray($blockplacments, 'block_placements');
        if (!$res) {
            return LogUtil::registerError(__('Error! Could not create the new item.'));
        }
    }

    // Let other modules know we have created an item
    pnModCallHooks('item', 'create', $block['bid'], array('module' => 'Blocks'));

    return $block['bid'];
}

/**
 * Set a block's active state
 * @author Robert Gasch
 * @param int $args ['bid'] the ID of the block to deactivate
 * @return bool true on success, false on failure
 */
function blocks_adminapi_setActiveState($block)
{
    if (!isset($block['bid']) || !is_numeric($block['bid'])) {
        return LogUtil::registerArgsError();
    }
    if (!isset($block['active']) || !is_numeric($block['active'])) {
        return LogUtil::registerArgsError();
    }
    $blockinfo = pnBlockGetInfo($block['bid']);
    if (!SecurityUtil::checkPermission('Blocks::', "$blockinfo[bkey]:$blockinfo[title]:$block[bid]", ACCESS_EDIT)) {
        return LogUtil::registerPermissionError();
    }

    // create a new object to ensure that we only update the 'active' field
    $obj = array();
    $obj['bid']    = $block['bid'];
    $obj['active'] = $block['active'];
    $res = DBUtil::updateObject ($obj, 'blocks', '', 'bid');

    return $res;
}

/**
 * deactivate a block
 * @author Jim McDonald
 * @author Robert Gasch
 * @param int $args ['bid'] the ID of the block to deactivate
 * @return bool true on success, false on failure
 */
function blocks_adminapi_deactivate($args)
{
    $args['active'] = 0;
    $res = (boolean)blocks_adminapi_setActiveState ($args);

    if (!$res) {
        return LogUtil::registerError(__('Error! Could not deactivate the block.'));
    }

    return $res;
}

/**
 * activate a block
 * @author Jim McDonald
 * @author Robert Gasch
 * @param int $args ['bid'] the ID of the block to activate
 * @return bool true on success, false on failure
 */
function blocks_adminapi_activate($args)
{
    $args['active'] = 1;
    $res = (boolean)blocks_adminapi_setActiveState ($args);

    if (!$res) {
        return LogUtil::registerError(__('Error! Could not activate the block.'));
    }

    return $res;
}

/**
 * delete a block
 * @author Jim McDonald
 * @param int $args ['bid'] the ID of the block to delete
 * @return bool true on success, false on failure
 */
function blocks_adminapi_delete($args)
{
    // Argument check
    if (!isset($args['bid']) || !is_numeric($args['bid'])) {
        return LogUtil::registerArgsError();
    }

    $block = DBUtil::selectObjectByID ('blocks', $args['bid'], 'bid');

    // Security check
    if (!SecurityUtil::checkPermission('Blocks::', "$block[bkey]:$block[title]:$block[bid]", ACCESS_DELETE)) {
        return LogUtil::registerPermissionError();
    }

    // delete block placements for this block
    $res = DBUtil::deleteObjectByID('block_placements', $args['bid'], 'bid');
    if (!$res) {
        return LogUtil::registerError(__('Error! Could not perform the deletion.'));
    }

    // delete the block itself
    $res = DBUtil::deleteObjectByID ('blocks', $args['bid'], 'bid');
    if (!$res) {
        return LogUtil::registerError(__('Error! Could not perform the deletion.'));
    }

    // Let other modules know we have deleted an item
    pnModCallHooks('item', 'delete', $args['bid'], array('module' => 'Blocks'));

    return true;
}

/**
 * create a block position
 * @author Mark West
 * @param string $args['name'] name of the position
 * @param string $args['description'] description of the position
 * @return mixed position ID on success, false on failure
 */
function Blocks_adminapi_createposition($args)
{
    // Argument check
    if (!isset($args['name']) ||
        !strlen($args['name']) ||
        !isset($args['description'])) {
        return LogUtil::registerArgsError();
    }

    // Security check
    if (!defined('_PNINSTALLVER') && !SecurityUtil::checkPermission('Blocks::position', "$args[name]::", ACCESS_ADD)) {
        return LogUtil::registerPermissionError();
    }

    $positions = pnModAPIFunc('Blocks', 'user', 'getallpositions');
    if (isset($positions) && is_array($positions)) {
        foreach ($positions as $position) {
            if ($position['name'] == $args['name']) {
               return LogUtil::registerError(__('Error! There is already a block position with the name you entered.'));
            }
        }
    }

    $item = array('name' => $args['name'], 'description' => $args['description']);

    if (!DBUtil::insertObject($item, 'block_positions', 'pid')) {
        return LogUtil::registerError(__('Error! Could not create the new item.'));
    }

    // Return the id of the newly created item to the calling process
    return $item['pid'];
}

/**
 * update a block position item
 * @author Mark West
 * @param int $args['pid'] the ID of the item
 * @param sting $args['name'] name of the block position
 * @param string $args['description'] description of the block position
 * @return bool true if successful, false otherwise
 */
function Blocks_adminapi_updateposition($args)
{
    // Argument check
    if (!isset($args['pid'])           ||
        !isset($args['name'])          ||
        !isset($args['description'])) {
        return LogUtil::registerArgsError();
    }

    // Get the existing admin message
    $item = pnModAPIFunc('Blocks', 'user', 'getposition', array('pid' => $args['pid']));

    if ($item == false) {
        return LogUtil::registerError(__('Sorry! No such item found.'));
    }

    // Security check
    if (!SecurityUtil::checkPermission('Blocks::position', "$item[name]::$item[pid]", ACCESS_EDIT)) {
        return LogUtil::registerPermissionError();
    }

    // create the item array
    $item = array('pid' => $args['pid'], 'name' => $args['name'], 'description' => $args['description']);

    if (!DBUtil::updateObject($args, 'block_positions', '', 'pid')) {
        return LogUtil::registerError(__('Error! Could not save your changes.'));
    }

    // Let the calling process know that we have finished successfully
    return true;
}

/**
 * delete a block position
 * @author Mark West
 * @param int $args['pid'] ID of the position
 * @return bool true on success, false on failure
 */
function blocks_adminapi_deleteposition($args)
{
    if (!isset($args['pid']) || !is_numeric($args['pid'])) {
        return LogUtil::registerArgsError();
    }

    $item = pnModAPIFunc('Blocks', 'user', 'getposition', array('pid' => $args['pid']));

    if ($item == false) {
        return LogUtil::registerError(__('Sorry! No such item found.'));
    }

    if (!SecurityUtil::checkPermission('Blocks::position', "$item[name]::$item[pid]", ACCESS_DELETE)) {
        return LogUtil::registerPermissionError();
    }

    // Now actually delete the category
    if (!DBUtil::deleteObjectByID ('block_positions', $args['pid'], 'pid')) {
        return LogUtil::registerError(__('Error! Could not perform the deletion.'));
    }

    // Let the calling process know that we have finished successfully
    return true;
}

/**
 * get available admin panel links
 *
 * @author Mark West
 * @return array array of admin links
 */
function blocks_adminapi_getlinks()
{
    $links = array();

    if (SecurityUtil::checkPermission('Blocks::', '::', ACCESS_EDIT)) {
        $links[] = array('url' => pnModURL('Blocks', 'admin', 'view'), 'text' => __('Blocks list'));
    }
    if (SecurityUtil::checkPermission('Blocks::', '::', ACCESS_ADD)) {
        $links[] = array('url' => pnModURL('Blocks', 'admin', 'new'), 'text' => __('Create new block'));
    }
    if (SecurityUtil::checkPermission('Blocks::', '::', ACCESS_ADD)) {
        $links[] = array('url' => pnModURL('Blocks', 'admin', 'newposition'), 'text' => __('Create new block position'));
    }
    if (SecurityUtil::checkPermission('Blocks::', '::', ACCESS_EDIT)) {
        if (SessionUtil::getVar('blocks_show_all')) {
            $links[] = array('url' => pnModURL('Blocks', 'admin', 'showactive'), 'text' => __('Display active blocks'));
        } else {
            $links[] = array('url' => pnModURL('Blocks', 'admin', 'showall'), 'text' => __('Display all blocks'));
        }
    }
    if (SecurityUtil::checkPermission('Blocks::', '::', ACCESS_ADMIN)) {
        $links[] = array('url' => pnModURL('Blocks', 'admin', 'modifyconfig'), 'text' => __('Settings'));
    }

    return $links;
}
