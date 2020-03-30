<?php

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings = new theme_boost_admin_settingspage_tabs('themesettingboostwarwick', get_string('configtitle', 'theme_boostwarwick'));
    $page = new admin_settingpage('theme_boost_general', get_string('generalsettings', 'theme_boost'));

    // Advanced settings.
    $page = new admin_settingpage('theme_boost_advanced', get_string('advancedsettings', 'theme_boost'));

    // Alert message enable/disable
    $setting = new admin_setting_configcheckbox('theme_boostwarwick/alertmessageenabled', get_string('alertmessageenabled','theme_boostwarwick'), get_string('alertmessageenabled_desc', 'theme_boostwarwick'), 0);
    $page->add($setting);

    // Alert message
    $setting = new admin_setting_configtextarea('theme_boostwarwick/alertmessage', get_string('alertmessage','theme_boostwarwick'), get_string('alertmessage_desc', 'theme_boostwarwick'), 'Moodle will be unavailable on XX due to an upgrade', PARAM_NOTAGS);
    $page->add($setting);

    // Info message settings
    $setting = new admin_setting_configcheckbox('theme_boostwarwick/infomessageenabled',
        get_string('infomessageenabled','theme_boostwarwick'),
        get_string('infomessageenabled_desc',
        'theme_boostwarwick'), 0);
    $page->add($setting);

    $setting = new admin_setting_configtextarea('theme_boostwarwick/infomessage',
        get_string('infomessage','theme_boostwarwick'),
        get_string('infomessage_desc', 'theme_boostwarwick'),
        'Information message.',
        PARAM_NOTAGS);
    $page->add($setting);

    $setting = new admin_setting_configtextarea('theme_boostwarwick/infomessageroles',
        get_string('infomessage_roles','theme_boostwarwick'),
        get_string('infomessage_roles_desc', 'theme_boostwarwick'),
        'Undergraduate',
        PARAM_NOTAGS);
    $page->add($setting);

    // Academic year pattern
    $setting = new admin_setting_configtextarea('theme_boostwarwick/currentyearpattern',
      get_string('currentyearpatternname','theme_boostwarwick'),
      get_string('currentyearpattern_desc', 'theme_boostwarwick'),
      '19/20', PARAM_NOTAGS);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $settings->add($page);
}
