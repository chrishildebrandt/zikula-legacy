<?php
/**
 * Zikula Application Framework
 *
 * @copyright Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: pninit.php 27268 2009-10-30 10:02:50Z mateo $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @author Robert Gasch rgasch@gmail.com
 * @package Zikula_Core
 */

/**
 * initialise module
 */
function Categories_init()
{
    if (!DBUtil::createTable('categories_category')) {
        return false;
    }

    // Create the index
    if (!DBUtil::createIndex('idx_categories_parent', 'categories_category', 'parent_id') || !DBUtil::createIndex('idx_categories_is_leaf', 'categories_category', 'is_leaf') || !DBUtil::createIndex('idx_categories_name', 'categories_category', 'name') || !DBUtil::createIndex('idx_categories_ipath', 'categories_category', array(
        'ipath',
        'is_leaf',
        'status')) || !DBUtil::createIndex('idx_categories_status', 'categories_category', 'status') || !DBUtil::createIndex('idx_categories_ipath_status', 'categories_category', array('ipath', 'status'))) {
        return false;
    }

    Categories_insertData_10();

    // Set autonumber to 10000 (for DB's that support autonumber fields)
    $cat['id'] = 9999;
    DBUtil::insertObject($cat, 'categories_category', 'id', true);

    // for postgres, we need to explicitly set the sequence value to reflect the inserted data
    $dbType = DBConnectionStack::getConnectionDBType();
    if ($dbType == 'postgres') {
        $pntables = pnDBGetTables();
        $tab = $pntables['categories_category'];
        $col = $pntables['categories_category_column'];
        $seq = $tab . '_cat_id_seq';
        $sql = "SELECT setval('$seq', (SELECT MAX($col[id]) + 1 FROM $tab))";
        DBUtil::executeSQL($sql);
    }

    DBUtil::deleteObjectByID('categories_category', 9999, 'id');

    Categories_createTables_101();

    pnModSetVar('Categories', 'userrootcat', '/__SYSTEM__/Users');
    pnModSetVar('Categories', 'allowusercatedit', 0);
    pnModSetVar('Categories', 'autocreateusercat', 0);
    pnModSetVar('Categories', 'autocreateuserdefaultcat', 0);
    pnModSetVar('Categories', 'userdefaultcatname', 'Default');

    // Initialisation successful
    return true;
}

/**
 * upgrade the module from an old version
 *
 * This function must consider all the released versions of the module!
 * If the upgrade fails at some point, it returns the last upgraded version.
 *
 * @param        string   $oldVersion   version number string to upgrade from
 * @return       mixed    true on success, last valid version string or false if fails
 */
function Categories_upgrade($oldversion)
{
    switch ($oldversion)
    {
         case '1.04':
            categories_upgrade_fixSerializedData();
            categories_upgrade_MigrateLanguageCodes();

        case '1.1':
            // future upgrade routines
    }

    return true;
}

/**
 * delete module
 */
function Categories_delete()
{
    DBUtil::dropTable('categories_category');
    DBUtil::dropTable('categories_mapobj');
    DBUtil::dropTable('categories_mapmeta');
    DBUtil::dropTable('categories_registry');

    pnModDelVar('Categories');

    // delete other modules use of categories flag
    $pntable = pnDBGetTables();
    $cols = $pntable['module_vars_column'];
    $name = DataUtil::formatForStore('enablecategorization');
    $where = "$cols[name]='$name'";
    $res = (bool) DBUtil::deleteWhere('module_vars', $where);

    // Deletion successful
    return true;
}

/**
 * create tables
 */
function Categories_createTables_101()
{
    if (!DBUtil::createTable('categories_mapmeta')) {
        return false;
    }

    if (!DBUtil::createIndex('idx_categories_mapmeta', 'categories_mapmeta', 'meta_id')) {
        return false;
    }

    if (!DBUtil::createTable('categories_mapobj')) {
        return false;
    }

    if (!DBUtil::createIndex('idx_categories_mapobj', 'categories_mapobj', array('modname', 'table', 'obj_id', 'obj_idcolumn'))) {
        return false;
    }

    if (!DBUtil::createTable('categories_registry')) {
        return false;
    }

    if (!DBUtil::createIndex('idx_categories_registry', 'categories_registry', array('modname', 'table', 'property'))) {
        return false;
    }

    return true;
}

/**
 * insert data
 */
function Categories_insertData_10()
{
    $objArray = array();
    $objArray[] = array('id' => 1, 'parent_id' => 0, 'is_locked' => 1, 'is_leaf' => 0, 'name' => '__SYSTEM__', 'display_name' => 'b:0;', 'display_desc' => 'b:0;', 'path' => '/__SYSTEM__', 'ipath' => '/1', 'status' => 'A');
    $objArray[] = array('id' => 2, 'parent_id' => 1, 'is_locked' => 0, 'is_leaf' => 0, 'name' => 'Modules', 'display_name' => Categories_makeDisplayName(__('Modules')), 'display_desc' => Categories_makeDisplayDesc(), 'path' => '/__SYSTEM__/Modules', 'ipath' => '/1/2', 'status' => 'A');
    $objArray[] = array('id' => 3, 'parent_id' => 1, 'is_locked' => 0, 'is_leaf' => 0, 'name' => 'General', 'display_name' => Categories_makeDisplayName(__('General')), 'display_desc' => Categories_makeDisplayDesc(), 'path' => '/__SYSTEM__/General', 'ipath' => '/1/3', 'status' => 'A');
    $objArray[] = array(
        'id' => 4,
        'parent_id' => 3,
        'is_locked' => 0,
        'is_leaf' => 0,
        'name' => 'YesNo',
        'display_name' => Categories_makeDisplayName(__('Yes/No')),
        'display_desc' => Categories_makeDisplayDesc(),
        'path' => '/__SYSTEM__/General/YesNo',
        'ipath' => '/1/3/4',
        'status' => 'A');
    $objArray[] = array(
        'id' => 5,
        'parent_id' => 4,
        'is_locked' => 0,
        'is_leaf' => 1,
        'name' => '1 - Yes',
        'display_name' => 'b:0;',
        'display_desc' => 'b:0;',
        'path' => '/__SYSTEM__/General/YesNo/1 - Yes',
        'ipath' => '/1/3/4/5',
        'status' => 'A',
        '__ATTRIBUTES__' => array('code' => 'Y'));
    $objArray[] = array(
        'id' => 6,
        'parent_id' => 4,
        'is_locked' => 0,
        'is_leaf' => 1,
        'name' => '2 - No',
        'display_name' => 'b:0;',
        'display_desc' => 'b:0;',
        'path' => '/__SYSTEM__/General/YesNo/2 - No',
        'ipath' => '/1/3/4/6',
        'status' => 'A',
        '__ATTRIBUTES__' => array('code' => 'N'));
    $objArray[] = array(
        'id' => 10,
        'parent_id' => 3,
        'is_locked' => 0,
        'is_leaf' => 0,
        'name' => 'Publication Status (extended)',
        'display_name' => Categories_makeDisplayName(__('Publication status (extended)')),
        'display_desc' => Categories_makeDisplayDesc(),
        'path' => '/__SYSTEM__/General/Publication Status Extended',
        'ipath' => '/1/3/10',
        'status' => 'A');
    $objArray[] = array(
        'id' => 11,
        'parent_id' => 10,
        'is_locked' => 0,
        'is_leaf' => 1,
        'name' => 'Pending',
        'display_name' => Categories_makeDisplayName(__('Pending')),
        'display_desc' => Categories_makeDisplayDesc(),
        'path' => '/__SYSTEM__/General/Publication Status Extended/Pending',
        'ipath' => '/1/3/10/11',
        'status' => 'A',
        '__ATTRIBUTES__' => array('code' => 'P'));
    $objArray[] = array(
        'id' => 12,
        'parent_id' => 10,
        'is_locked' => 0,
        'is_leaf' => 1,
        'name' => 'Checked',
        'display_name' => Categories_makeDisplayName(__('Checked')),
        'display_desc' => Categories_makeDisplayDesc(),
        'path' => '/__SYSTEM__/General/Publication Status Extended/Checked',
        'ipath' => '/1/3/10/12',
        'status' => 'A',
        '__ATTRIBUTES__' => array('code' => 'C'));
    $objArray[] = array(
        'id' => 13,
        'parent_id' => 10,
        'is_locked' => 0,
        'is_leaf' => 1,
        'name' => 'Approved',
        'display_name' => Categories_makeDisplayName(__('Approved')),
        'display_desc' => Categories_makeDisplayDesc(),
        'path' => '/__SYSTEM__/General/Publication Status Extended/Approved',
        'ipath' => '/1/3/10/13',
        'status' => 'A',
        '__ATTRIBUTES__' => array('code' => 'A'));
    $objArray[] = array(
        'id' => 14,
        'parent_id' => 10,
        'is_locked' => 0,
        'is_leaf' => 1,
        'name' => 'On-line',
        'display_name' => Categories_makeDisplayName(__('On-line')),
        'display_desc' => Categories_makeDisplayDesc(),
        'path' => '/__SYSTEM__/General/Publication Status Extended/Online',
        'ipath' => '/1/3/10/14',
        'status' => 'A',
        '__ATTRIBUTES__' => array('code' => 'O'));
    $objArray[] = array(
        'id' => 15,
        'parent_id' => 10,
        'is_locked' => 0,
        'is_leaf' => 1,
        'name' => 'Rejected',
        'display_name' => Categories_makeDisplayName(__('Rejected')),
        'display_desc' => Categories_makeDisplayDesc(),
        'path' => '/__SYSTEM__/General/Publication Status Extended/Rejected',
        'ipath' => '/1/3/10/15',
        'status' => 'A',
        '__ATTRIBUTES__' => array('code' => 'R'));
    $objArray[] = array(
        'id' => 16,
        'parent_id' => 3,
        'is_locked' => 0,
        'is_leaf' => 0,
        'name' => 'Gender',
        'display_name' => Categories_makeDisplayName(__('Gender')),
        'display_desc' => Categories_makeDisplayDesc(),
        'path' => '/__SYSTEM__/General/Gender',
        'ipath' => '/1/3/16',
        'status' => 'A');
    $objArray[] = array(
        'id' => 17,
        'parent_id' => 16,
        'is_locked' => 0,
        'is_leaf' => 1,
        'name' => 'Male',
        'display_name' => Categories_makeDisplayName(__('Male')),
        'display_desc' => Categories_makeDisplayDesc(),
        'path' => '/__SYSTEM__/General/Gender/Male',
        'ipath' => '/1/3/16/17',
        'status' => 'A',
        '__ATTRIBUTES__' => array('code' => 'M'));
    $objArray[] = array(
        'id' => 18,
        'parent_id' => 16,
        'is_locked' => 0,
        'is_leaf' => 1,
        'name' => 'Female',
        'display_name' => Categories_makeDisplayName(__('Female')),
        'display_desc' => Categories_makeDisplayDesc(),
        'path' => '/__SYSTEM__/General/Gender/Female',
        'ipath' => '/1/3/16/18',
        'status' => 'A',
        '__ATTRIBUTES__' => array('code' => 'F'));
    $objArray[] = array(
        'id' => 19,
        'parent_id' => 3,
        'is_locked' => 0,
        'is_leaf' => 0,
        'name' => 'Title',
        'display_name' => Categories_makeDisplayName(__('Title')),
        'display_desc' => Categories_makeDisplayDesc(),
        'path' => '/__SYSTEM__/General/Title',
        'ipath' => '/1/3/19',
        'status' => 'A');
    $objArray[] = array(
        'id' => 20,
        'parent_id' => 19,
        'is_locked' => 0,
        'is_leaf' => 1,
        'name' => 'Mr',
        'display_name' => Categories_makeDisplayName(__('Mr.')),
        'display_desc' => Categories_makeDisplayDesc(),
        'path' => '/__SYSTEM__/General/Title/Mr',
        'ipath' => '/1/3/19/20',
        'status' => 'A');
    $objArray[] = array(
        'id' => 21,
        'parent_id' => 19,
        'is_locked' => 0,
        'is_leaf' => 1,
        'name' => 'Mrs',
        'display_name' => Categories_makeDisplayName(__('Mrs.')),
        'display_desc' => Categories_makeDisplayDesc(),
        'path' => '/__SYSTEM__/General/Title/Mrs',
        'ipath' => '/1/3/19/21',
        'status' => 'A');
    $objArray[] = array(
        'id' => 22,
        'parent_id' => 19,
        'is_locked' => 0,
        'is_leaf' => 1,
        'name' => 'Ms',
        'display_name' => Categories_makeDisplayName(__('Ms.')),
        'display_desc' => Categories_makeDisplayDesc(),
        'path' => '/__SYSTEM__/General/Title/Ms',
        'ipath' => '/1/3/19/22',
        'status' => 'A');
    $objArray[] = array(
        'id' => 23,
        'parent_id' => 19,
        'is_locked' => 0,
        'is_leaf' => 1,
        'name' => 'Miss',
        'display_name' => Categories_makeDisplayName(__('Miss')),
        'display_desc' => Categories_makeDisplayDesc(),
        'path' => '/__SYSTEM__/General/Title/Miss',
        'ipath' => '/1/3/19/23',
        'status' => 'A');
    $objArray[] = array(
        'id' => 24,
        'parent_id' => 19,
        'is_locked' => 0,
        'is_leaf' => 1,
        'name' => 'Dr',
        'display_name' => Categories_makeDisplayName(__('Dr.')),
        'display_desc' => Categories_makeDisplayDesc(),
        'path' => '/__SYSTEM__/General/Title/Dr',
        'ipath' => '/1/3/19/24',
        'status' => 'A');
    $objArray[] = array(
        'id' => 25,
        'parent_id' => 3,
        'is_locked' => 0,
        'is_leaf' => 0,
        'name' => 'ActiveStatus',
        'display_name' => Categories_makeDisplayName(__('Activity status')),
        'display_desc' => Categories_makeDisplayDesc(),
        'path' => '/__SYSTEM__/General/ActiveStatus',
        'ipath' => '/1/3/25',
        'status' => 'A');
    $objArray[] = array(
        'id' => 26,
        'parent_id' => 23,
        'is_locked' => 0,
        'is_leaf' => 1,
        'name' => 'Active',
        'display_name' => Categories_makeDisplayName(__('Active')),
        'display_desc' => Categories_makeDisplayDesc(),
        'path' => '/__SYSTEM__/General/ActiveStatus/Active',
        'ipath' => '/1/3/25/26',
        'status' => 'A',
        '__ATTRIBUTES__' => array('code' => 'A'));
    $objArray[] = array(
        'id' => 27,
        'parent_id' => 23,
        'is_locked' => 0,
        'is_leaf' => 1,
        'name' => 'Inactive',
        'display_name' => Categories_makeDisplayName(__('Inactive')),
        'display_desc' => Categories_makeDisplayDesc(),
        'path' => '/__SYSTEM__/General/ActiveStatus/Inactive',
        'ipath' => '/1/3/25/27',
        'status' => 'A',
        '__ATTRIBUTES__' => array('code' => 'I'));
    $objArray[] = array(
        'id' => 28,
        'parent_id' => 3,
        'is_locked' => 0,
        'is_leaf' => 0,
        'name' => 'Publication status (basic)',
        'display_name' => Categories_makeDisplayName(__('Publication status (basic)')),
        'display_desc' => Categories_makeDisplayDesc(),
        'path' => '/__SYSTEM__/General/Publication Status Basic',
        'ipath' => '/1/3/28',
        'status' => 'A');
    $objArray[] = array(
        'id' => 29,
        'parent_id' => 28,
        'is_locked' => 0,
        'is_leaf' => 1,
        'name' => 'Pending',
        'display_name' => Categories_makeDisplayName(__('Pending')),
        'display_desc' => Categories_makeDisplayDesc(),
        'path' => '/__SYSTEM__/General/Publication Status Basic/Pending',
        'ipath' => '/1/3/28/29',
        'status' => 'A',
        '__ATTRIBUTES__' => array('code' => 'P'));
    $objArray[] = array(
        'id' => 30,
        'parent_id' => 28,
        'is_locked' => 0,
        'is_leaf' => 1,
        'name' => 'Approved',
        'display_name' => Categories_makeDisplayName(__('Approved')),
        'display_desc' => Categories_makeDisplayDesc(),
        'path' => '/__SYSTEM__/General/Publication Status Basic/Approved',
        'ipath' => '/1/3/28/30',
        'status' => 'A',
        '__ATTRIBUTES__' => array('code' => 'A'));
    $objArray[] = array(
	'id' => 31,
	'parent_id' => 1,
	'is_locked' => 0,
	'is_leaf' => 0,
	'name' => 'Users',
	'display_name' => Categories_makeDisplayName(__('Users')),
	'display_desc' => Categories_makeDisplayDesc(),
	'path' => '/__SYSTEM__/Users',
	'ipath' => '/1/31',
	'status' => 'A');
    $objArray[] = array(
        'id' => 32,
        'parent_id' => 2,
        'is_locked' => 0,
        'is_leaf' => 0,
        'name' => 'Global',
        'display_name' => Categories_makeDisplayName(__('Global')),
        'display_desc' => Categories_makeDisplayDesc(),
        'path' => '/__SYSTEM__/Modules/Global',
        'ipath' => '/1/2/32',
        'status' => 'A');
    $objArray[] = array(
        'id' => 33,
        'parent_id' => 32,
        'is_locked' => 0,
        'is_leaf' => 1,
        'name' => 'Blogging',
        'display_name' => Categories_makeDisplayName(__('Blogging')),
        'display_desc' => Categories_makeDisplayDesc(),
        'path' => '/__SYSTEM__/Modules/Global/Blogging',
        'ipath' => '/1/2/32/33',
        'status' => 'A');
    $objArray[] = array(
        'id' => 34,
        'parent_id' => 32,
        'is_locked' => 0,
        'is_leaf' => 1,
        'name' => 'Music and audio',
        'display_name' => Categories_makeDisplayName(__('Music and audio')),
        'display_desc' => Categories_makeDisplayDesc(),
        'path' => '/__SYSTEM__/Modules/Global/MusicAndAudio',
        'ipath' => '/1/2/32/34',
        'status' => 'A');
    $objArray[] = array(
        'id' => 35,
        'parent_id' => 32,
        'is_locked' => 0,
        'is_leaf' => 1,
        'name' => 'Art and photography',
        'display_name' => Categories_makeDisplayName(__('Art and photography')),
        'display_desc' => Categories_makeDisplayDesc(),
        'path' => '/__SYSTEM__/Modules/Global/ArtAndPhotography',
        'ipath' => '/1/2/32/35',
        'status' => 'A');
    $objArray[] = array(
        'id' => 36,
        'parent_id' => 32,
        'is_locked' => 0,
        'is_leaf' => 1,
        'name' => 'Writing and thinking',
        'display_name' => Categories_makeDisplayName(__('Writing and thinking')),
        'display_desc' => Categories_makeDisplayDesc(),
        'path' => '/__SYSTEM__/Modules/Global/WritingAndThinking',
        'ipath' => '/1/2/32/36',
        'status' => 'A');
    $objArray[] = array(
        'id' => 37,
        'parent_id' => 32,
        'is_locked' => 0,
        'is_leaf' => 1,
        'name' => 'Communications and media',
        'display_name' => Categories_makeDisplayName(__('Communications and media')),
        'display_desc' => Categories_makeDisplayDesc(),
        'path' => '/__SYSTEM__/Modules/Global/CommunicationsAndMedia',
        'ipath' => '/1/2/32/37',
        'status' => 'A');
    $objArray[] = array(
        'id' => 38,
        'parent_id' => 32,
        'is_locked' => 0,
        'is_leaf' => 1,
        'name' => 'Travel and culture',
        'display_name' => Categories_makeDisplayName(__('Travel and culture')),
        'display_desc' => Categories_makeDisplayDesc(),
        'path' => '/__SYSTEM__/Modules/Global/TravelAndCulture',
        'ipath' => '/1/2/32/38',
        'status' => 'A');
    $objArray[] = array(
        'id' => 39,
        'parent_id' => 32,
        'is_locked' => 0,
        'is_leaf' => 1,
        'name' => 'Science and technology',
        'display_name' => Categories_makeDisplayName(__('Science and technology')),
        'display_desc' => Categories_makeDisplayDesc(),
        'path' => '/__SYSTEM__/Modules/Global/ScienceAndTechnology',
        'ipath' => '/1/2/32/39',
        'status' => 'A');
    $objArray[] = array(
        'id' => 40,
        'parent_id' => 32,
        'is_locked' => 0,
        'is_leaf' => 1,
        'name' => 'Sport and activities',
        'display_name' => Categories_makeDisplayName(__('Sport and activities')),
        'display_desc' => Categories_makeDisplayDesc(),
        'path' => '/__SYSTEM__/Modules/Global/SportAndActivities',
        'ipath' => '/1/2/32/40',
        'status' => 'A');
    $objArray[] = array(
        'id' => 41,
        'parent_id' => 32,
        'is_locked' => 0,
        'is_leaf' => 1,
        'name' => 'Business and work',
        'display_name' => Categories_makeDisplayName(__('Business and work')),
        'display_desc' => Categories_makeDisplayDesc(),
        'path' => '/__SYSTEM__/Modules/Global/BusinessAndWork',
        'ipath' => '/1/2/32/41',
        'status' => 'A');
    $objArray[] = array(
        'id' => 42,
        'parent_id' => 32,
        'is_locked' => 0,
        'is_leaf' => 1,
        'name' => 'Activism and action',
        'display_name' => Categories_makeDisplayName(__('Activism and action')),
        'display_desc' => Categories_makeDisplayDesc(),
        'path' => '/__SYSTEM__/Modules/Global/ActivismAndAction',
        'ipath' => '/1/2/32/42',
        'status' => 'A');

    DBUtil::insertObjectArray($objArray, 'categories_category', 'id', true);
}

/**
 * update the value addons tables
 */
function Categories_updateValueAddons_104()
{
    // Array of the modules to update
    $mods = array('News' => array('stories' => 'Main'), 'Pages' => array('pages' => 'Main'), 'FAQ' => array('faqanswer' => 'Main'), 'Feeds' => array('feeds' => 'Main'), 'Reviews' => array('reviews' => 'Main'), 'Content' => array('page' => 'primary'));

    $pntables = pnDBGetTables();
    $regcol = $pntables['categories_registry_column'];
    $mapcol = $pntables['categories_mapobj_column'];

    // Update all the items mapped if there's a Register of the module
    foreach ($mods as $module => $data) {
        foreach ($data as $table => $property) {
            $where = "$regcol[modname]='$module' AND $regcol[table]='$table' AND $regcol[property]='$property'";
            $reg_id = DBUtil::selectObject('categories_registry', $where, array('id'));
            if ($reg_id !== false) {
                $obj = array('reg_id' => $reg_id['id']);
                $where = "$mapcol[modname]='$module' AND $mapcol[table]='$table'";
                DBUtil::updateObject($obj, 'categories_mapobj', $where, 'sid');
            }
        }
    }

    return true;
}

function Categories_makeDisplayName($name)
{

    return serialize(array(ZLanguage::getLanguageCode() => $name));
}

function Categories_makeDisplayDesc()
{
    return serialize(array('eng' => ''));
}

function categories_upgrade_fixSerializedData()
{
    // fix serialised data in categories
    $objArray = DBUtil::selectObjectArray('categories_category');
    DBUtil::truncateTable('categories_category');

    foreach ($objArray as $category)
    {
        $data = DataUtil::mb_unserialize($category['display_name']);
        $category['display_name'] = serialize($data);
        $data = DataUtil::mb_unserialize($category['display_desc']);
        $category['display_desc'] = serialize($data);
        DBUtil::insertObject($category, 'categories_category', 'id', true, true);
    }

    return;
}

function categories_upgrade_MigrateLanguageCodes()
{
    $objArray = DBUtil::selectObjectArray('categories_category');
    DBUtil::truncateTable('categories_category');

    $newObjArray = array();
    foreach ($objArray as $category)
    {
        // translate display_name l3 -> l2
        $data = unserialize($category['display_name']);
        if (is_array($data)) {
            $array = array();
            foreach ($data as $l3 => $v) {
                $l2 = ZLanguage::translateLegacyCode($l3);
                if ($l2) {
                    $array[$l2] = $v;
                }
            }
            $category['display_name'] = serialize($array);
        }

        // translate display_desc l3 -> l2
        $data = unserialize($category['display_desc']);
        if (is_array($data)) {
            $array = array();
            foreach ($data as $l3 => $v) {
                $l2 = ZLanguage::translateLegacyCode($l3);
                if ($l2) {
                    $array[$l2] = $v;
                }
            }
            $category['display_desc'] = serialize($array);
        }

        // commit
        DBUtil::insertObject($category, 'categories_category', 'id', true);
    }

    return;
}

