<?php
/**
 * Zikula Application Framework
 *
 * @copyright Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: HtmlUtil.class.php 28182 2010-02-01 00:42:44Z rgasch $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @author Robert Gasch rgasch@gmail.com
 * @package Zikula_Core
 */

/**
 * HTMLUtil
 *
 * @package Zikula_Core
 * @subpackage HTMLUtil
 */
class HtmlUtil
{
    /**
     * Return the HTML code for the specified date selector input box
     *
     * @param objectname    The name of the object the field will be placed in
     * @param htmlname      The html fieldname under which the date value will be submitted
     * @param dateFormat    The dateformat to use for displaying the chosen date
     * @param defaultString The String to display before a value has been selected
     * @param defaultDate   The Date the calendar should to default to
     *
     * @return The resulting HTML string
     */
    function buildCalendarInputBox($objectname, $htmlname, $dateFormat, $defaultString = '', $defaultDate = '')
    {
        $html = '';

        if (!$htmlname) {
            return pn_exit(__('%1$s: Missing %2$s.', array('HtmlUtil::buildCalendarInputBox', 'htmlname')));
        }

        if (!$dateFormat) {
            return pn_exit(__('%1$s: Missing %2$s.', array('HtmlUtil::buildCalendarInputBox', 'dateFormat')));
        }

        $fieldKey = $htmlname;
        if ($objectname) {
            $fieldKey = $objectname . '[' . $htmlname . ']';
        }

        $triggerName = 'trigger_' . $htmlname;
        $displayName = 'display_' . $htmlname;
        //$daFormat    = preg_replace ('/([a-z|A-Z])/', '%$1', $dateFormat); // replace 'x' -> '%x'

        $html .= '<span id="' . $displayName . '">' . $defaultString . '</span>';
        $html .= '&nbsp;';
        $html .= '<input type="hidden" name="' . $fieldKey . '" id="' . $htmlname . '" value="' . $defaultDate . '" />';
        $html .= '<img src="javascript/jscalendar/img.gif" id="' . $triggerName . '" style="cursor: pointer; border: 0px solid blue;" title="Date selector" alt="Date selector" onmouseover="this.style.background=\'blue\';" onmouseout="this.style.background=\'\'" />';

        $html .= '<script type="text/javascript"> Calendar.setup({';
        $html .= 'ifFormat    : "%Y-%m-%d %H:%M:00",'; // universal format, don't change this!
        $html .= 'inputField  : "' . $htmlname . '",';
        $html .= 'displayArea : "' . $displayName . '",';
        $html .= 'daFormat    : "' . $dateFormat . '",';
        $html .= 'button      : "' . $triggerName . '",';
        $html .= 'align       : "Tl",';

        if ($defaultDate) {
            $d = strtotime($defaultDate);
            $d = date('Y/m/d', $d);
            $html .= 'date : "' . $d . '",';
        }

        $html .= 'singleClick : true }); </script>';

        return $html;
    }

    /**
     * Return the HTML for a generic selector
     *
     * @param name           The name of the generated selector (default='countries') (optional)
     * @param data           The data to build the selector from (default='array()') (optional)
     * @param selectedValue  The value which is currently selected (default='') (optional)
     * @param defaultValue   The default value to select (default='') (optional)
     * @param defaultText    The text for the default value (default='') (optional)
     * @param allValue       The value to assign for the "All" choice (optional) (default=0)
     * @param allText        The text to display for the "All" choice (optional) (default='')
     * @param submit         Whether or not to auto-submit the selector
     * @param disabled       Whether or not to disable selector (optional) (default=false)
     * @param multipleSize   The size to use for a multiple selector, 1 produces a normal/single selector (optional (default=1)
     *
     * @return The generated HTML for the selector
     */
    function getSelector_Generic($name = 'genericSelector', $data = array(), $selectedValue = null, $defaultValue = null, $defaultText = null, $allValue = null, $allText = null, $submit = false, $disabled = false, $multipleSize = 1)
    {
        if (!$name) {
            return LogUtil::registerError(__f('Invalid %1$s [%2$s] passed to %3$s.', array('name', $name, 'HtmlUtil::getSelector_Generic')));
        }

        $id = strtr($name, '[]', '__');
        $disabled = $disabled ? 'disabled="disabled"' : '';
        $multiple = $multipleSize > 1 ? 'multiple="multiple"' : '';
        $multipleSize = $multipleSize > 1 ? "size=\"$multipleSize\"" : '';
        $submit = $submit ? 'onchange="this.form.submit();"' : '';

        $html = "<select name=\"$name\" id=\"$id\" $multipleSize $multiple $submit $disabled>";

        if ($defaultText && !$selectedValue) {
            $sel = ((string) $defaultValue == (string) $selectedValue ? 'selected="selected"' : '');
            $html .= "<option value=\"$defaultValue\" $sel>$defaultText</option>";
        }

        if ($allText) {
            $sel = ((string) $allValue == (string) $selectedValue ? 'selected="selected"' : '');
            $html .= "<option value=\"$allValue\" $sel>$allText</option>";
        }

        foreach ($data as $k => $v) {
            $sel = ((string) $selectedValue == (string) $k ? 'selected="selected"' : '');
            $html .= "<option value=\"$k\" $sel>" . DataUtil::formatForDisplayHTML($v) . '</option>';
        }

        $html .= '</select>';

        return $html;
    }

    function getSelector_ObjectArray($modname, $objectType, $name, $field = 'id', $displayField = 'name', $where = '', $sort = '', $selectedValue = '', $defaultValue = 0, $defaultText = '', $allValue = 0, $allText = '', $displayField2 = null, $submit = true, $disabled = false, $fieldSeparator = ', ', $multipleSize = 1)
    {
        if (!$modname) {
            return pn_exit(__f('Invalid %1$s passed to %2$s.', array('modname', 'HtmlUtil::getSelector_ObjectArray')));
        }

        if (!$objectType) {
            return pn_exit(__f('Invalid %1$s passed to %2$s.', array('objectType', 'HtmlUtil::getSelector_ObjectArray')));
        }

        if (!pnModDBInfoLoad($modname)) {
            return __f('Unavailable/Invalid %1$s [%2$s] passed to %3$s.', array('modulename', $modname, 'HtmlUtil::getSelector_ObjectArray'));
        }

        if (!SecurityUtil::checkPermission("$objectType::", '::', ACCESS_OVERVIEW)) {
            return __f('Security check failed for %1$s [%2$s] passed to %3$s.', array('modulename', $modname, 'HtmlUtil::getSelector_ObjectArray'));
        }

        $cacheKey = md5("$modname|$objectType|$where|$sort");
        if (isset($cache[$cacheKey])) {
            $dataArray = $cache[$cacheKey];
        } else {
            $classname = Loader::loadClassFromModule($modname, $objectType, true);
            if (!$classname) {
                return __f('Unable to load class [%s] for module [%s]', array($objectType, $modname));
            }

            $class = new $classname();
            $dataArray = $class->get($where, $sort, -1, -1, '', false/*, $distinct*/);
            $cache[$cacheKey] = $dataArray;
        }

        $data2 = array();
        foreach ($dataArray as $object) {
            $val = $object[$field];
            $disp = $object[$displayField];
            if ($displayField2) {
                $disp .= $fieldSeparator . $object[$displayField2];
            }
            $data2[$val] = $disp;
        }

        return HtmlUtil::getSelector_Generic($name, $data2, $selectedValue, $defaultValue, $defaultText, $allValue, $allText, $submit, $disabled, $multipleSize);
    }

    function getSelector_FieldArray($modname, $tablekey, $name, $field = 'id', $where = '', $sort = '', $selectedValue = '', $defaultValue = 0, $defaultText = '', $allValue = 0, $allText = '', $assocKey = '', $distinct = false, $submit = true, $disabled = false, $truncate = 0, $multipleSize = 1)
    {
        if (!$tablekey) {
            return pn_exit(__f('Invalid %1$s [%2$s] passed to %3$s.', array('tablekey', $modname, 'HtmlUtil::getSelector_FieldArray')));
        }

        if (!$name) {
            return pn_exit(__f('Invalid %1$s [%2$s] passed to %3$s.', array('name', $name, 'HtmlUtil::getSelector_FieldArray')));
        }

        if ($modname) {
            pnModDBInfoLoad($modname, '', true);
        }

        if ($truncate > 0) {
            Loader::loadClass('StringUtil');
        }

        $fa = DBUtil::selectFieldArray($tablekey, $field, $where, $sort, $distinct, $assocKey);
        $data = array();
        foreach ($fa as $k => $v) {
            if ($v) {
                if ($truncate > 0 && strlen($v) > $truncate) {
                    $v = StringUtil::getTruncatedString($v, $truncate);
                }
                $data[$k] = $v;
            }
        }

        return HtmlUtil::getSelector_Generic($name, $data, $selectedValue, $defaultValue, $defaultText, $allValue, $allText, $submit, $disabled, $multipleSize);
    }

    /**
     * Return the HTML selector code for the given category hierarchy, maps to CategoryUtil::getSelector_Categories()
     *
     * @param cats              The category hierarchy to generate a HTML selector for
     * @param name              The name of the selector field to generate (optional) (default='category[parent_id]')
     * @param field             The field value to return (optional) (default='id')
     * @param selectedValue     The selected category (optional) (default=0)
     * @param defaultValue      The default value to present to the user (optional) (default=0)
     * @param defaultText       The default text to present to the user (optional) (default='')
     * @param allValue          The value to assign for the "All" choice (optional) (default=0)
     * @param allText           The text to display for the "All" choice (optional) (default='')
     * @param submit            whether or not to submit the form upon change (optional) (default=false)
     * @param displayPath       If false, the path is simulated, if true, the full path is shown (optional) (default=false)
     * @param doReplaceRootCat  Whether or not to replace the root category with a localized string (optional) (default=true)
     * @param multipleSize      If > 1, a multiple selector box is built, otherwise a normal/single selector box is build (optional) (default=1)
     *
     * @return The HTML selector code for the given category hierarchy
     */
    function getSelector_Categories($cats, $name, $field = 'id', $selectedValue = '0', $defaultValue = 0, $defaultText = '', $allValue = 0, $allText = '', $submit = false, $displayPath = false, $doReplaceRootCat = true, $multipleSize = 1)
    {
        if (!Loader::loadClass('CategoryUtil')) {
            return pn_exit(__f('Error! Unable to load class [%s]', 'CategoryUtil'));
        }

        return CategoryUtil::getSelector_Categories($cats, $field, $selectedValue, $name, $defaultValue, $defaultText, $allValue, $allText, $submit, $displayPath, $doReplaceRootCat, $multipleSize);
    }

    /**
     * Return the HTML code for the values in a given category
     *
     * @param categoryPath  The identifying category path
     * @param values        The values used to populate the defautl states (optional) (default=array())
     * @param namePrefix    The path/object prefix to apply to the field name (optional) (default='')
     * @param excludeList   A (string) list of IDs to exclude (optional) (default=null)
     * @param disabled      whether or not the checkboxes are to be disabled (optional) (default=false)
     *
     * @return The resulting dropdown data
     */
    function getCheckboxes_CategoryField($categoryPath, $values = array(), $namePrefix = '', $excludeList = null, $disabled = false)
    {
        if (!Loader::loadClass('CategoryUtil')) {
            return pn_exit(__f('Error! Unable to load class [%s]', 'CategoryUtil'));
        }

        if (!$categoryPath) {
            return pn_exit(__f('Invalid %1$s passed to %2$s.', array('category', 'HtmlUtil::getCheckboxes_CategoryField')));
        }

        if (!$lang) {
            $lang = ZLanguage::getLanguageCode();
        }

        //if ($_SESSION['pnCache']['categoryselectorbypath'][$categoryPath] && !$force)
        //return $_SESSION['pnCache']['categoryselectorbypath'][$categoryPath];

        $cats = CategoryUtil::getSubCategoriesByPath($categoryPath, 'path', false, true, false, true, false, '', 'value');

        foreach ($cats as $k => $v) {
            $val = $k;
            $fname = $val;
            if ($namePrefix) {
                $fname = $namePrefix . '[' . $k . ']';
            }

            if (strpos($excludeList, ',' . $k . ',') === false) {
                $disp = $v['display_name'][$lang];
                if (!$disp) {
                    $disp = $v['name'];
                }

                $html .= "<input type=\"checkbox\" name=\"$fname\" " . ($values[$k] ? ' checked="checked" ' : '') . ($disabled ? ' disabled="disabled" ' : '') . " />&nbsp;&nbsp;&nbsp;&nbsp;$disp<br />";
            }
        }

        return $html;
    }

    function getSelector_ModuleTables($modname, $name, $selectedValue = '', $defaultValue = 0, $defaultText = '', $submit = false, $remove = '', $disabled = false, $nStripChars = 0, $multipleSize = 1)
    {
        if (!$modname) {
            return pn_exit(__f('Invalid %1$s passed to %2$s.', array('modname', 'HtmlUtil::getSelector_ModuleTables')));
        }

        $tables = pnModDBInfoLoad($modname, '', true);
        $data = array();
        if (is_array($tables) && $tables) {
            foreach ($tables as $k => $v) {
                if (strpos($k, '_column') === false && strpos($k, '_db_extra_enable') === false && strpos($k, '_primary_key_column') === false) {
                    if (strpos($k, 'pn_') === 0) {
                        $k = substr($k, 4);
                    }

                    if ($remove) {
                        $k2 = str_replace($remove, '', $k);
                    } else {
                        $k2 = $k;
                    }

                    if ($nStripChars) {
                        $k2 = ucfirst(substr($k2, $nStripChars));
                    }

                    // Use $k2 for display also (instead of showing the internal table name)
                    // See http://noc.postnuke.com/tracker/?func=detail&aid=16110&group_id=5&atid=101
                    $data[$k2] = $k2;
                }
            }
        }

        return HtmlUtil::getSelector_Generic($name, $data, $selectedValue, $defaultValue, $defaultText, $allValue, $allText, $submit, $disabled, $multipleSize);
    }

    function getSelector_TableFields($modname, $tablename, $name, $selectedValue = '', $defaultValue = 0, $defaultText = '', $submit = false, $showSystemColumns = false, $disabled = false, $multipleSize = 1)
    {
        if (!$modname) {
            return pn_exit(__f('Invalid %1$s passed to %2$s.', array('modname', 'HtmlUtil::getSelector_TableFields')));
        }

        if (!$tablename) {
            return pn_exit(__f('Invalid %1$s passed to %2$s.', array('tablename', 'HtmlUtil::getSelector_TableFields')));
        }

        if (!$name) {
            return pn_exit(__f('Invalid %1$s passed to %2$s.', array('name', 'HtmlUtil::getSelector_TableFields')));
        }

        $tables = pnModDBInfoLoad($modname, '', true);
        $colkey = $tablename . '_column';
        $cols = $tables[$colkey];

        if (!$cols) {
            return pn_exit(__f('Invalid %1$s [%2$s] in %3$s.', array('column key', $colkey, 'HtmlUtil::getSelector_TableFields')));
        }

        if (!$showSystemColumns) {
            $filtercols = array();
            ObjectUtil::addStandardFieldsToTableDefinition($filtercols, '');
        }

        $data = array();
        foreach ($cols as $k => $v) {
            if ($showSystemColumns) {
                $data[$v] = $k;
            } else {
                if (!$filtercols[$k]) {
                    $data[$v] = $k;
                }
            }
        }

        return HtmlUtil::getSelector_Generic($name, $data, $selectedValue, $defaultValue, $defaultText, $allValue, $allText, $submit, $disabled, $multipleSize);
    }

    /**
     * Return the HTML code for the Yes/No dropdown
     *
     * @param selected    The value which should be selected (default=1) (optional)
     * @param name        The name of the generated selector (optional)
     *
     * @return The resulting HTML string
     */
    function getSelector_YesNo($selected = '1', $name = '')
    {
        if (!$name) {
            $name = 'permission';
        }

        $vals = array();
        $vals[0] = __('No');
        $vals[1] = __('Yes');

        return HtmlUtil::getSelector_Generic($name, $vals);
    }

    /**
     * Return the localized string for the specified yes/no value
     *
     * @param val        The value for which we wish to obtain the string representation
     *
     * @return The string representation for the selected value
     */
    function getSelectorValue_YesNo($val)
    {
        $vals = array();
        $vals[0] = __('No');
        $vals[1] = __('Yes');

        return $vals[$val];
    }

    /**
     * Return the dropdown data for the language selector
     *
     * @param includeAll     whether or not to include the 'All' choice
     *
     * @return The string representation for the selected value
     */
    function getSelectorData_Language($includeAll = true)
    {
        $langlist = array();
        $dropdown = array();

        if ($includeAll) {
            $dropdown[] = array('id' => '', 'name' => __('All'));
        }

        $langlist = ZLanguage::getInstalledLanguageNames();

        asort($langlist);

        foreach ($langlist as $k => $v) {
            $dropdown[] = array('id' => $k, 'name' => $v);
        }

        return $dropdown;
    }

    /**
     * Return the localized string for the given value
     *
     * @param value        The currently active/selected value
     *
     * @return The resulting HTML string
     */
    function getSelectorValue_Permission($value)
    {
        $perms = array();
        $perms[_PN_PERMISSION_BASIC_PRIVATE] = __('Private');
        $perms[_PN_PERMISSION_BASIC_GROUP]   = __('Group');
        $perms[_PN_PERMISSION_BASIC_USER]    = __('User');
        $perms[_PN_PERMISSION_BASIC_PUBLIC]  = __('Public');

        return $perms[$value];
    }

    /**
     * Return the HTML code for the Permission dropdown
     *
     * @param name           The name of the generated selector (optional) (default='permission')
     * @param selectedValue  The value which should be selected (optional) (default=2)
     *
     * @return The resulting HTML string
     */
    function getSelector_Permission($name = 'permission', $selectedValue = 'U')
    {
        if (!$name) {
            $name = 'permission';
        }

        $perms = array();
        $perms[_PN_PERMISSION_BASIC_PRIVATE] = __('Private');
        $perms[_PN_PERMISSION_BASIC_GROUP]   = __('Group');
        $perms[_PN_PERMISSION_BASIC_USER]    = __('User');
        $perms[_PN_PERMISSION_BASIC_PUBLIC]  = __('Public');

        return HtmlUtil::getSelector_Generic($name, $perms, $selectedValue, $defaultValue, $defaultText, $allValue, $allText, $submit, $disabled, $multipleSize);
    }

    /**
     * Return the HTML code for the Permission Level dropdown
     *
     * @param name          The name of the generated selector (optional) (default='permission')
     * @param selectedValue The value which should be selected (optional) (default=2)
     *
     * @return The resulting HTML string
     */
    function getSelector_PermissionLevel($name = 'permission', $selectedValue = '0')
    {
        $perms = array();
        $perms[_PN_PERMISSION_LEVEL_NONE]  = __('No access');
        $perms[_PN_PERMISSION_LEVEL_READ]  = __('Read access');
        $perms[_PN_PERMISSION_LEVEL_WRITE] = __('Write access');

        return HtmlUtil::getSelector_Generic($name, $perms, $selectedValue, $defaultValue, $defaultText, $allValue, $allText, $submit, $disabled, $multipleSize);
    }

    /**
     * Return the html for the PN user group selector
     *
     * @param name           The selector name
     * @param selectedValue  The currently selected value of the selector (optional) (default=0)
     * @param defaultValue   The default value of the selector (optional) (default=0)
     * @param defaultText    The text of the default value (optional) (default='')
     * @param allValue       The value to assign for the "All" choice (optional) (default=0)
     * @param allText        The text to display for the "All" choice (optional) (default='')
     * @param excludeList    A (string) list of IDs to exclude (optional) (default=null)
     * @param submit         Whether or not to auto-submit the selector (optional) (default=false)
     * @param disabled       Whether or not to disable selector (optional) (default=false)
     * @param multipleSize   The size to use for a multiple selector, 1 produces a normal/single selector (optional (default=1)
     *
     * @return The html for the user group selector
     */
    function getSelector_PNGroup($name = 'groupid', $selectedValue = 0, $defaultValue = 0, $defaultText = '', $allValue = 0, $allText = '', $excludeList = '', $submit = false, $disabled = false, $multipleSize = 1)
    {
        Loader::loadClass('UserUtil');

        $data = array();
        $grouplist = UserUtil::getPNGroups('', 'ORDER BY pn_name');
        foreach ($grouplist as $k => $v) {
            $id = $v['gid'];
            $disp = $v['name'];
            if (strpos($excludeList, ",$id,") === false) {
                $data[$id] = $disp;
            }
        }

        return HtmlUtil::getSelector_Generic($name, $data, $selectedValue, $defaultValue, $defaultText, $allValue, $allText, $submit, $disabled, $multipleSize);
    }

    /**
     * Return a PN array strcuture for the PN user dropdown box
     *
     * @param name           The selector name
     * @param gid            The group ID to get users for (optional) (default=null)
     * @param selectedValue  The currently selected value of the selector (optional) (default=0)
     * @param defaultValue   The default value of the selector (optional) (default=0)
     * @param defaultText    The text of the default value (optional) (default='')
     * @param allValue       The value to assign for the "All" choice (optional) (default='')
     * @param allText        The text to display for the "All" choice (optional) (default='')
     * @param excludeList    A (string) list of IDs to exclude (optional) (default=null)
     * @param submit         Whether or not to auto-submit the selector (optional) (default=false)
     * @param disabled       Whether or not to disable selector (optional) (default=false)
     * @param multipleSize   The size to use for a multiple selector, 1 produces a normal/single selector (optional (default=1)
     *
     * @return The string for the user group selector
     */
    function getSelector_PNUser($name = 'userid', $gid = null, $selectedValue = 0, $defaultValue = 0, $defaultText = '', $allValue = 0, $allText = '', $excludeList = '', $submit = false, $disabled = false, $multipleSize = 1)
    {
        Loader::loadClass('UserUtil');

        $where = '';
        if ($excludeList) {
            $where = "WHERE pn_uid NOT IN ($excludeList)";
        }

        if ($gid) {
            $users = UserUtil::getUsersForGroup($gid);
            if ($users) {
                $and = $where ? ' AND ' : '';
                $where .= $and . 'pn_uid IN (' . implode(',', $users) . ')';
            }
        }

        $data = array();
        $userlist = UserUtil::getPNUsers($where, 'ORDER BY pn_uname');
        foreach ($userlist as $k => $v) {
            $data[$k] = $v['uname'];
        }

        return HtmlUtil::getSelector_Generic($name, $data, $selectedValue, $defaultValue, $defaultText, $allValue, $allText, $submit, $disabled, $multipleSize);
    }

    /**
     * Return the html for the PNModule selector
     *
     * @param name           The selector name
     * @param selectedValue  The currently selected value of the selector (optional) (default=0)
     * @param defaultValue   The default value of the selector (optional) (default=0)
     * @param defaultText    The text of the default value (optional) (default='')
     * @param allValue       The value to assign the "All" choice (optional) (default=0)
     * @param allText        The text to display for the "All" choice (optional) (default='')
     * @param submit         Whether or not to auto-submit the selector
     * @param disabled       Whether or not to disable selector (optional) (default=false)
     * @param multipleSize   The size to use for a multiple selector, 1 produces a normal/single selector (optional (default=1)
     *
     * @return The string for the user group selector
     */
     function getSelector_PNModule ($name='moduleName', $selectedValue=0, $defaultValue=0, $defaultText='', $allValue=0, $allText='', $submit=false, $disabled=false, $multipleSize=1)
     {
         Loader::loadClass ('ModuleUtil');

         $data = array();
         $modules = ModuleUtil::getModulesByState(3, 'displayname');
         foreach ($modules as $module) {
             $modname        = $module['name'];
             $displayname    = $module['displayname'];
             $data[$modname] = $displayname;
         }

         return HtmlUtil::getSelector_Generic($name, $data, $selectedValue, $defaultValue, $defaultText, $allValue, $allText, $submit, $disabled, $multipleSize);
     }

    /**
     * Return the HTML for the date day selector
     *
     * @param selectedValue  The value which should be selected (default=0) (optional)
     * @param name           The name of the generated selector (default='day') (optional)
     * @param submit         Whether or not to auto-submit the selector
     * @param disabled       Whether or not to disable selector (optional) (default=false)
     * @param multipleSize   The size to use for a multiple selector, 1 produces a normal/single selector (optional (default=1)
     *
     * @return The generated HTML for the selector
     */
    function getSelector_DatetimeDay($selectedValue = 0, $name = 'day', $submit = false, $disabled = false, $multipleSize = 1)
    {
        if (!$name) {
            $name = 'day';
        }

        $data = array();
        for ($i = 1; $i < 32; $i++) {
            $val = sprintf("%02d", $i);
            $data[$val] = $val;
        }

        return HtmlUtil::getSelector_Generic($name, $data, $selectedValue, null, null, null, null, $submit, $disabled, $multipleSize = 1);
    }

    /**
     * Return the HTML for the date hour selector
     *
     * @param selectedValue  The value which should be selected (default=0) (optional)
     * @param name           The name of the generated selector (default='hour') (optional)
     * @param submit         Whether or not to auto-submit the selector
     * @param disabled       Whether or not to disable selector (optional) (default=false)
     * @param multipleSize   The size to use for a multiple selector, 1 produces a normal/single selector (optional (default=1)
     *
     * @return The generated HTML for the selector
     */
    function getSelector_DatetimeHour($selectedValue = 0, $name = 'hour', $submit = false, $disabled = false, $multipleSize = 1)
    {
        if (!$name) {
            $name = 'hour';
        }

        $data = array();
        for ($i = 0; $i < 24; $i++) {
            $val = sprintf("%02d", $i);
            $data[$val] = $val;
        }

        return HtmlUtil::getSelector_Generic($name, $data, $selectedValue, null, null, null, null, $submit, $disabled, $multipleSize = 1);
    }

    /**
     * Return the HTML for the date minute selector
     *
     * @param selectedValue  The value which should be selected (default=0) (optional)
     * @param name           The name of the generated selector (default='minute') (optional)
     * @param submit         Whether or not to auto-submit the selector
     * @param disabled       Whether or not to disable selector (optional) (default=false)
     * @param multipleSize   The size to use for a multiple selector, 1 produces a normal/single selector (optional (default=1)
     *
     * @return The generated HTML for the selector
     */
    function getSelector_DatetimeMinute($selectedValue = 0, $name = 'minute', $submit = false, $disabled = false, $multipleSize = 1)
    {
        if (!$name) {
            $name = 'minute';
        }

        $data = array();
        for ($i = 0; $i < 60; $i += 5) {
            $val = sprintf('%02d', $i);
            $data[$val] = $val;
        }

        return HtmlUtil::getSelector_Generic($name, $data, $selectedValue, null, null, null, null, $submit, $disabled, $multipleSize = 1);
    }

    /**
     * Return the HTML for the date month selector
     *
     * @param selectedValue  The value which should be selected (default=0) (optional)
     * @param name           The name of the generated selector (default='month') (optional)
     * @param submit         Whether or not to auto-submit the selector
     * @param disabled       Whether or not to disable selector (optional) (default=false)
     * @param multipleSize   The size to use for a multiple selector, 1 produces a normal/single selector (optional (default=1)
     *
     * @return The generated HTML for the selector
     */
    function getSelector_DatetimeMonth ($selected=0, $name='month', $submit=false, $disabled=false, $multipleSize=1, $text=0)
    {
        if (!$name) {
            $name = 'month';
        }

        if ($text) {
            $mnames = explode(' ', __('January February March April May June July August September October November December'));
        }
        array_unshift($mnames, 'noval');

        $id           = strtr($name, '[]', '__');
        $disabled     = $disabled ? 'disabled="disabled"' : '';
        $multiple     = $multipleSize > 1 ? 'multiple="multiple"' : '';
        $multipleSize = $multipleSize > 1 ? "size=\"$multipleSize\"" : '';
        $submit       = $submit ? 'onchange="this.form.submit();"' : '';

        $html = "<select name=\"$name\" id=\"$id\" $multipleSize $multiple $submit $disabled>";

        for ($i=1; $i<13; $i++) {
            $val = sprintf ("%02d", $i);
            $opt = $text ? $mnames[$i]:$val;
            $sel = ($i==$selected ? 'selected="selected"' : '');
            $html = $html . "<option value=\"$val\" $sel>$opt</option>";
        }

        $html = $html . '</select>';

        return $html;
    }

    /**
     * Return the HTML for the date year selector
     *
     * @param selectedValue  The value which should be selected (default=2009) (optional)
     * @param name           The name of the generated selector (default='year') (optional)
     * @param first          The start year for the selector (default=2003) (optional)
     * @param last           The name of the generated selector (default=2007) (optional)
     * @param submit         Whether or not to auto-submit the selector
     * @param disabled       Whether or not to disable selector (optional) (default=false)
     * @param multipleSize   The size to use for a multiple selector, 1 produces a normal/single selector (optional (default=1)
     *
     * @return The generated HTML for the selector
     */
    function getSelector_DatetimeYear($selectedValue = 2009, $name = 'year', $first = 2003, $last = 2008, $submit = false, $disabled = false, $multipleSize = 1)
    {
        if (!$name) {
            $name = 'year';
        }

        $data = array();
        for ($i = $first; $i < $last; $i++) {
            $data[$i] = $i;
        }

        return HtmlUtil::getSelector_Generic($name, $data, $selectedValue, null, null, null, null, $submit, $disabled, $multipleSize = 1);
    }

    /**
     * Return the HTML for the country selector
     *
     * @param name           The name of the generated selector (default='countries') (optional)
     * @param selectedValue  The value which is currently selected (default='') (optional)
     * @param defaultValue   The default value to select (default='') (optional)
     * @param defaultText    The text for the default value (default='') (optional)
     * @param allValue       The value to assign for the "All" choice (optional) (default=0)
     * @param allText        The text to display for the "All" choice (optional) (default='')
     * @param submit         Whether or not to auto-submit the selector
     * @param disabled       Whether or not to disable selector (optional) (default=false)
     * @param multipleSize   The size to use for a multiple selector, 1 produces a normal/single selector (optional (default=1)
     *
     * @return The generated HTML for the selector
     */
    function getSelector_Countries($name = 'countries', $selectedValue = '', $defaultValue = 0, $defaultText = '', $allValue = 0, $allText = '', $submit = false, $disabled = false, $multipleSize = 1)
    {
        $countries = ZLanguage::countryMap();
        asort($countries);

        return HtmlUtil::getSelector_Generic($name, $countries, $selectedValue, $defaultValue, $defaultText, $allValue, $allText, $submit, $disabled, $multipleSize);
    }

    /**
     * Same as PN HTMLApi function but adds javascript form submit code to selector
     */
    function FormSelectMultipleSubmit($fieldname, $data, $multiple = 0, $size = 1, $selected = '', $accesskey = '', $onchange = '')
    {
        if (empty($fieldname)) {
            return;
        }

        // Set up selected if required
        if (!empty($selected)) {
            for ($i = 0; !empty($data[$i]); $i++) {
                if ($data[$i]['id'] == $selected) {
                    $data[$i]['selected'] = 1;
                }
            }
        }

        $c = count($data);
        if ($c < $size) {
            $size = $c;
        }

        $idname = strtr($fieldname, '[]', '__');

        $output = '<select' . ' name="' . DataUtil::formatForDisplay($fieldname) . '"'
                            . ' id="' . DataUtil::formatForDisplay($idname) . '"'
                            . ' size="' . DataUtil::formatForDisplay($size) . '"'
                            . (($multiple == 1) ? ' multiple="multiple"' : '')
                            . ((empty($accesskey)) ? '' : ' accesskey="' . DataUtil::formatForDisplay($accesskey) . '"')
                            //. ' tabindex="'.$this->tabindex.'"'
                            . ($onchange ? " onchange=\"$onchange\"" : '') . '>';

        foreach ($data as $datum) {
            $output .= '<option value="' . DataUtil::formatForDisplay($datum['id']) . '"' . ((empty($datum['selected'])) ? '' : " selected='$datum[selected]'") . '>' . DataUtil::formatForDisplay($datum['name']) . '</option>';
        }

        $output .= '</select>';
        return $output;
    }
}
