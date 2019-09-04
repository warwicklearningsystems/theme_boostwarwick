<?php

defined('MOODLE_INTERNAL') || die();

//require_once(__DIR__ . '/lib.php');

$THEME->name = 'boostwarwick';
$THEME->parents = ['boost'];
$THEME->sheets = [];
$THEME->editor_sheets = [];
//$THEME->editor_scss = ['editor'];
//$THEME->usefallback = true;
$THEME->enable_dock = false;

$THEME->scss = function($theme) {
    return theme_boostwarwick_get_main_scss_content($theme);
};

$THEME->layouts = [

  // The site home page.
  'frontpage' => array(
    'file' => 'frontpage.php',
    'regions' => array(),
    'options' => array('nonavbar' => true),
  ),
  'maintenance' => array(
    'file' => 'maintenance.php',
    'regions' => array(),
    'options' => array('nonavbar' => true),
  ),

];


$THEME->yuicssmodules = array();
$THEME->rendererfactory = 'theme_overridden_renderer_factory';
$THEME->requiredblocks = '';
$THEME->addblockposition = BLOCK_ADDBLOCK_POSITION_FLATNAV;

$THEME->hidefromselector = false;
