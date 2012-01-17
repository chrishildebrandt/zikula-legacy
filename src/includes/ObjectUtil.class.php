<?php
/**
 * Zikula Application Framework
 *
 * @copyright Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: ObjectUtil.class.php 27582 2009-11-14 14:38:44Z mateo $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @author Robert Gasch rgasch@gmail.com
 * @package Zikula_Core
 * @subpackage ObjectUtil
 */

/**
 * ObjectUtil
 *
 * @package Zikula_Core
 * @subpackage ObjectUtil
 */
class ObjectUtil
{
    /**
     * Add standard PN architecture fields to the table definition
     *
     * @param columns The column list from the PNTables structure for the current table
     * @param col_prefix The column prefix
     *
     * @return Nothing, column array is altered in place
     */
    function addStandardFieldsToTableDefinition(&$columns, $col_prefix)
    {
        // ensure correct handling of prefix with and without underscore
        if ($col_prefix) {
            $plen = strlen($col_prefix);
            if ($col_prefix[$plen - 1] != '_')
                $col_prefix .= '_';
        }

        // add standard fields
        $columns['obj_status'] = $col_prefix . 'obj_status';
        $columns['cr_date'] = $col_prefix . 'cr_date';
        $columns['cr_uid'] = $col_prefix . 'cr_uid';
        $columns['lu_date'] = $col_prefix . 'lu_date';
        $columns['lu_uid'] = $col_prefix . 'lu_uid';

        return;
    }

    /**
     * Generate the SQL to create the standard PN architecture fields
     *
     * @param columns The column list from the PNTables structure for the current table
     *
     * @return The generated SQL string
     */
    function generateCreateSqlForStandardFields($columns)
    {
        $sql = "$columns[obj_status] CHAR(1)  NOT NULL DEFAULT 'A',
                $columns[cr_date]    DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00',
                $columns[cr_uid]     INTEGER  NOT NULL DEFAULT '0',
                $columns[lu_date]    DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00',
                $columns[lu_uid]     INTEGER  NOT NULL DEFAULT '0'";

        return $sql;
    }

    /**
     * Generate the ADODB DD field descruptors for the standard PN architecture fields
     *
     * @param columns The column list from the PNTables structure for the current table
     *
     * @return The modified list of ADODB DD strings
     */
    function addStandardFieldsToTableDataDefinition(&$columns)
    {
        $columns['obj_status'] = "C(1) NOTNULL DEFAULT 'A'";
        $columns['cr_date'] = "T    NOTNULL DEFAULT '1970-01-01 00:00:00'";
        $columns['cr_uid'] = "I    NOTNULL DEFAULT '0'";
        $columns['lu_date'] = "T    NOTNULL DEFAULT '1970-01-01 00:00:00'";
        $columns['lu_uid'] = "I    NOTNULL DEFAULT '0'";

        return;
    }

    /**
     * Generate the ADODB datadict entries to create the standard PN architecture fields
     *
     * @param $table  The table to add standard fields using ADODB dictionary method
     *
     * @return The generated SQL string
     */
    function generateCreateDataDictForStandardFields($table)
    {
        $pntables = pnDBGetTables();
        $columns = $pntables["{$table}_column"];
        $sql = ",
                $columns[obj_status] C(1) NOTNULL DEFAULT 'A',
                $columns[cr_date]    T    NOTNULL DEFAULT '1970-01-01 00:00:00',
                $columns[cr_uid]     I    NOTNULL DEFAULT '0',
                $columns[lu_date]    T    NOTNULL DEFAULT '1970-01-01 00:00:00',
                $columns[lu_uid]     I    NOTNULL DEFAULT '0'";

        return $sql;
    }

    /**
     * Set the standard PN architecture fields for object creation/insert
     *
     * @param object         The object we need to set the standard fields on
     * @param preserveValues whether or not to preserve value fields which have a valid value set (optional) (default=false)
     * @param idcolumn       The column name of the primary key column (optional) (default='id')
     *
     * @return Nothing, object is altered in place
     */
    function setStandardFieldsOnObjectCreate(&$obj, $preserveValues = false, $idcolumn = 'id')
    {
        if (!is_array($obj)) {
            pn_exit(__f('%s called on a non-object', 'ObjectUtil::setStandardFieldsOnObjectCreate'));
            return;
        }

        Loader::loadClass('DateUtil');

        $obj[$idcolumn] = (isset($obj[$idcolumn]) && $obj[$idcolumn] && $preserveValues ? $obj[$idcolumn] : null);
        $obj['cr_date'] = (isset($obj['cr_date']) && $obj['cr_date'] && $preserveValues ? $obj['cr_date'] : DateUtil::getDatetime());
        $obj['cr_uid'] = (isset($obj['cr_uid']) && $obj['cr_uid'] && $preserveValues ? $obj['cr_uid'] : pnUserGetVar('uid'));
        $obj['lu_date'] = (isset($obj['lu_date']) && $obj['lu_date'] && $preserveValues ? $obj['lu_date'] : DateUtil::getDatetime());
        $obj['lu_uid'] = (isset($obj['lu_uid']) && $obj['lu_uid'] && $preserveValues ? $obj['lu_uid'] : pnUserGetVar('uid'));

        if (is_null($obj['cr_uid'])) {
            $obj['cr_uid'] = 0;
        }
        if (is_null($obj['lu_uid'])) {
            $obj['lu_uid'] = 0;
        }

        return;
    }

    /**
     * Set the standard PN architecture fields to sane values for an object update
     *
     * @param object         The object we need to set the standard fields on
     * @param preserveValues whether or not to preserve value fields which have a valid value set (optional) (default=false)
     *
     * @return Nothing, object is altered in place
     */
    function setStandardFieldsOnObjectUpdate(&$obj, $preserveValues = false)
    {
        if (!is_array($obj)) {
            pn_exit(__f('%s called on a non-object', 'ObjectUtil::setStandardFieldsOnObjectUpdate'));
            return;
        }

        Loader::loadClass('DateUtil');

        $obj['lu_date'] = (isset($obj['lu_date']) && $obj['lu_date'] && $preserveValues ? $obj['lu_date'] : DateUtil::getDatetime());
        $obj['lu_uid'] = (isset($obj['lu_uid']) && $obj['lu_uid'] && $preserveValues ? $obj['lu_uid'] : pnUserGetVar('uid'));

        if (is_null($obj['lu_uid'])) {
            $obj['lu_uid'] = 0;
        }

        return;
    }

    /**
     * Remove the standard fields from the given object
     *
     * @param object    The object to operate on
     *
     * @return Nothing, object is altered in place
     */
    function removeStandardFieldsFromObject(&$obj)
    {
        unset($obj['obj_status']);
        unset($obj['cr_date']);
        unset($obj['cr_uid']);
        unset($obj['lu_date']);
        unset($obj['lu_uid']);

        return;
    }

    /**
     * If the specified field is set return it, otherwise return default
     *
     * @param object     The object to get the field from
     * @param field      The field to get
     * @param default     The default value to return if the field is not set on the object (default=null) (optional)
     *
     * @return The object field value or the default
     */
    function getField($obj, $field, $default = null)
    {
        if (isset($obj[$field])) {
            return $obj[$field];
        }

        return $default;
    }

    /**
     * Create an object of the reuqested type and set the cr_date and cr_uid fields.
     *
     * @param $type     The type of the object to create
     *
     * @return The newly created object
     */
    function createObject($type)
    {
        $pntable = pnDBGetTables();
        if (!$pntable[$type]) {
            return pn_exit(__f('%s: Unable to reference object type [%s]', array('ObjectUtil::createObject', $type)));
        }

        Loader::loadClass('DateUtil');
        $obj = array();
        $obj['__TYPE__'] = $type;
        $obj['cr_date'] = DateUtil::getDateTime();
        $obj['cr_uid'] = pnUserGetVar('uid');

        return $obj;
    }

    /**
     * Diff 2 objects/arrays
     *
     * @param object1     The first array/object
     * @param object2     The second object/array
     *
     * @return The difference between the two objects
     */
    function diff($obj1, $obj2)
    {
        if (!is_array($obj1)) {
            return pn_exit(__f('%s: %s is not an object.', array('ObjectUtil::diff', 'object1')));
        }
        if (!is_array($obj2)) {
            return pn_exit(__f('%s: %s is not an object.', array('ObjectUtil::diff', 'object2')));
        }

        return array_diff($obj1, $obj2);
    }

    /**
     * Provide an informative extended diff array when comparing 2 arrays
     *
     * @param a1        Array 1
     * @param a2        Array 2
     * @param detail    whether or not to give detailed update info (optional (default=false)
     * @param recurse   whether or not to recurse (optional) (default=true)
     *
     * @return A data array containing the diff results
     */
    function diffExtended($a1, $a2, $detail = false, $recurse = true)
    {
        $res = array();

        if (!is_array($a1) || !is_array($a2)) {
            return $res;
        }

        foreach ($a1 as $k => $v) {
            if (is_array($v)) {
                if ($recurse)
                    $res[$k] = ObjectUtil::diff($v, $a2[$k], $detail);
            } else if (!isset($a2[$k]))
                $res[$k] = 'I: ' . $v;
            else if ($v !== $a2[$k]) {
                if ($detail) {
                    $res[$k] = array();
                    $res[$k]['old'] = $v;
                    $res[$k]['new'] = $a2[$k];
                } else
                    $res[$k] = 'U: ' . $a2[$k];
            }

            unset($a2[$k]);
        }

        foreach ($a2 as $k => $v) {
            if (is_array($v)) {
                if ($recurse) {
                    $res[$k] = ObjectUtil::diff($a1[$k], $v, $detail);
                }
            } else {
                $res[$k] = 'D: ' . $v;
            }
        }

        return $res;
    }

    /**
     * Fixes the sequence numbers (column position) in a given table.
     * Needed, if an object was added or deleted in a table using the
     * arrow up/down feature.
     *
     * @param tablename   The tablename key for the PNTables structure
     * @param field       The name of the field we wish to resequence (optional) (default='position')
     * @param float       whether or not to use a float (optional) (default=false (uses integer))
     * @param idcolumn    The column which contains the unique ID
     *
     * @return nothing
     */
    function resequenceFields($tablename, $field = 'position', $float = false, $idcolumn = 'id')
    {
        $pntables = pnDBGetTables();
        $column = $pntables["{$tablename}_column"];
        $tab = $pntables[$tablename];

        if (!$column[$field]) {
            return pn_exit(__f('%s: there is no [%s] field in the [%s] table.', array('ObjectUtil::resequenceFields', $field, $tablename)));
        }

        $sql = "SELECT $column[$idcolumn], $column[$field]
                FROM $tab
                ORDER BY $column[$field]";
        $res = DBUtil::executeSQL($sql);

        $seq = ($float ? 1.0 : 1);
        while (list ($id, $curseq) = $res->fields) {
            $res->MoveNext();
            if ($curseq != $seq) {
                $sql = "UPDATE $tab SET $column[$field] = '" . DataUtil::formatForStore($seq) . "' WHERE $column[$idcolumn]='" . DataUtil::formatForStore($id) . "'";
                $upd = DBUtil::executeSQL($sql);
            }
            $seq += 1;
        }
    }

    /**
     * Increments or decremnts a sequence number (column position) in a table for
     * a given ID. If exists, it swaps the sequence of the field above or down.
     *
     * @param object     The object we wish to increment or decrement
     * @param tablename  The tablename key for the PNTables structure
     * @param direction  whether we want to increment or decrement the position of the object. Possible values are 'up' (default) and 'down'
     * @param field      The name of the field we wish to resequence
     * @param idcolumn   The column which contains the unique ID
     * @param field2     An additional field to consider in the where-clause
     * @param value2     An additional value to consider in the where-clause
     *
     * @return true/false on success/failure
     */
    function moveField($obj, $tablename, $direction = 'up', $field = 'position', $idcolumn = 'id', $field2 = '', $value2 = '')
    {
        if (!is_array($obj)) {
            return pn_exit(__f('%s: %s is not an array.', array('ObjectUtil::moveField', 'object')));
        }

        if (!isset($obj[$idcolumn])) {
            return pn_exit(__f('Unable to determine a valid ID in object [%s, %s]', array($idcolumn, 'ObjectUtil::moveField')));
        }

        $pntables = pnDBGetTables();
        $column = $pntables["{$tablename}_column"];
        $tab = $pntables[$tablename];

        if (!$column[$field]) {
            return pn_exit(__f('%s: there is no [%s] field in the [%s] table.', array('ObjectUtil::moveField', $field, $tablename)));
        }

        // Get info on current position of field
        $where = "$column[$idcolumn]='" . DataUtil::formatForStore($obj[$idcolumn]) . "'";
        $seq = DBUtil::selectField($tablename, $field, $where);

        // Get info on displaced field
        $direction = strtolower($direction);
        $where2 = '';
        if ($field2 && $value2) {
            $where2 = " AND $column[$field2]='" . DataUtil::formatForStore($value2) . "'";
        }

        if ($direction == 'up') {
            $sql = "SELECT $column[$idcolumn], $column[$field]
                    FROM $tab
                    WHERE $column[$field] < '" . DataUtil::formatForStore($seq) . "' $where2
                    ORDER BY $column[$field] DESC LIMIT 0,1";
        } else if ($direction == 'down') {
            $sql = "SELECT $column[$idcolumn], $column[$field]
                    FROM $tab
                    WHERE $column[$field] > '" . DataUtil::formatForStore($seq) . "' $where2
                    ORDER BY $column[$field] ASC LIMIT 0,1";
        } else {
            return pn_exit(__f('%s: invalid direction [%s] supplied.', array('ObjectUtil::moveField', $direction)));
        }

        $res = DBUtil::executeSQL($sql);
        if ($res->EOF) {
            // No field directly above or below that one
            return false;
        }

        list ($altid, $altseq) = $res->fields;

        // Swap sequence numbers
        $sql = "UPDATE $tab SET $column[$field]='" . DataUtil::formatForStore($seq) . "' WHERE $column[$idcolumn]='" . DataUtil::formatForStore($altid) . "'";
        $upd1 = DBUtil::executeSQL($sql);
        $sql = "UPDATE $tab SET $column[$field]='" . DataUtil::formatForStore($altseq) . "' WHERE $column[$idcolumn]='" . DataUtil::formatForStore($obj[$idcolumn]) . "'";
        $upd2 = DBUtil::executeSQL($sql);

        return true;
    }

    /**
     * Retrieve the attribute maps for the specified object
     *
     * @param object     The object whose attribute we wish to retrieve
     * @param type       The type of the given object
     * @param idcolumn   The column which holds the ID value (optional) (default='id')
     *
     * @return The object attribute (array)
     */
    function retrieveObjectAttributes($obj, $type, $idcolumn = 'id')
    {
        if (!$obj) {
            return pn_exit(__f('Invalid %1$s passed to %2$s.', array('object', __CLASS__.'::'.__FUNCTION__)));
        }

        if (!$type) {
            return pn_exit(__f('Invalid %1$s passed to %2$s.', array('type', __CLASS__.'::'.__FUNCTION__)));
        }

        // ensure that only objects with a valid ID are used
        if (!$obj[$idcolumn]) {
            return false;
        }

        if (!pnModAvailable('ObjectData') || !pnModDBInfoLoad('ObjectData')) {
            return false;
        }

        $pntables = pnDBGetTables();
        $objattr_table = $pntables['objectdata_attributes'];
        $objattr_column = $pntables['objectdata_attributes_column'];

        $where = "WHERE $objattr_column[object_id]= '" . DataUtil::formatForStore($obj[$idcolumn]) . "' AND
                        $objattr_column[object_type]='" . DataUtil::formatForStore($type) . "'";

        return DBUtil::selectObjectArray('objectdata_attributes', $where);
    }

    /**
     * Expand the given object with it's attributes
     *
     * @param object     The object whose attribute we wish to retrieve
     * @param type       The type of the given object
     * @param idcolumn   The column which holds the ID value (optional) (default='id')
     *
     * @return The object which has been altered in place
     */
    function expandObjectWithAttributes(&$obj, $type, $idcolumn = 'id')
    {
        if (!isset($obj[$idcolumn]) || !$obj[$idcolumn]) {
            return pn_exit(__f('Unable to determine a valid ID in object [%s, %s]', array($type, $idcolumn)));
        }

        $atrs = ObjectUtil::retrieveObjectAttributes($obj, $type, $idcolumn);
        if (!$atrs) {
            return false;
        }

        foreach ($atrs as $atr) {
            $obj['__ATTRIBUTES__'][$atr['attribute_name']] = $atr['value'];
        }

        return $obj;
    }

    /**
     * Store the attributes for the given objects.
     *
     * @param object     The object whose attributes we wish to store
     * @param type       The type of the given object
     * @param idcolumn   The idcolumn of the object (optional) (default='id')
     *
     * @return true/false on success/failure
     */
    function storeObjectAttributes($obj, $type, $idcolumn = 'id', $wasUpdateQuery = true)
    {
        if (!$obj) {
            return pn_exit(__f('Invalid %1$s passed to %2$s.', array('object', __CLASS__.'::'.__FUNCTION__)));
        }

        if (!$type) {
            return pn_exit(__f('Invalid %1$s passed to %2$s.', array('type', __CLASS__.'::'.__FUNCTION__)));
        }

        if (!$idcolumn) {
            return pn_exit(__f('Invalid %1$s passed to %2$s.', array('idcolumn', __CLASS__.'::'.__FUNCTION__)));
        }

        if (!isset($obj['__ATTRIBUTES__']) || !is_array($obj['__ATTRIBUTES__'])) {
            return false;
        }

        if (!pnModAvailable('ObjectData') || !pnModDBInfoLoad('ObjectData')) {
            return false;
        }

        $pntables = pnDBGetTables();
        $objattr_table = $pntables['objectdata_attributes'];
        $objattr_column = $pntables['objectdata_attributes_column'];

        $objID = $obj[$idcolumn];
        if (!$objID) {
            return pn_exit(__f('Unable to determine a valid ID in object [%s, %s]', array($type, $idcolumn)));
        }

        if ($wasUpdateQuery) {
            // delete old attribute values for this object
            $sql = "DELETE FROM $objattr_table WHERE $objattr_column[object_type] = '" . DataUtil::formatForStore($type) . "' AND
                                                     $objattr_column[object_id] = '" . DataUtil::formatForStore($objID) . "'";
            DBUtil::executeSQL($sql);
        }

        $atrs = (isset($obj['__ATTRIBUTES__']) ? $obj['__ATTRIBUTES__'] : null);
        if (!$atrs) {
            return true;
        }

        // process all the attribute fields
        $tobj = array();
        foreach ($atrs as $k => $v) {
            if (strlen($v) || $v == false) {
                // special treatment for false value, otherwise it doesn't get stored at all
                $tobj['attribute_name'] = $k;
                $tobj['object_id'] = $objID;
                $tobj['object_type'] = $type;
                $tobj['value'] = $v;

                DBUtil::insertObject($tobj, 'objectdata_attributes');
            }
        }

        return true;
    }

    /**
     * update the attributes for the given objects.
     *
     * @todo: check if the function can supersede storeObjectAttributes()
     *
     * @param obj        The object whose attributes we wish to store
     * @param type       The type of the given object
     * @param idcolumn   The idcolumn of the object (optional) (default='id')
     * @param force      Flag to force the attribute update
     *
     * @return true/false on success/failure
     */
    function updateObjectAttributes($obj, $type, $idcolumn = 'id', $force=false)
    {
        if (!$obj) {
            return pn_exit(__f('Invalid %1$s passed to %2$s.', array('object', __CLASS__.'::'.__FUNCTION__)));
        }

        if (!$type) {
            return pn_exit(__f('Invalid %1$s passed to %2$s.', array('type', __CLASS__.'::'.__FUNCTION__)));
        }

        if (!isset($obj['__ATTRIBUTES__']) || !is_array($obj['__ATTRIBUTES__'])) {
            return false;
        }

        if (!pnModAvailable('ObjectData') || !pnModDBInfoLoad('ObjectData')) {
            return false;
        }

        $objID = $obj[$idcolumn];
        if (!$objID) {
            return pn_exit(__f('Unable to determine a valid ID in object [%s, %s]', array($type, $idcolumn)));
        }

        $pntables = pnDBGetTables();
        $objattr_column = $pntables['objectdata_attributes_column'];

        // select all attributes so that we can check if we have to update or insert
        // this will be an assoc array of attributes with 'attribute_name' as key
        $where = 'WHERE ' . $objattr_column['object_type'] . '=\'' . DataUtil::formatForStore($type) . '\'
                    AND ' . $objattr_column['object_id'] . '=\'' . DataUtil::formatForStore($objID) . '\'';
        $attrs = DBUtil::selectObjectArray('objectdata_attributes', $where, null, null, null, 'attribute_name');

        // process all the attribute fields
        foreach ($obj['__ATTRIBUTES__'] as $k => $v) {
            // only fill empty attributes when force 
            if ($force || strlen($v)) {
                if (!array_key_exists($k, $attrs)) {
                    $newobj['attribute_name'] = $k;
                    $newobj['object_id'] = $objID;
                    $newobj['object_type'] = $type;
                    $newobj['value'] = $v;
                    DBUtil::insertObject($newobj, 'objectdata_attributes');
                } else {
                    $attrs[$k]['value'] = $v;
                    DBUtil::updateObject($attrs[$k], 'objectdata_attributes');
                }
            }
        }

        return true;
    }

    /**
     * Delete the attributes for the given object.
     *
     * @param object     The object whose attributes we wish to store
     * @param type       The type of the given object
     * @param idcolumn   The idcolumn of the object (optional) (default='id')
     *
     * @return the SQL result of the delete operation
     */
    function deleteObjectAttributes(&$obj, $type, $idcolumn = 'id')
    {
        if (!$obj) {
            return pn_exit(__f('Invalid %1$s passed to %2$s.', array('object', __CLASS__.'::'.__FUNCTION__)));
        }

        if (!$type) {
            return pn_exit(__f('Invalid %1$s passed to %2$s.', array('type', __CLASS__.'::'.__FUNCTION__)));
        }

        if (!$idcolumn) {
            return pn_exit(__f('Invalid %1$s passed to %2$s.', array('idcolumn', __CLASS__.'::'.__FUNCTION__)));
        }

        if (!pnModAvailable('ObjectData') || !pnModDBInfoLoad('ObjectData')) {
            return false;
        }

        $pntables = pnDBGetTables();
        $objattr_table = $pntables['objectdata_attributes'];
        $objattr_column = $pntables['objectdata_attributes_column'];

        // ensure module was successfully loaded
        if (!$objattr_table) {
            return false;
        }

        $objID = $obj[$idcolumn];
        if (!$objID) {
            return pn_exit(__f('Unable to determine a valid ID in object [%s, %s]', array($type, $idcolumn)));
        }

        $sql = "DELETE FROM $objattr_table WHERE $objattr_column[object_type] = '" . DataUtil::formatForStore($type) . "' AND
                                                 $objattr_column[object_id] = '" . DataUtil::formatForStore($objID) . "'";

        return DBUtil::executeSQL($sql);
    }

    /**
     * Delete a single attribute for the given object.
     *
     * @param object     The object whose attributes we wish to store
     * @param type       The type of the given object
     * @param idcolumn   The idcolumn of the object (optional) (default='id')
     *
     * @return the SQL result of the delete operation
     */
    function deleteObjectSingleAttribute($objID, $type, $attributename)
    {
        if (!$objID) {
            return pn_exit(__f('Invalid %1$s passed to %2$s.', array('objectid', __CLASS__.'::'.__FUNCTION__)));
        }

        if (!$type) {
            return pn_exit(__f('Invalid %1$s passed to %2$s.', array('type', __CLASS__.'::'.__FUNCTION__)));
        }

        if (!$attributename) {
            return pn_exit(__f('Invalid %1$s passed to %2$s.', array('attributename', __CLASS__.'::'.__FUNCTION__)));
        }

        $pntables = pnDBGetTables();
        $objattr_table = $pntables['objectdata_attributes'];
        $objattr_column = $pntables['objectdata_attributes_column'];

        // ensure module was successfully loaded
        if (!$objattr_table) {
            return false;
        }

        $sql = 'DELETE FROM ' . $objattr_table . ' WHERE ' . $objattr_column['attribute_name'] . ' = \'' . DataUtil::formatForStore($attributename) . '\' AND '
                                                           . $objattr_column['object_type'] . ' = \'' . DataUtil::formatForStore($type) . '\' AND '
                                                           . $objattr_column['object_id'] . ' = \'' . DataUtil::formatForStore($objID) . '\'';

        return (bool) DBUtil::executeSQL($sql);
    }

    /**
     * Delete the all attributes for the given tab
     *
     * @param type       The type/tablename we wish to delete attributes for
     *
     * @return the SQL result of the delete operation
     */
    function deleteAllObjectTypeAttributes($type)
    {
        if (!pnModAvailable('ObjectData') || !pnModDBInfoLoad('ObjectData')) {
            return false;
        }

        $pntables = pnDBGetTables();
        $objattr_table = $pntables['objectdata_attributes'];
        $objattr_column = $pntables['objectdata_attributes_column'];

        $sql = "DELETE FROM $objattr_table WHERE $objattr_column[object_type] = '" . DataUtil::formatForStore($type) . "'";
        $res = DBUtil::executeSQL($sql);

        return $res;
    }

    /**
     * Retrieve a list of attributes defined in the system
     *
     * @param sort         The column to sort by (optional) (default='attribute_name')
     *
     * @return the system attributes field array
     */
    function getSystemAttributes($sort = 'attribute_name')
    {
        if (!pnModAvailable('ObjectData') || !pnModDBInfoLoad('ObjectData')) {
            return false;
        }

        $pntables = pnDBGetTables();
        $objattr_table = $pntables['objectdata_attributes'];
        $objattr_column = $pntables['objectdata_attributes_column'];

        // ensure module was successfully loaded
        if (!$objattr_table) {
            return false;
        }

        $atrs = DBUtil::selectFieldArray('objectdata_attributes', 'attribute_name', '', 'attribute_name', true);
        return $atrs;
    }

    /**
     * Retrieve the count for a given attribute name
     *
     * @param atrName     The name of the attribute
     *
     * @return The count for the given attribute
     */
    function getAttributeCount($atrName)
    {
        if (!pnModAvailable('ObjectData') || !pnModDBInfoLoad('ObjectData')) {
            return false;
        }

        $pntables = pnDBGetTables();
        $objattr_column = $pntables['objectdata_attributes_column'];

        $where = "$objattr_column[attribute_name]='" . DataUtil::formatForStore($atrName) . "'";
        return DBUtil::selectObjectCount('objectdata_attributes', $where);
    }

    /**
     * Ensure that a meta-data object has reasonable default values
     *
     * @param obj        The object we wish to store metadata for
     * @param tablename  The object's tablename
     * @param idcolumn   The object's idcolumn (optional) (default='id')
     *
     * @return Altered meta object (meta object is also altered in place)
     */
    function fixObjectMetaData(&$obj, $tablename, $idcolumn)
    {
        if (!isset($obj['__META__']) || !is_array($obj['__META__'])) {
            $obj['__META__'] = array();
        }

        $meta = & $obj['__META__'];

        $meta['table'] = $tablename;
        $meta['idcolumn'] = $idcolumn;

        if (!isset($meta['module']) || !$meta['module']) {
            $meta['module'] = pnModGetName();
        }

        if (!isset($meta['obj_id']) || !$meta['obj_id']) {
            $meta['obj_id'] = (isset($obj[$idcolumn]) ? $obj[$idcolumn] : -1);
        }

        return $meta;
    }

    /**
     * Insert a meta data object
     *
     * @param obj        The object we wish to store metadata for
     * @param tablename  The object's tablename
     * @param idcolumn   The object's idcolumn (optional) (default='id')
     *
     * @return The result from the metadata insert operation
     */
    function insertObjectMetaData(&$obj, $tablename, $idcolumn = 'id')
    {
        if (!pnModAvailable('ObjectData') || !pnModDBInfoLoad('ObjectData')) {
            return false;
        }

        $meta = ObjectUtil::fixObjectMetaData($obj, $tablename, $idcolumn);
        if ($meta['obj_id'] > 0) {
            $result = DBUtil::insertObject($meta, 'objectdata_meta');
            $obj['__META__']['metaid'] = $meta['id'];
            return $meta['id'];
        }

        return false;
    }

    /**
     * Update a meta data object
     *
     * @param obj        The object we wish to store metadata for
     * @param tablename  The object's tablename
     * @param idcolumn   The object's idcolumn (optional) (default='id')
     *
     * @return The result from the metadata insert operation
     */
    function updateObjectMetaData(&$obj, $tablename, $idcolumn = 'id')
    {
        if (!pnModAvailable('ObjectData') || !pnModDBInfoLoad('ObjectData')) {
            return false;
        }

        if (!isset($obj['__META__']['id'])) {
            return false;
        }

        $meta = $obj['__META__'];
        if ($meta['obj_id'] > 0) {
            return DBUtil::updateObject($meta, 'objectdata_meta');
        }

        return true;
    }

    /**
     * Delete a meta data object
     *
     * @param obj        The object we wish to delete metadata for
     * @param tablename  The object's tablename
     * @param idcolumn   The object's idcolumn (optional) (default='id')
     *
     * @return The result from the metadata insert operation
     */
    function deleteObjectMetaData(&$obj, $tablename, $idcolumn = 'id')
    {
        if (!pnModAvailable('ObjectData') || !pnModDBInfoLoad('ObjectData')) {
            return false;
        }

        ObjectUtil::fixObjectMetaData($obj, $tablename, $idcolumn);

        if (isset($obj['__META__']['id']) && $obj['__META__']['id']) {
            $rc = DBUtil::deleteObjectByID($obj['__META__'], 'objectdata_meta');

        } elseif (isset($obj['__META__']['idcolumn']) && $obj['__META__']['obj_id']) {
            $pntables = pnDBGetTables();
            $meta_column = $pntables['objectdata_meta_column'];

            $meta = $obj['__META__'];
            $where = "WHERE $meta_column[module]='" . DataUtil::formatForStore($meta['module']) . "'
                        AND $meta_column[table]='" . DataUtil::formatForStore($meta['table']) . "'
                        AND $meta_column[idcolumn]='" . DataUtil::formatForStore($meta['idcolumn']) . "'
                        AND $meta_column[obj_id]='" . DataUtil::formatForStore($meta['obj_id']) . "'";

            $rc = DBUtil::deleteObject(array(), 'objectdata_meta', $where);
        }

        return (boolean) $rc;
    }

    /**
     * Retrieve object meta data
     *
     * @param obj        The object we wish to retrieve metadata for
     * @param tablename  The object's tablename
     * @param idcolumn   The object's idcolumn (optional) (default='id')
     *
     * @return The object with the meta data filled in
     */
    function retrieveObjectMetaData(&$obj, $tablename, $idcolumn = 'id')
    {
        if (!pnModAvailable('ObjectData') || !pnModDBInfoLoad('ObjectData')) {
            return false;
        }

        $meta = ObjectUtil::fixObjectMetaData($obj, $tablename, $idcolumn);
        if ($meta['obj_id'] > 0) {
            $pntables = pnDBGetTables();
            $meta_column = $pntables['objectdata_meta_column'];

            $where = "WHERE $meta_column[module]='" . DataUtil::formatForStore($meta['module']) . "'
                        AND $meta_column[table]='" . DataUtil::formatForStore($meta['table']) . "'
                        AND $meta_column[idcolumn]='" . DataUtil::formatForStore($meta['idcolumn']) . "'
                        AND $meta_column[obj_id]='" . DataUtil::formatForStore($meta['obj_id']) . "'";

            return DBUtil::selectObject('objectdata_meta', $where);
        }

        return true;
    }

    /**
     * Expand an object with it's Meta data
     *
     * @param obj        The object we wish to get the metadata for
     * @param tablename  The object's tablename
     * @param idcolumn   The object's idcolumn (optional) (default='id')
     *
     * @return The object with the meta data filled in. The object passed in is altered in place
     */
    function expandObjectWithMeta(&$obj, $tablename, $idcolumn = 'id')
    {
        if (!isset($obj[$idcolumn]) || !$obj[$idcolumn]) {
            return pn_exit(__f('Unable to determine a valid ID in object [%s, %s]', array($type, $idcolumn)));
        }

        $meta = ObjectUtil::retrieveObjectMetaData($obj, $tablename, $idcolumn);
        if (!$meta) {
            return false;
        }

        $obj['__META__'] = $meta;
        return $obj;
    }

    /**
     * Insert a categorization data object
     *
     * @param obj        The object we wish to store categorization data for
     * @param tablename  The object's tablename
     * @param idcolumn   The object's idcolumn (optional) (default='id')
     *
     * @return The result from the category data insert operation
     */
    function storeObjectCategories($obj, $tablename, $idcolumn = 'id', $wasUpdateQuery = true)
    {
        if (!$obj) {
            return pn_exit(__f('Invalid %1$s passed to %2$s.', array('object', __CLASS__.'::'.__FUNCTION__)));
        }

        if (!$tablename) {
            return pn_exit(__f('Invalid %1$s passed to %2$s.', array('tablename', __CLASS__.'::'.__FUNCTION__)));
        }

        if (!$idcolumn) {
            return pn_exit(__f('Invalid %1$s passed to %2$s.', array('idcolumn', __CLASS__.'::'.__FUNCTION__)));
        }

        if (!pnModDBInfoLoad('Categories')) {
            return false;
        }

        if (!isset($obj['__CATEGORIES__']) || !is_array($obj['__CATEGORIES__']) || !$obj['__CATEGORIES__']) {
            return false;
        }

        if ($wasUpdateQuery) {
            ObjectUtil::deleteObjectCategories($obj, $tablename, $idcolumn);
        }

        // ensure that we don't store duplicate object mappings
        $values = array();
        foreach ($obj['__CATEGORIES__'] as $k => $v) {
            if (isset($values[$v])) {
                unset($obj['__CATEGORIES__'][$k]);
            } else {
                $values[$v] = 1;
            }
        }

        // cache category id arrays to improve performance with DBUtil::(insert|update)ObjectArray()
        static $modTableCategoryIDs = array();

        // Get the ids of the categories properties of the object
        $modname = isset($obj['__META__']['module']) ? $obj['__META__']['module'] : pnModGetName();
        $reg_key = $modname . '_' . $tablename;

        if (!isset($modTableCategoryIDs[$reg_key])) {
            Loader::loadClass('CategoryRegistryUtil');
            $modTableCategoryIDs[$reg_key] = CategoryRegistryUtil::getRegisteredModuleCategoriesIds($modname, $tablename);
        }
        $reg_ids = $modTableCategoryIDs[$reg_key];

        $cobj = array();
        $cobj['table'] = $tablename;
        $cobj['obj_idcolumn'] = $idcolumn;

        $res = true;
        foreach ($obj['__CATEGORIES__'] as $prop => $cat) {
            // if there's all the data and the Registry exists
            // the category is mapped
            if ($cat && $prop && isset($reg_ids[$prop])) {
                $cobj['id'] = '';
                $cobj['modname'] = $modname;
                $cobj['obj_id'] = $obj[$idcolumn];
                $cobj['category_id'] = $cat;
                $cobj['reg_id'] = $reg_ids[$prop];

                $res = DBUtil::insertObject($cobj, 'categories_mapobj');
            }
        }

        return (boolean) $res;
    }

    /**
     * Delete a meta data object
     *
     * @param obj        The object we wish to delete categorization data for
     * @param tablename  The object's tablename
     * @param idcolumn   The object's idcolumn (optional) (default='obj_id')
     *
     * @return The result from the metadata insert operation
     */
    function deleteObjectCategories($obj, $tablename, $idcolumn = 'obj_id')
    {
        if (!pnModDBInfoLoad('Categories')) {
            return false;
        }

        $where = "cmo_table='" . DataUtil::formatForStore($tablename) . "' AND cmo_obj_id='" . DataUtil::formatForStore($obj[$idcolumn]) . "' AND cmo_obj_idcolumn='" . DataUtil::formatForStore($idcolumn) . "'";
        return (boolean) DBUtil::deleteWhere('categories_mapobj', $where);
    }

    /**
     * Retrieve object category data
     *
     * @param obj        The object we wish to retrieve metadata for
     * @param tablename  The object's tablename
     * @param idcolumn   The object's idcolumn (optional) (default='id')
     *
     * @return The object with the meta data filled in
     */
    function retrieveObjectCategoriesList($obj, $tablename, $idcolumn = 'id')
    {
        static $cache;

        $key = $tablename . '_' . $obj[$idcolumn];
        if (isset($cache[$key])) {
            return $cache[$key];
        }

        if (!pnModDBInfoLoad('Categories')) {
            return false;
        }

        $pntabs = pnDBGetTables();
        $cat = $pntabs['categories_mapobj_column'];

        $where = "WHERE tbl.$cat[table]='" . DataUtil::formatForStore($tablename) . "'
                    AND tbl.$cat[obj_idcolumn]='" . DataUtil::formatForStore($idcolumn) . "'
                    AND tbl.$cat[obj_id]='" . DataUtil::formatForStore($obj[$idcolumn]) . "'";
        $orderby = "ORDER BY tbl.$cat[category_id]";

        $joinInfo[] = array('join_table' => 'categories_registry', 'join_field' => 'property', 'object_field_name' => 'property', 'compare_field_table' => 'reg_id', 'compare_field_join' => 'id');

        $cache[$key] = DBUtil::selectExpandedFieldArray('categories_mapobj', $joinInfo, 'category_id', $where, $orderby, false, 'property');
        return $cache[$key];
    }

    /**
     * Retrieve object category data
     *
     * @param obj        The object we wish to retrieve metadata for
     * @param tablename  The object's tablename
     * @param idcolumn   The object's idcolumn (optional) (default='id')
     * @param assocKey   The field to use for the associative array index (optional) (default='id')
     *
     * @return The object with the meta data filled in
     */
    function retrieveObjectCategoriesObjects($obj, $tablename, $idcolumn = 'id', $assocKey = '', $enablePermissionCheck = true)
    {
        if (!Loader::loadClass('CategoryUtil')) {
            return pn_exit(__f('Error! Unable to load class [%s]', 'CategoryUtil'));
        }

        $catlist = ObjectUtil::retrieveObjectCategoriesList($obj, $tablename, $idcolumn);
        if (!$catlist) {
            return array();
        }

        $cats = implode(',', array_values($catlist));
        $where = "WHERE cat_id IN ($cats)";
        $catsdata = CategoryUtil::getCategories($where, '', 'id', $enablePermissionCheck);

        $result = array();
        foreach ($catlist as $prop => $cat) {
            if (isset($catsdata[$cat])) {
                $result[$prop] = $catsdata[$cat];
            }
        }

        return $result;
    }

    /**
     * Expand an object array with it's category data
     *
     * @param objArray   The object array we wish to get the category for
     * @param tablename  The object's tablename
     * @param idcolumn   The object's idcolumn (optional) (default='id')
     * @param field      The category field to return the object's category info (optional) (default='id')
     *
     * @return The object with the meta data filled in. The object passed in is altered in place
     */
    function expandObjectArrayWithCategories(&$objArray, $tablename, $idcolumn = 'id', $field = 'id', $locale = 'eng')
    {
        if (!pnModDBInfoLoad('Categories')) {
            return false;
        }

        if (!$objArray || !is_array($objArray)) {
            return false;
        }

        $pntabs = pnDBGetTables();
        $tab = $pntabs['categories_mapobj'];
        $col = $pntabs['categories_mapobj_column'];

        $w1 = array();
        $w2 = array();
        foreach ($objArray as $obj) {
            $w1[] = DataUtil::formatForStore($obj[$idcolumn]);
        }

        $t = implode(',', $w1);
        $w2[] = "tbl.$col[obj_id] IN (" . $t . ')';
        $w2[] = "tbl.$col[table]='" . DataUtil::formatForStore($tablename) . "' AND tbl.$col[obj_idcolumn]='" . DataUtil::formatForStore($idcolumn) . "' ";
        $where = "WHERE " . implode(' AND ', $w2);
        $sort = "ORDER BY tbl.$col[obj_id], tbl.$col[category_id]";

        $joinInfo[] = array('join_table' => 'categories_registry', 'join_field' => 'property', 'object_field_name' => 'property', 'compare_field_table' => 'reg_id', 'compare_field_join' => 'id');

        $maps = DBUtil::selectExpandedObjectArray('categories_mapobj', $joinInfo, $where, $sort);
        if (!$maps) {
            return false;
        }

        // since we don't know the order in which our data array will be, we
        // have to do this iteratively. However, this is still a lot faster
        // than doing a select for every data line.
        $catlist = array();
        foreach ($objArray as $k => $obj) {
            $last = null;
            foreach ($maps as $map) {
                if ($map['obj_id'] == $obj[$idcolumn]) {
                    $last = $map['obj_id'];
                    $prop = $map['property'];
                    $catid = $map['category_id'];
                    $objArray[$k]['__CATEGORIES__'][$prop] = $catid;
                    $catlist[] = $catid;
                }

                if ($last && $last != $map['obj_id'])
                    break;
            }
        }

        // now retrieve the full category data
        $where = 'WHERE cat_id IN (' . implode(',', $catlist) . ')';

        //$cats  = DBUtil::selectObjectArray ('categories_category', $where, '', -1, -1, 'id');
        if (!($catClass = Loader::loadClassFromModule('Categories', 'category', true))) {
            return pn_exit(__f('Unable to load class [%s] for module [%s]', array('category', 'Categories')));
        }

        $catArray = new $catClass();
        $data = $catArray->get($where, '', -1, -1, 'id');

        // use the cagtegory map created previously to build the object category array
        foreach ($objArray as $k => $obj) {
            foreach ($obj['__CATEGORIES__'] as $prop => $cat) {
                $data[$cat]['path'] = str_replace('__SYSTEM__', __('Root Category'), $data[$cat]['path']);
                $objArray[$k]['__CATEGORIES__'][$prop] = $data[$cat];
            }
        }

        // now generate the relative paths
        //$rootCatID = CategoryRegistryUtil::getRegisteredModuleCategory (pnModGetName(), $tablename, 'main_table', '/__SYSTEM__/Modules/Quotes/Default');
        //postProcessExpandedObjectArrayCategories ($objArray, $rootCatID, false);

        return $objArray;
    }

    /**
     * Expand an object with it's category data
     *
     * @param obj        The object we wish to get the metadata for
     * @param tablename  The object's tablename
     * @param idcolumn   The object's idcolumn (optional) (default='id')
     * @param field      The category field to return the object's category info (optional) (default='id')
     * @param assocKey   The field to use for the associative array index (optional) (default='id')
     *
     * @return The object with the meta data filled in. The object passed in is altered in place
     */
    function expandObjectWithCategories(&$obj, $tablename, $idcolumn = 'id', $assocKey = '')
    {
        if (!isset($obj[$idcolumn]) || !$obj[$idcolumn]) {
            return pn_exit(__f('Unable to determine a valid ID in object [%s, %s]', array($type, $idcolumn)));
        }

        if (!pnModDBInfoLoad('Categories')) {
            return false;
        }

        $cats = ObjectUtil::retrieveObjectCategoriesObjects($obj, $tablename, $idcolumn, $assocKey, false);
        $obj['__CATEGORIES__'] = $cats;

        // now generate the relative paths
        //$module = pnModGetName();
        //$rootCatID = CategoryRegistryUtil::getRegisteredModuleCategory (pnModGetName(), $tablename, 'main_table', '/__SYSTEM__/Modules/Quotes/Default');
        //postProcessExpandedObjectCategories ($obj, $rootCatID);

        return $obj;
    }

    /**
     * Post-process an object-array's expanded categories to generate relative paths
     *
     * @param objArray    The object array we wish to post-process
     * @param rootCatID   The root category ID for the relative path creation
     * @param includeRoot whether or not to include the root folder in the relative path (optional) (default=false)
     *
     * @return The object-array with the additionally expanded category data is altered in place and returned
     */
    function postProcessExpandedObjectArrayCategories(&$objArray, $rootCats, $includeRoot = false)
    {
        if (!$objArray) {
            return pn_exit(__f('Invalid object in %s', 'postProcessExpandedObjectArrayCategories'));
        }

        $ak = array_keys($objArray);
        foreach ($ak as $k) {
            if (isset($objArray[$k]['__CATEGORIES__']) && $objArray[$k]['__CATEGORIES__']) {
                ObjectUtil::postProcessExpandedObjectCategories($objArray[$k]['__CATEGORIES__'], $rootCats, $includeRoot);
            }
        }

        return $objArray;
    }

    /**
     * Post-process an object's expanded category data to generate relative paths
     *
     * @param obj         The object we wish to post-process
     * @param rootCat     The root category ID for the relative path creation
     * @param includeRoot whether or not to include the root folder in the relative path (optional) (default=false)
     *
     * @return The object with the additionally expanded category data is altered in place and returned
     */
    function postProcessExpandedObjectCategories(&$obj, $rootCatsIDs, $includeRoot = false)
    {
        if (!$obj) {
            return pn_exit(__f('Invalid object in %s', 'postProcessExpandedObjectCategories'));
        }

        if (!Loader::loadClass('CategoryUtil')) {
            return pn_exit(__f('Error! Unable to load class [%s]', 'CategoryUtil'));
        }

        $rootCats = CategoryUtil::getCategoriesByRegistry($rootCatsIDs);

        if (empty($rootCats)) {
            return false;
        }

        // if the function was called to process the object categories
        if (isset($obj['__CATEGORIES__'])) {
            $ak = array_keys($obj['__CATEGORIES__']);
            foreach ($ak as $prop) {
                CategoryUtil::buildRelativePathsForCategory($rootCats[$prop], $obj['__CATEGORIES__'][$prop], $includeRoot);
            }
            // else, if the function was called to process the categories array directly
        } else {
            $ak = array_keys($obj);
            foreach ($ak as $prop) {
                CategoryUtil::buildRelativePathsForCategory($rootCats[$prop], $obj[$prop], $includeRoot);
            }
        }

        return;
    }
}
