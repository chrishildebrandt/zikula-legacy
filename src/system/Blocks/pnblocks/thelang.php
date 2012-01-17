<?php
/**
 * Zikula Application Framework
 * @copyright (c) 2001, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: thelang.php 27610 2009-11-16 23:30:07Z ph $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula_System_Modules
 * @subpackage Blocks
 */

/**
 * initialise block
 *
 * @author       The Zikula Development Team
 */
function Blocks_thelangblock_init()
{
    // Security
    SecurityUtil::registerPermissionSchema('Languageblock::', 'Block title::');
}

/**
 * get information on block
 *
 * @author       The Zikula Development Team
 * @return       array       The block information
 */
function Blocks_thelangblock_info()
{
    return array('module'          => 'Blocks',
                 'text_type'       => __('Language'),
                 'text_type_long'  => __('Language selector block'),
                 'allow_multiple'  => false,
                 'form_content'    => false,
                 'form_refresh'    => false,
                 'show_preview'    => true,
                 'admin_tableless' => true);
}

/**
 * Display the block
 *
 * @param        row           blockinfo array
 */
function Blocks_thelangblock_display($blockinfo)
{
    // security check
    if (!SecurityUtil::checkPermission('Languageblock::', "$blockinfo[title]::", ACCESS_OVERVIEW)) {
        return;
    }

    // if the site's not an ML site don't display the block
    if (!pnConfigGetVar('multilingual')) {
        return;
    }

    // Get current content
    $vars = pnBlockVarsFromContent($blockinfo['content']);
    $vars['bid'] = $blockinfo['bid'];
    // Defaults
    if (empty($vars['format'])) {
        $vars['format'] = 2;
    }

    if (!isset($vars['languages']) || empty($vars['languages']) || !is_array($vars['languages'])) {
        $vars['languages'] = Blocks_thelangblock_getAvailableLanguages();
    }

    // Create output object - this object will store all of our output so that
    // we can return it easily when required
    $pnRender = & pnRender::getInstance('Blocks', false);

    // assign the block vars
    $pnRender->assign($vars);

    // what's the current language
    $currentlanguage = ZLanguage::getLanguageCode();
    $pnRender->assign('currentlanguage', $currentlanguage);

    // set a block title
    if (empty($blockinfo['title'])) {
        $blockinfo['title'] = __('Choose a language');
    }


    // prepare vars for pnModURL
    $module = FormUtil::getPassedValue('module', null, 'GET');
    $type = FormUtil::getPassedValue('type', null, 'GET');
    $func = FormUtil::getPassedValue('func', null, 'GET');
    $get = $_GET;
    if (isset($get['module'])) {
        unset($get['module']);
    }
    if (isset($get['type'])) {
        unset($get['type']);
    }
    if (isset($get['func'])) {
        unset($get['func']);
    }
    if (isset($get['lang'])) {
        unset($get['lang']);
    }

    // make homepage calculations
    $shorturls = pnConfigGetVar('shorturls', false);
    $shorturlstype = pnConfigGetVar('shorturlstype');
    $dirBased = ($shorturlstype == 0 ? true : false);

    if ($shorturls && $dirBased) {
        $homepage = pnGetBaseURL().pnConfigGetVar('entrypoint', 'index.php');
        $forcefqdn = true;
    } else {
        $homepage = pnConfigGetVar('entrypoint', 'index.php');
        $forcefqdn = false;
    }

    // build URLS
    $languages = ZLanguage::getInstalledLanguages();
    $urls = array();
    foreach ($languages as $code) {
        $thisurl = pnModURL($module, $type, $func, $get, null, null, true, $forcefqdn, $code);
        if ($thisurl == '') {
            $thisurl = ($shorturls && $dirBased ? $code : "$homepage?lang=$code");
        }
        $codeFS = ZLanguage::transformFS($code);
        $legacyCodeFS = ZLanguage::transformFS(ZLanguage::lookupLegacyCode($code));

        $files = array("images/flags/flag-$codeFS.png", "images/flags/flag-$legacyCodeFS.png");
        if (file_exists($files[0])) {
            $flag = $files[0];
        } else if (file_exists($files[1])) {
            $flag = $files[1];
        } else {
            $flag = '';
        }

        $flag = (($flag && $shorturls && $dirBased) ? pnGetBaseURL().$flag : $flag);

        $urls[] = array('code' => $code, 'name' => ZLanguage::getLanguageName($code), 'url' => $thisurl, 'flag' => $flag);
    }
    usort($urls, '_blocks_thelangblock_sort');

    $pnRender->assign('urls', $urls);

    // get the block content from the template then end the templating
    $blockinfo['content'] = $pnRender->fetch('blocks_block_thelang.htm');

    // return the block to the theme
    return pnBlockThemeBlock($blockinfo);
}


/**
 * modify block settings
 *
 * @author       The Zikula Development Team
 * @param        array       $blockinfo     a blockinfo structure
 * @return       output      the bock form
 */
function blocks_thelangblock_modify($blockinfo)
{
    // Get current content
    $vars = pnBlockVarsFromContent($blockinfo['content']);

    // Defaults
    if (empty($vars['format'])) {
        $vars['format'] = 2;
    }

    // Create output object
    // As Admin output changes often, we do not want caching.
    $pnRender = & pnRender::getInstance('Blocks', false);

    // assign the approriate values
    $pnRender->assign($vars);

    // clear the block cache
    $pnRender = & pnRender::getInstance('Blocks');
    $pnRender->clear_cache('blocks_block_thelang.htm');

    // Return the output that has been generated by this function
    return $pnRender->fetch('blocks_block_thelang_modify.htm');
}


/**
 * update block settings
 *
 * @author       The Zikula Development Team
 * @param        array       $blockinfo     a blockinfo structure
 * @return       $blockinfo  the modified blockinfo structure
 */
function Blocks_thelangblock_update($blockinfo)
{
    // Get current content
    $vars = pnBlockVarsFromContent($blockinfo['content']);

    // Read inputs
    $vars['format'] = FormUtil::getPassedValue('format');

    // Scan for languages and save cached version
    $vars['languages'] = Blocks_thelangblock_getAvailableLanguages();

    // write back the new contents
    $blockinfo['content'] = pnBlockVarsToContent($vars);

    // clear the block cache
    $pnRender = & pnRender::getInstance('Blocks');
    $pnRender->clear_cache('blocks_block_thelang.htm');

    return $blockinfo;
}


function Blocks_thelangblock_getAvailableLanguages()
{
    $langlist = ZLanguage::getInstalledLanguageNames();

    $list = array();
    foreach ($langlist as $code => $langname)
    {
        $d3l = ZLanguage::lookupLegacyCode($code);  // 3-digit language code
        $img = file_exists("images/flags/flag-$d3l.png");

        $list[] = array('code' => $code,
                        'd3l'  => $d3l,
                        'name' => $langname,
                        'flag' => $img ? "images/flags/flag-$d3l.png" : '');
    }

    usort($list, '_blocks_thelangblock_sort');

    return $list;
}


function _blocks_thelangblock_sort($a, $b)
{
    return strcmp($a['name'], $b['name']);
}

