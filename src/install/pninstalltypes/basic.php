<?php
/** 
 * define what additional modules to install and which 
 * folders to place them in
 */
function installer_basic_modules()
{
    // note in addition to these modules Modules, Blocks, Users, Permissions & Groups are all
    // installed - zikula will not start without these
    // Category references
    // -------------------
    // _ADMIN_CATEGORY_00_a: System
    // _ADMIN_CATEGORY_01_a: Layout
    // _ADMIN_CATEGORY_02_a: Users
    //  ADMIN_CATEGORY_03_a: Content
    //  ADMIN_CATEGORY_04_a: 3rdParty
    //  ADMIN_CATEGORY_05_a: Security
    //  ADMIN_CATEGORY_06_a: Hookd
    return array(array('module'   => 'Admin_Messages',
                       'category' => __('Content')),
                 array('module'   => 'SecurityCenter',
                       'category' => __('Security')),
                 array('module'   => 'Tour',
                       'category' => __('Content')),
                 array('module'   => 'Categories',
                       'category' => __('Content')),
                 array('module'   => 'Header_Footer',
                       'category' => __('Layout')),
                 array('module'   => 'legal',
                       'category' => __('Content')),
                 array('module'   => 'Mailer',
                       'category' => __('System')),
                 array('module'   => 'Errors',
                       'category' => __('System')),
                 array('module'   => 'pnRender',
                       'category' => __('Layout')),
                 array('module'   => 'pnForm',
                       'category' => __('System')),
                 array('module'   => 'Search',
                       'category' => __('Content')),
                 array('module'   => 'Workflow',
                       'category' => __('System')),
                 array('module'   => 'PageLock',
                       'category' => __('System')),
                 array('module'   => 'SysInfo',
                       'category' => __('Security')));
}

/** 
 * Custom configuration for modules installed by this install type
 */
function installer_basic_post_install()
{
    // no custom configuration for this install type
    return;
}
