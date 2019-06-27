<?php

function theme_boostwarwick_get_main_scss_content($theme) {
	global $CFG;
	$a = file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/default.scss');
        $a .= file_get_contents($CFG->dirroot . '/theme/boostwarwick/scss/main.scss');
	return $a;

}
