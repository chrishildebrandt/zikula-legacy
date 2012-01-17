<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2001, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: modifier.updated.php 24342 2008-06-06 12:03:14Z markwest $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 *
 * @package Zikula_Themes
 * @subpackage Atom
 */

/**
 * Smarty modifier format an issue date for an atom news feed
 *
 * Example
 *
 *   <!--[$MyVar|updated]-->
 *
 * @author       Mark West
 * @author		 Franz Skaaning
 * @since        02 March 2004
 * @param        array    $string     the contents to transform
 * @return       string   the updated output
 */
function smarty_modifier_updated($string)
{
    global $atom_feed_lastupdated;

    $timestamp = strtotime($string);

    if (!isset($atom_feed_lastupdated) || $timestamp > $atom_feed_lastupdated) {
        $atom_feed_lastupdated = $timestamp;
    }

    return strftime('%Y-%m-%dT%H:%M:%SZ', $timestamp);
}
