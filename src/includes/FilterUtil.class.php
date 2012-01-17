<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2001, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: FilterUtil.class.php 28256 2010-02-17 06:28:37Z drak $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula
 * @subpackage FilterUtil
 */

/**
 * Define Class path
 */
define('FILTERUTIL_CLASS_PATH', 'includes/FilterUtil/');

Loader::loadClass('FilterUtil_Plugin', FILTERUTIL_CLASS_PATH);
Loader::loadClass('FilterUtil_PluginCommon', FILTERUTIL_CLASS_PATH);
Loader::loadClass('FilterUtil_Common', FILTERUTIL_CLASS_PATH);

/**
 * This Class adds a Pagesetter like filter feature to PostNuke
 */
class FilterUtil extends FilterUtil_Common
{

    /**
     * The Input variable name
     */
    private $varname;

    /**
     * Plugin object
     */
    private $plugin;

    /**
     * Filter object holder
     */
    private $obj;

    /**
     * Filter string holder
     */
    private $filter;

    /**
     * Filter SQL holder
     */
    private $sql;

    /**
     * Constructor
     *
     * @param string $module Module name
     * @param string $table	Table name
     * @param array $args Mixed arguments
     * @access public
     */
    public function __construct($module, $table, $args = array())
    {
        $this->setVarName('filter');
        $args['module'] = $module;
        $args['table'] = $table;
        parent::__construct($args);
        $config = array();
        $this->addCommon($config);
        $this->plugin = new FilterUtil_Plugin($args, array('default' => array()));

        if (isset($args['plugins'])) {
            $this->plugin->loadPlugins($args['plugins']);
        }
        if (isset($args['varname'])) {
            $this->setVarName($args['varname']);
        }

        return $this;
    }

    /**
     * Set name of input variable of filter
     *
     * @access public
     * @param string $name name of input variable
     * @return bool true on success, false otherwise
     */
    public function setVarName($name)
    {
        if (!is_string($name)) {
            return false;
        }

        $this->varname = $name;
    }
    
    /**
     * Get plugin manager class.
     * 
     * @return FilterUtil_Plugin
     */
    public function getPlugin()
    {
        return $this->plugin;
    }

    //++++++++++++++++ Object handling +++++++++++++++++++


    /**
     * strip brackets around a filterstring
     *
     * @access private
     * @param string $filter Filterstring
     * @return string edited filterstring
     */
    private function stripBrackets($filter)
    {
        if (substr($filter, 0, 1) == '(' && substr($filter, -1) == ')') {
            return substr($filter, 1, -1);
        }

        return $filter;
    }

    /**
     * Create a condition object out of a string
     *
     * @access private
     * @param string $filter Condition string
     * @return array condition object
     */
    private function makeCondition($filter)
    {
        if (strpos($filter, ':')) {
            $parts = explode(':', $filter, 3);
        } elseif (strpos($filter, '^')) {
            $parts = explode('^', $filter, 3);
        }

        $obj = array(   'field' => false,
                        'op' => false,
                        'value' => false);

        if (isset($parts[2]) && substr($parts[2], 0, 1) == '$') {
            $value = FormUtil::getPassedValue(substr($parts[2], 1), null);
            if (empty($value) && !is_numeric($value)) {
                return false;
            }
            $obj['value'] = $value;
        } elseif (isset($parts) && is_array($parts) && count($parts) > 2) {
            $obj['value'] = $parts[2];
        }

        if (isset($parts) && is_array($parts) && count($parts) > 1) {
            $obj['field'] = $parts[0];
            $obj['op'] = $parts[1];
        }

        if (!$obj['field'] || !$obj['op']) { // no valid Condition
            return false;
        }

        $obj = $this->plugin->replace($obj['field'], $obj['op'], $obj['value']);

        return $obj;
    }

    /**
     * Help function to generate an object out of a string
     *
     * @access private
     * @param string $filter	Filterstring
     */
    private function GenObjectRecursive($filter)
    {
        $obj = array();
        $cycle = 0;
        $op = 0;
        $level = 0;
        $sub = false;
        for ($i = 0; $i < strlen($filter); $i++) {
            $c = substr($filter, $i, 1);
            switch ($c) {
                case ',': // Operator: AND
                    if (!empty($string)) {
                        $sub = $this->makeCondition($string);
                        if ($sub != false && count($sub) > 0) {
                            $obj[$op] = $sub;
                            $sub = false;
                        }
                    }
                    if (count($obj) > 0) {
                        $op = 'and' . $cycle++;
                    }
                    $string = '';
                    break;
                case '*': // Operator: OR
                    if (!empty($string)) {
                        $sub = $this->makeCondition($string);
                        if ($sub != false && count($sub) > 0) {
                            $obj[$op] = $sub;
                            $sub = false;
                        }
                    }
                    if (count($obj) > 0) {
                        $op = 'or' . $cycle++;
                    }
                    $string = '';
                    break;
                case '(': // Subquery
                    $level++;
                    while ($level != 0 && $i <= strlen($filter)) { // get end bracket
                        $i++;
                        $c = substr($filter, $i, 1);
                        switch ($c) {
                            case '(':
                                $level++;
                                break;
                            case ')':
                                $level--;
                                break;
                        }
                        if ($level > 0) {
                            $string .= $c;
                        }
                    }
                    if (!empty($string)) {
                        $sub = $this->GenObjectRecursive($string);
                        if ($sub != false && count($sub) > 0) {
                            $obj[$op] = $sub;
                            $sub = false;
                        }
                    }
                    $string = '';
                    break;
                default:
                    $string .= $c;
                    break;
            }
        }
        if (!empty($string)) {
            $sub = $this->makeCondition($string);
            if ($sub != false && count($sub) > 0) {
                $obj[$op] = $sub;
                $sub = false;
            }
        }

        return $obj;
    }

    /**
     * Generate the filter object from a string
     *
     * @access public
     */
    public function genObject()
    {
        $this->obj = $this->GenObjectRecursive($this->getFilter());
    }

    /**
     * Get the filter object
     *
     * @access public
     * @return array filter object
     */
    public function getObject()
    {
        if (!isset($this->obj) || !is_array($this->obj)) {
            $this->GenObject();
        }
        return $this->obj;
    }

    //---------------- Object handling ---------------------
    //++++++++++++++++ Filter handling +++++++++++++++++++++
    /**
     * Get all filters from Input
     *
     * @return array Array of filters
     */
    public function getFiltersFromInput()
    {
        $i = 1;
        $filter = array();

        // Get unnumbered filter string
        $filterStr = FormUtil::getPassedValue($this->varname, '');
        if (!empty($filterStr)) {
            $filter[] = $filterStr;
        }

        // Get filter1 ... filterN
        while (true) {
            $filterURLName = $this->varname . "$i";
            $filterStr = FormUtil::getPassedValue($filterURLName, '');

            if (empty($filterStr)) {
                break;
            }

            $filter[] = $filterStr;
            ++$i;
        }

        return $filter;
    }

    /**
     * Get filterstring
     *
     * @access public
     * @return string $filter Filterstring
     */
    public function getFilter()
    {
        if (!isset($this->filter) || empty($this->filter)) {
            $filter = $this->GetFiltersFromInput();
            if (is_array($filter) && count($filter) > 0) {
                $this->filter = "(" . implode(')*(', $filter) . ")";
            }
        }

        if ($this->filter == '()') {
            $this->filter = '';
        }

        return $this->filter;
    }

    /**
     * Set filterstring
     *
     * @access public
     * @param mixed $filter Filter string or array
     */
    public function setFilter($filter)
    {
        if (is_array($filter)) {
            $this->filter = "(" . implode(')*(', $filter) . ")";
        } else {
            $this->filter = $filter;
        }
        $this->obj = false;
        $this->sql = false;

    }

    //--------------- Filter handling ----------------------
    //+++++++++++++++ SQL Handling +++++++++++++++++++++++++


    /**
     * Help function for generate the filter SQL from a Filter-object
     *
     * @access private
     * @param array $obj Object array
     * @return array Where and Join sql
     */
    private function genSqlRecursive($obj)
    {
        if (!is_array($obj) || count($obj) == 0) {
            return '';
        }
        if (isset($obj['field']) && !empty($obj['field'])) {
            $obj['value'] = DataUtil::formatForStore($obj['value']);
            $res = $this->plugin->getSQL($obj['field'], $obj['op'], $obj['value']);
            $res['join'] = & $this->join;
            return $res;
        } else {
            $where = '';
            if (isset($obj[0]) && is_array($obj[0])) {
                $sub = $this->genSqlRecursive($obj[0]);
                if (!empty($sub['where'])) {
                    $where .= $sub['where'];
                }
                unset($obj[0]);
            }
            foreach ($obj as $op => $tmp) {
                $op = strtoupper(substr($op, 0, 3)) == 'AND' ? 'AND' : 'OR';
                if (strtoupper($op) == 'AND' || strtoupper($op) == 'OR') {
                    $sub = $this->genSqlRecursive($tmp);
                    if (!empty($sub['where'])) {
                        $where .= ' ' . strtoupper($op) . ' ' . $sub['where'];
                    }
                }
            }
        }
        return array(
                        'where' => (empty($where) ? '' : "(\n $where \n)"),
                        'join' => &$this->join);
    }

    /**
     * Generate where/join SQL
     *
     * access public
     */
    public function genSQL()
    {
        $object = $this->getObject();
        $this->sql = $this->genSqlRecursive($object);
    }

    /**
     * Get where/join SQL
     *
     * @access public
     * @return array Array with where and join
     */
    public function getSQL()
    {
        if (!isset($this->sql) || !is_array($this->sql)) {
            $this->genSQL();
        }
        return $this->sql;
    }
}
