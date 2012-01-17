<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2001, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: filter.category.class.php 28222 2010-02-08 02:22:59Z drak $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula
 * @subpackage FilterUtil
 */

Loader::loadClass('FilterUtil_Build', FILTERUTIL_CLASS_PATH);

class FilterUtil_Filter_category extends FilterUtil_PluginCommon implements FilterUtil_Build
{

    private $property;

    /**
     * Constructor
     *
     * @access public
     * @param array $config Configuration
     * @return object FilterUtil_Plugin_pgList
     */
    public function __construct($config)
    {
        parent::__construct($config);

        if (isset($config['fields']) && is_array($config['fields'])) {
            $this->addFields($config['fields']);
        }

        if (isset($config['property'])) {
            $this->setProperty($config['property']);
        } else {
            $this->setProperty('Main');
        }

        if (isset($config['ops']) && (!isset($this->ops) || !is_array($this->ops))) {
            $this->activateOperators($config['ops']);
        } else {
            $this->activateOperators($this->availableOperators());
        }
    }

    /**
     * Adds fields to list in common way
     *
     * @access public
     * @param mixed $fields Fields to add
     */
    public function addFields($fields)
    {
        if (is_array($fields)) {
            foreach ($fields as $fld) {
                $this->addFields($fld);
            }
        } elseif (!empty($fields) && !$this->fieldExists($fields) && array_search($fields, $this->fields) === false) {
            $this->fields[] = $fields;
        }
    }

    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Adds operators
     *
     * @access public
     * @param mixed $op Operators to activate
     */
    public function activateOperators($op)
    {
        if (is_array($op)) {
            foreach ($op as $v) {
                $this->activateOperators($v);
            }
        } elseif (!empty($op) && array_search($op, $this->ops) === false && array_search($op, $this->availableOperators()) !== false) {
            $this->ops[] = $op;
        }
    }

    /**
     * Get operators
     *
     * @access public
     * @return array Set of Operators and Arrays
     */
    public function getOperators()
    {
        $fields = $this->getFields();
        if ($this->default == true) {
            $fields[] = '-';
        }
        $ops = array();
        foreach ($this->ops as $op) {
            $ops[$op] = $fields;
        }
        return $ops;
    }

    /**
     * Set the category property
     *
     * @see CategoryUtil
     * @param mixed $property Category Property
     */
    public function setProperty($property)
    {
        $this->property = (array) $property;
    }

    public function availableOperators()
    {
        return array(
                        'eq',
                        'ne',
                        'sub');
    }

    /**
     * return SQL code
     *
     * @access public
     * @param string $field Field name
     * @param string $op Operator
     * @param string $value Test value
     * @return string SQL code
     */
    function getSQL($field, $op, $value)
    {
        if (array_search($op, $this->availableOperators()) === false || array_search($field, $this->fields) === false) {
            return '';
        }
        Loader::loadClass('CategoryUtil');

        $items = array();
        $items[] = $value;
        if ($op == 'sub') {
            $cats = CategoryUtil::getSubCategories($value);
            foreach ($cats as $item) {
                $items[] = $item['id'];
            }
        }

        $filter = array('__META__' => array('module' => $this->module));
        foreach ($this->property as $prop) {
            $filter[$prop] = $items;
        }

        $where = DBUtil::generateCategoryFilterWhere($this->pntable, false, $filter);
        if ($op == 'ne') {
            $where = 'NOT ' . $where;
        }
        return array('where' => $where);
    }
}