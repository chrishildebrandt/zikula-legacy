<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2004, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: function.pncalendarinput.php 28120 2010-01-20 06:55:39Z mateo $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula_Template_Plugins
 * @subpackage Functions
 */

/**
 * Smarty function to display a calendar input control
 *
 * This function displays a javascript (jscalendar) calendar control
 *
 * Available parameters:
 *   - objectname     The name of the object the field will be placed in
 *   - htmlname:      The html fieldname under which the date value will be submitted
 *   - dateformat:    The dateformat to use for displaying the chosen date
 *   - ifformat:      Format of the date field sent in the form (optional - defaults to dateformat but only accepts '%Y-%m-%d %H:%M' or '%Y-%m-%d')
 *   - defaultstring: The String to display before a value has been selected
 *   - defaultdate:   The Date the calendar should to default to (format: Y/m/d)
 *   - hidden:        Boolean to show a hidden input or not
 *   - display:       Boolean to show a display output (when date is added in a hidden field)
 *   - class:         The class to apply to the html elements
 *   - time:          If set show time selection
 *
 * Example
 * <!--[pncalendarinput objectname=myobject htmlname=from dateformat='%Y-%m-%d' defaultdate='2005/12/31']-->
 *
 * @author       Mark West
 * @author       Robert Gasch
 * @since        25/10/2005
 * @param        array       $params      All attributes passed to this function from the template
 * @param        object      &$smarty     Reference to the Smarty object
 * @param        assign      The smarty variable to assign the resulting menu HTML to
 * @return       string      the language constant
 */
function smarty_function_pncalendarinput($params, &$smarty)
{
    if (!isset($params['objectname'])) {
        $smarty->trigger_error(__f('Error! in %1$s: the %2$s parameter must be specified.', array('pncalendarinput', 'objectname')));
        return false;
    }
    if (!isset($params['htmlname'])) {
        $smarty->trigger_error(__f('Error! in %1$s: the %2$s parameter must be specified.', array('pncalendarinput', 'htmlname')));
        return false;
    }
    if (!isset($params['dateformat'])) {
        $smarty->trigger_error(__f('Error! in %1$s: the %2$s parameter must be specified.', array('pncalendarinput', 'dateformat')));
        return false;
    }
    $ifformat = isset($params['ifformat']) ? $params['ifformat'] : $params['dateformat'];
    $inctime  = isset($params['time']) ? (bool)$params['time'] : false;

    switch ($ifformat)
    {
        case '%Y-%m-%d':
            $ifformat = __('%Y-%m-%d');
            break;

        case '%Y-%m-%d %H:%M':
            $ifformat = __('%Y-%m-%d %H:%M');
            break;

        default:
            $ifformat = $inctime ? __('%Y-%m-%d %H:%M') : __('%Y-%m-%d');
    }

    // start of old pncalendarinit
    // pagevars make an extra pncalendarinit obsolete, they take care about the fact
    // that the styles/jsvascript do not get loaded multiple times
    static $firstTime = true;

    if ($firstTime) {
        $lang = ZLanguage::transformFS(ZLanguage::getLanguageCode());
        // map of the jscalendar supported languages
        $map = array('ca' => 'ca_ES', 'cz' => 'cs_CZ', 'da' => 'da_DK', 'de' => 'de_DE', 'el' => 'el_GR', 'en-us' => 'en_US', 'es' => 'es_ES', 'fi' => 'fi_FI', 'fr' => 'fr_FR', 'he' => 'he_IL', 'hr' => 'hr_HR', 'hu' => 'hu_HU', 'it' => 'it_IT', 'ja' => 'ja_JP',
                     'ko' => 'ko_KR', 'lt' => 'lt_LT', 'lv' => 'lv_LV', 'nl' => 'nl_NL', 'no' => 'no_NO', 'pl' => 'pl_PL', 'pt' => 'pt_BR', 'ro' => 'ro_RO', 'ru' => 'ru_RU', 'si' => 'si_SL', 'sk' => 'sk_SK', 'sv' => 'sv_SE', 'tr' => 'tr_TR');

        if (isset($map[$lang])) {
            $lang = $map[$lang];
        }

        $headers[] = 'javascript/jscalendar/calendar.js';
        if (file_exists("javascript/jscalendar/lang/calendar-$lang.utf8.js")) {
            $headers[] = "javascript/jscalendar/lang/calendar-$lang.utf8.js";
        }
        $headers[] = 'javascript/jscalendar/calendar-setup.js';
        PageUtil::addVar('stylesheet', 'javascript/jscalendar/calendar-win2k-cold-2.css');
        PageUtil::addVar('javascript', $headers);
    }
    $firstTime = false;
    // end of old pncalendarinit

    if (!isset($params['defaultstring'])) $params['defaultstring'] = null;
    if (!isset($params['defaultdate']))   $params['defaultdate'] = null;

    $html = '';

    $fieldKey = $params['htmlname'];
    if ($params['objectname']) {
        $fieldKey = $params['objectname'] . '[' . $params['htmlname'] . ']';
    }

    $triggerName = 'trigger_' . $params['htmlname'];
    $displayName = 'display_' . $params['htmlname'];

    if (isset($params['class']) && !empty($params['class'])) {
        $params['class'] = ' class="' . DataUtil::formatForDisplay($params['class']) . '"';
    } else {
        $params['class'] = '';
    }

    if (isset($params['display']) && $params['display']) {
        $html .= '<span id="'.$displayName.'"'.$params['class'].'>'.$params['defaultstring'].'</span>&nbsp;';
    }

    if (isset($params['hidden']) && $params['hidden']) {
        $html .= '<input type="hidden" name="'.$fieldKey.'" id="'.$params['htmlname'].'" value="'.$params['defaultdate'].'" />';
    }

    $html .= '<img class="z-calendarimg" src="'.pnGetBaseURL().'javascript/jscalendar/img.gif" id="'.$triggerName.'" style="cursor: pointer;" title="' . DataUtil::formatForDisplay(__('Date selector')) . '"  alt="' . DataUtil::formatForDisplay(__('Date selector')) . '" />';

    $i18n = & ZI18n::getInstance();

    $html .= "<script type=\"text/javascript\">
              // <![CDATA[
              Calendar.setup(
              {";

    //$html .= 'ifFormat    : "%Y-%m-%d %H:%M:00",'; // universal format, don't change this!
    $html .= 'ifFormat    : "'.$ifformat.'",';
    $html .= 'inputField  : "'.$params['htmlname'].'",';
    $html .= 'displayArea : "'.$displayName.'",';
    $html .= 'daFormat    : "'.$params['dateformat'].'",';
    $html .= 'button      : "'.$triggerName.'",';
    $html .= 'defaultDate : "'.$params['defaultdate'].'",';
    $html .= 'firstDay    : "'.$i18n->locale->getFirstweekday().'",';
    $html .= 'align       : "Tl",';

    if (isset($params['defaultdate']) && $params['defaultdate']) {
        $d = strtotime ($params['defaultdate']);
        $d = date ('Y/m/d', $d);
        $html .= 'date : "'.$d.'",';
    }

    if ($inctime) {
        $html .= 'showsTime  : true,';
        $html .= 'timeFormat : "'.$i18n->locale->getTimeformat().'",';
    }

    $html .= "singleClick : true });
              // ]]>
              </script>";

    return $html;
}
