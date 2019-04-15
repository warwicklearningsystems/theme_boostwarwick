<?php

defined('MOODLE_INTERNAL') || die();

//require_once(__DIR__ . '/lib.php');

$THEME->name = 'boostwarwick';
$THEME->sheets = [];
$THEME->editor_sheets = [];
$THEME->editor_scss = ['editor'];
$THEME->usefallback = true;

$THEME->parents = ['boost'];
$THEME->enable_dock = false;

$THEME->yuicssmodules = array();
$THEME->rendererfactory = 'theme_overridden_renderer_factory';
$THEME->requiredblocks = '';
$THEME->addblockposition = BLOCK_ADDBLOCK_POSITION_FLATNAV;

$THEME->hidefromselector = false;