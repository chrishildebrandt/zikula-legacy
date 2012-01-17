<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2004, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: outputfilter.pagevars.php 28200 2010-02-03 14:56:12Z yokav $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula_Template_Plugins
 * @subpackage Filters
 */

/**
 * Smarty outputfilter to add page variables and additional header global into page header
 *
 * By default this output filter places page variable output immediately prior to the closing
 * head tag (</head>). The output can, optionally, be placed anywhere in the template by adding
 * the HTML comment <!-- pagevars --> to the page template. Note that this must always be in
 * the header for the output to function correctly.
 *
 * @author    Mark West
 * @param     string
 * @param     Smarty
 */
function smarty_outputfilter_pagevars($source, &$smarty)
{
    $return = '';

    // We need to make sure that the content of the oldstyle additional_header array does
    // lead to duplicate headers if the same output is also defined in the PageVars.
    // This is complicated as the format differs:
    // PageVar for javascript: path/to/javascript.js
    // additional_header: <script type="text/javascript" src="path/to/javascript"></script> or different
    // We go the easy way and check if the value of a pagevar is part of the additional_header value (which
    // it is in the example above)
    // This will be done for stylesheet and javascript pagevars only right now. We can extend this if necessary.
    global $additional_header;

    $themeinfo = ThemeUtil::getInfo(ThemeUtil::getIDFromName(pnUserGetTheme()));

    // get any javascript page vars
    $javascripts = PageUtil::getVar('javascript');
    // check if we need to perform ligthbox replacement
    if(false !== $key = array_search('javascript/ajax/lightbox.js', $javascripts)) {
        if(!is_readable('javascript/ajax/lightbox.js')) {
            $javascripts[$key] = 'javascript/helpers/Zikula.ImageViewer.js';
            $replaceLightbox = true;
        }
    }

    // get any stylesheet page vars
    $stylesheets = PageUtil::getVar('stylesheet');
    $stylesheets[] = 'javascript/style.css';
    //lightbox replacement
    if(isset($replaceLightbox) && $replaceLightbox === true) {
        if(false !== $key = array_search('javascript/ajax/lightbox/lightbox.css', $stylesheets)) {
            $stylesheets[$key] = 'javascript/helpers/ImageViewer/ImageViewer.css';
        }
    }
    if (is_array($stylesheets) && !empty($stylesheets)) {
        foreach ($stylesheets as $s => $stylesheet) {
            if (empty($stylesheet)) {
                unset($stylesheets[$s]);
                continue;
            }
            // check if the stylesheets is in the additional_header array
            _smarty_outputfilter_pagevars_clean_additional_header($additional_header, $stylesheet);
        }
        $stylesheets = array_unique(array_values($stylesheets));
        // Perform a check on import and expand those for packing later on
        $stylesheetFile = _smarty_outputfilter_pagevars_save($stylesheets,'css',$smarty->cache_dir);
        if ($themeinfo['xhtml']) {
            $return .= '<link rel="stylesheet" href="'.DataUtil::formatForDisplay($stylesheetFile).'" type="text/css" />'."\n";
        } else {
            $return .= '<link rel="stylesheet" href="'.DataUtil::formatForDisplay($stylesheetFile).'" type="text/css">'."\n";
        }
    }

    if (is_array($javascripts) && !empty($javascripts)) {
        // some check for prototype/pnajax/scriptaculous
        $enableSplice = false;
        $javascriptLinksToCheck = array('javascript/ajax/prototype.js',
                                        'javascript/ajax/scriptaculous.js',
                                        'javascript/ajax/scriptaculous.js?load=builder',
                                        'javascript/ajax/scriptaculous.js?load=effects',
                                        'javascript/ajax/scriptaculous.js?load=dragdrop',
                                        'javascript/ajax/scriptaculous.js?load=controls',
                                        'javascript/ajax/scriptaculous.js?load=slider',
                                        'javascript/ajax/scriptaculous.js?load=sound',
                                        'javascript/ajax/pnajax.js');
 
        $javascriptNewLinks     = array('javascript/ajax/prototype.js',
                                        'javascript/ajax/scriptaculous.js',
                                        'javascript/ajax/builder.js',
                                        'javascript/ajax/effects.js',
                                        'javascript/ajax/dragdrop.js',
                                        'javascript/ajax/controls.js',
                                        'javascript/ajax/slider.js',
                                        'javascript/ajax/sound.js',
                                        'javascript/ajax/pnajax.js');

        foreach ($javascripts as $key => $currentJS) {
            if (in_array($currentJS, $javascriptLinksToCheck)) {
                unset($javascripts[$key]);
                $enableSplice = true;
            }
        }

        if ($enableSplice) {
            array_splice($javascripts,0,0,$javascriptNewLinks);
        }

        // Ugly but necessary inline javascript for now: Some javascripts, eg. the lightbox, need to know the path to the system and
        // the entrypoint as well (which can be configured in the settings) otherwise they may fail in case of short urls being
        // enabled. We will now add some inline javascript to extend the DOM:
        //
        // document.location.entrypoint: will be set to what is configured to be the entrypoint
        // document.location.pnbaseURL: will point to the result of pnGetBaseURL();
        //
        // todo: make his more unobtrusive, but how? Dynamic javascript creation might be a performance problem. Any idea here
        // is highly appreciated! [landseer]
        //
        $return .= '<script type="text/javascript">/* <![CDATA[ */ document.location.entrypoint="' . pnConfigGetVar('entrypoint', 'index.php') . '"; document.location.pnbaseURL="' . pnGetBaseURL(). '"; ';
        // check if the ajaxtimeout is configured and not the defsult value of 5000, in this case add the value in the inline js for refernce in pnajax.js
        $ajaxtimeout = pnConfigGetVar('ajaxtimeout', 5000);
        if ($ajaxtimeout != 5000) {
            $return .= 'document.location.ajaxtimeout=' . (int) DataUtil::formatForDisplay($ajaxtimeout). ';';
        }
        $return .= ' /* ]]> */</script>' . "\n";
        foreach ($javascripts as $j => $javascript) {
            if (empty($javascript)) {
                unset($javascripts[$j]);
                continue;
            }
            // check if the javascript is in the additional_header array
            _smarty_outputfilter_pagevars_clean_additional_header($additional_header, $javascript);
        }
        $javascripts = array_unique(array_values($javascripts));
        $javascriptFile = _smarty_outputfilter_pagevars_save($javascripts, 'js', $smarty->cache_dir);
        $return .= '<script type="text/javascript" src="'.DataUtil::formatForDisplay($javascriptFile).'"></script>'."\n";
    }

    $rawtext = PageUtil::getVar('rawtext');
    if (is_array($rawtext) && !empty($rawtext)) {
        $return .= implode("\n", $rawtext) . "\n";
    }

    // implode the remaining additional header global to a string
    if (isset($additional_header) && count($additional_header)>0) {
        $return .= @implode("\n", $additional_header) . "\n";
    }

    // if we've got some page vars to add the header wrap the output in
	// suitable identifiying comments when in development mode
    $return = trim($return);
    if (!empty($return) && pnConfigGetVar('development') != 0) {
        $return = "<!-- zikula pagevars -->\n" . $return . "\n<!-- /zikula pagevars -->";
    }

    // get any body page vars
    $bodyvars = PageUtil::getVar('body');
    if (!empty($bodyvars)) {
        $bodyattribs = '<body ' . @implode(' ', $bodyvars) . '>';
        $source = str_replace('<body>', $bodyattribs, $source);
    }

    // get any footer page vars
    $footervars = PageUtil::getVar('footer');
    if (!empty($footervars)) {
        $footersource = @implode("\n", $footervars)."\n</body>";
        $source = str_replace('</body>', $footersource, $source);
    }

    // replace the string in the template source
    if (stristr($source, '<!-- pagevars -->')) {
        $source = str_replace('<!-- pagevars -->', $return, $source);
    } else {
        $source = str_replace('</head>', $return."\n</head>", $source);
    }
    // return the modified source
    return $source;
}

function _smarty_outputfilter_pagevars_clean_additional_header(&$additional_header, $pagevar)
{
    $ahcount = count($additional_header);
    if ($ahcount == 0) {
        return;
    }

    $new_header = array();
    for ($i = 0; $i < $ahcount; $i++) {
        if (!empty($additional_header[$i])) {
            if (stristr($additional_header[$i], $pagevar) != false) {
                // gotcha -found pagevar in additional_header string
            // skip this
            } else {
                // not found, keep the additional_header for later checks or output
                $new_header[] = $additional_header[$i];
            }
        }
    }
    $additional_header = $new_header;
    return;
}

function _smarty_outputfilter_pagevars_save($files, $ext, $cache_dir)
{
    $themevars = pnModGetVar('Theme');

    $lifetime = $themevars['cssjscombine_lifetime'];
    $hash = md5(serialize($files).pnUserGetTheme());

    $cachedFile    = "{$cache_dir}/{$hash}_{$ext}.php";
    $cachedFileUri = "{$hash}_{$ext}.php";
    if(is_readable($cachedFile) && (filemtime($cachedFile) + $lifetime) > time()) {
        return "jcss.php?f=$cachedFileUri";
    }

    switch($ext) {
        case 'css':
            $ctype = 'text/css';
            break;
        case 'js':
            $ctype = 'text/javascript';
            break;
        default:
            $ctype = 'text/plain';
            break;
    }

    $contents = '';
    $dest = fopen($cachedFile, 'w');

    $contents .= "/* --- Combined file written: " . DateUtil::getDateTime() . " */\n\n";
    foreach($files as $file) {
        if($source = fopen($file, 'r')) {
            $filepath = explode('/', dirname($file));
            $contents .= "/* --- Source file: {$file} */\n\n";
            while (!feof($source)) {
                if($ext == 'css') {
                    $contents .= _smarty_outputfilter_pagevars_cssfixPath(fgets($source, 4096), $filepath);
                } else {
                    $contents .= fgets($source, 4096);
                }
            }
            fclose($source);
            $contents .= "\n\n";
        }
    }

    // optional minify
    if ($themevars['cssjsminify']) {
        if ($ext == 'js') {
            //$contents = JSMin::minify($contents); // TODO A JS minify library was removed... we can find a solution - drak.
        } elseif ($ext == 'css') {
            // Remove comments.
            $contents = trim(preg_replace('/\/\*.*?\*\//s', '', $contents));
            // Compress whitespace.
            $contents = preg_replace('/\s+/', ' ', $contents);
            // Additional whitespace optimisation -- spaces around certain tokens is not required by CSS
            $contents = preg_replace('/\s*(;|\{|\}|:|,)\s*/', '\1', $contents);
        }
    }

    global $PNConfig;
    $signingKey = md5($PNConfig['DBInfo']['default']['dbhost'].$PNConfig['DBInfo']['default']['dbuname'].$PNConfig['DBInfo']['default']['dbpass']);
    $signature = md5($contents.$ctype.$lifetime.$themevars['cssjscompress'].$signingKey);
    $data = array('contents' => $contents, 'ctype' => $ctype, 'lifetime' => $lifetime, 'gz' => $themevars['cssjscompress'], 'signature' => $signature);
    fwrite($dest, serialize($data));
    fclose($dest);
    return "jcss.php?f=$cachedFileUri";
}

if (!function_exists('_smarty_outputfilter_pagevars_cssfixPath')) {
    function _smarty_outputfilter_pagevars_cssfixPath($line, $filepath)
    {
        $regexpurl = '/url\([\'"]?([\.\/]*)(.*?)[\'"]?\)/i';
        if(strpos($line, 'url') !== false) {
            preg_match_all($regexpurl, $line, $matches, PREG_SET_ORDER);
            foreach($matches as $match) {
                if(strpos($match[1], '/') !== 0) {
                    $depth = substr_count($match[1], '../') * -1;
                    $path = $depth < 0 ? array_slice($filepath, 0, $depth) : $filepath;
                    $path = implode('/', $path);
                    $path = !empty($path) ? $path . '/' : '';
                    $line = str_replace($match[0], "url('{$path}{$match[2]}')", $line);
                }
            }
        }
        return $line;
    }
}
