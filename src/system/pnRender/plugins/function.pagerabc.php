<?php
/**
 * Zikula Application Framework
 *
 * @link http://www.zikula.org
 * @version $Id: function.pagerabc.php 27067 2009-10-21 17:20:35Z drak $
 * @author Peter Dudas <duda at bigfish dot hu>
 * @package Zikula_Template_Plugins
 * @subpackage Functions
 */

/**
* Smarty plugin
* -------------------------------------------------------------
* Type:     function
* Name:     pagerabc
* Purpose:  Displays alphabetical selection links
* Version:  1.1
* Date:     April 30, 2005
* Author:   Peter Dudas <duda at bigfish dot hu>
*           Martin Andersen; API links for ShortURL compliance
* -------------------------------------------------------------
*  Changes:   2002/09/25                 - created
*             2005/04/30   msandersen    - Added Span with Pager class, uses API for links when link to API module, various tweaks
*             2005/08/20   msandersen    - Changed forwardvars behaviour to be consistent with pager plugin:
*                                          If forwardvars is not set, ALL the URL vars are forwarded.
*                                          Fixed bug where if forwardvars weren't specifically set with "module,func" these core vars would not be used at all in the links
*                                          Added support for use on the Startpage, where the vars are taken from the config starttype, startfunc, and startargs vars.
*                                          Fixed the example below.
*             2008/12/07   nestormateo   - General code improvement
*                                          Do not extract the parameters
*                                          CSS class from parameters
*                                          Improved the example below
*
*  Examples:
*    code:
*    <!--[pagerabc posvar='letter' class='abcpager' class_num='abclink' class_numon='abclink_on' separator=' - ' names='A,B;C,D;E,F;G,H;I,J;K,L;M,N,O;P,Q,R;S,T;U,V,W,X,Y,Z']-->
*
*    result
* <span class="abcpager">
* <a class="abclink_on" href="index.php?module=Example&amp;letter=A,B">&nbspA,B</a>
*  - <a class="abclink" href="index.php?module=Example&amp;letter=C,D">&nbspC,D</a>
*  - <a class="abclink" href="index.php?module=Example&amp;letter=E,F">&nbspE,F</a>
*  - <a class="abclink" href="index.php?module=Example&amp;letter=G,H">&nbspG,H</a>
*  - <a class="abclink" href="index.php?module=Example&amp;letter=I,J">&nbspI,J</a>
*  - <a class="abclink" href="index.php?module=Example&amp;letter=K,L">&nbspK,L</a>
*  - <a class="abclink" href="index.php?module=Example&amp;letter=M,N,O">&nbspM,N,O</a>
*  - <a class="abclink" href="index.php?module=Example&amp;letter=P,Q,R">&nbspP,Q,R</a>
*  - <a class="abclink" href="index.php?module=Example&amp;letter=S,T">&nbspS,T</a>
*  - <a class="abclink" href="index.php?module=Example&amp;letter=U,V,W,X,Y,Z">&nbspU,V,W,X,Y,Z</a>
* </span>
*
*
* Parameters
*     @param    string     $posvar           - name of the variable that contains the position data, eg "letter"
*     @param    cvs        $forwardvars      - comma- semicolon- or space-delimited list of POST and GET variables to forward in the pager links. If unset, all vars are forwarded.
*     @param    cvs        $additionalvars   - comma- semicolon- or space-delimited list of additional variable and value pairs to forward in the links. eg "foo=2,bar=4"
*     @param    string     $class            - class for the pager
*     @param    string     $class_num        - class for the pager links (<a> tags)
*     @param    string     $class_numon      - class for the active page
*     @param    string     $separator        - string to put between the letters, eg "|" makes | A | B | C | D |
*     @param    string     $printempty       - print empty sel ('-')
*     @param    string     $lang             - language
*     @param    mixed      $names            - string or array of names to select from (array or csv)
*     @param    mixed      $values           - optional parameter for the previous names (array or cvs)
*     @param    string     $skin             - use predefined values (hu - hungarian ABC)
*/
function smarty_function_pagerabc($params, &$smarty)
{
    if (!isset($params['posvar'])) {
        $params['posvar'] = 'letter';
    }

    if (!isset($params['separator'])) {
        $params['separator'] = ' | ';
    }

    if (!isset($params['skin'])) {
        $params['skin'] = '';
    }

    if (!isset($params['printempty']) || !is_bool($params['printempty'])) {
        $params['printempty'] = false;
    }

    // set a default class
    if (!isset($params['class'])) {
        $params['class'] = 'z-pager';
    }

    if (!isset($params['class_num'])) {
        $params['class_num'] = 'z-pagerabclink';
    }

    if (!isset($params['class_numon'])) {
        $params['class_numon'] = 'z-pagerselected';
    }


    $pager = array();

    if (!empty($params['names'])) {
        if (!is_array($params['names'])) {
            $pager['names'] = explode(';', $params['names']);
        } else {
            $pager['names'] = $params['names'];
        }
        if (!empty($params['values'])) {
            if (!is_array($params['values']))    {
                $pager['values'] = explode(';', $params['values']);
            } else {
                $pager['values'] = $params['values'];
            }
            if (count($pager['values']) != count($pager['names'])) {
                LogUtil::registerError('pagerabc: Values length must be the same of the names');
                $pager['values'] = $pager['names'];
            }
        } else {
            $pager['values'] = $pager['names'];
        }
    } else {
        // predefined abc
        if (strtolower($params['skin']) == 'hu') { // Hungarian
            $pager['names']  = $pager['values'] = array('A','�','B','C','D','E','�','F','G','H','I','�','J','K','L','M','N','O','�','�','O','P','Q','R','S','T','U','�','�','U','V','W','X','Y','Z');
          //$params['names']  = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U'    ,'V','W','X','Y','Z');
          //$params['values'] = array('A,�','B','C','D','E,�','F','G','H','I,�','J','K','L','M','N','O,�,�,O','P','Q','R','S','T','U,�,�,U','V','W','X','Y','Z');
        } else {
            $alphabet = (defined('_ALPHABET')) ? constant('_ALPHABET') : 'A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z';
            $pager['names'] = $pager['values'] = explode(',', $alphabet);
        }
    }

    $pager['posvar'] = $params['posvar'];
    unset($params['posvar']);
    unset($params['names']);
    unset($params['values']);

    if (isset($params['modname'])) {
        $pager['module'] = $params['modname'];
    } else {
        $module = FormUtil::getPassedValue('module', null, 'GETPOST');
        $name   = FormUtil::getPassedValue('name', null, 'GETPOST');
        $pager['module'] = !empty($module) ? $module : $name;
    }

    $pager['func'] = isset($params['func']) ? $params['func'] : FormUtil::getPassedValue('func', 'main', 'GETPOST');
    $pager['type'] = isset($params['type']) ? $params['type'] : FormUtil::getPassedValue('type', 'user', 'GETPOST');

    $allVars = array_merge($_POST, $_GET);

    $pager['args'] = array();
    if (empty($pager['module'])) {
        $pager['module'] = pnConfigGetVar('startpage');
        $starttype = pnConfigGetVar('starttype');
        $pager['type'] = !empty($starttype) ? $starttype : 'user';
        $startfunc = pnConfigGetVar('startfunc');
        $pager['func'] = !empty($startfunc) ? $startfunc : 'main';

        $startargs = explode(',', pnConfigGetVar('startargs'));
        foreach ($startargs as $arg) {
            if (!empty($arg)) {
                $argument = explode('=', $arg);
                if ($argument[0] == $pager['posvar']) {
                    $allVars[$argument[0]] = $argument[1];
                }
            }
        }
    }

    // If $forwardvars set, add only listed vars to query string, else add all POST and GET vars
    if (isset($params['forwardvars'])) {
        if (!is_array($params['forwardvars'])) {
            $params['forwardvars'] = preg_split('/[,;\s]/', $params['forwardvars'], -1, PREG_SPLIT_NO_EMPTY);
        }
        foreach ((array)$params['forwardvars'] as $key => $var) {
            if (!empty($var) && (!empty($allVars[$var]))) {
                $pager['args'][$var] = $allVars[$var];
            }
        }
    } else {
        $pager['args'] = array_merge($pager['args'], $allVars);
    }

    if (isset($params['additionalvars'])) {
        if (!is_array($params['additionalvars'])) {
            $params['additionalvars'] = preg_split('/[,;\s]/', $params['additionalvars'], -1, PREG_SPLIT_NO_EMPTY);
        }
        foreach ((array)$params['additionalvars'] as $var) {
            $additionalvar = preg_split('/=/', $var);
            if (!empty($var) && !empty($additionalvar[1])) {
                $pager['args'][$additionalvar[0]] = $additionalvar[1];
            }
        }
    }
    unset($pager['args']['module']);
    unset($pager['args']['func']);
    unset($pager['args']['type']);
    unset($pager['args'][$pager['posvar']]);

    // begin to fill the output
    $output = '<span class="'.$params['class'].'">'."\n";

    $style = '';
    if ($params['printempty']) {
        if (!empty($params['class_num'])) {
            $style = 'class="'.$params['class_num'].'"';
        }
        $vars[$pager['posvar']] = '';
        $urltemp = DataUtil::formatForDisplay(pnModURL($pager['module'], $pager['type'], $pager['func'], $pager['args']));
        $output .= '<a '.$tmp.' href="'.$urltemp.'"> -'."\n</a>".$params['separator'];
    }

    $style = '';
    foreach(array_keys($pager['names']) as $i) {
        if (!empty($params['class_numon']))    {
            if (isset($allVars[$pager['posvar']]) && $allVars[$pager['posvar']] == $pager['values'][$i])  {
                $style = ' class="'.$params['class_numon'].'"';
            } elseif (!empty($params['class_num']))    {
                $style = ' class="'.$params['class_num'].'"';
            } else {
                $style = '';
            }
        }
        $pager['args'][$pager['posvar']] = $pager['values'][$i];
        $urltemp = DataUtil::formatForDisplay(pnModURL($pager['module'], $pager['type'], $pager['func'], $pager['args']));
        if ($i > 0) {
            $output .= $params['separator'];
        }
        $output .= '<a'.$style.' href="'.$urltemp.'">'.$pager['names'][$i]."</a>\n";
    }
    $output .= "</span>\n";

    return $output;
}
