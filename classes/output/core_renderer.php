<?php

namespace theme_boostwarwick\output;

use html_writer;
use user_picture;
use moodle_url;
use moodle_page;
use renderer_base;
use coding_exception;
use context_user;
use context_course;

class user_picture_with_popover extends \user_picture {

  protected static $fields = array('id', 'picture', 'firstname', 'lastname', 'firstnamephonetic', 'lastnamephonetic',
    'middlename', 'alternatename', 'imagealt', 'email', 'idnumber');

  public function __construct(user_picture $userpicture) {

    global $DB;

    $user = $userpicture->user;

    if (empty($user->id)) {
      throw new coding_exception('User id is required when printing user avatar image.');
    }

    // only touch the DB if we are missing data and complain loudly...
    $needrec = false;
    foreach (self::$fields as $field) {
      if (!array_key_exists($field, $user)) {
        $needrec = true;
        debugging('Missing '.$field.' property in $user object, this is a performance problem that needs to be fixed by a developer. '
          .'Please use user_picture::fields() to get the full list of required fields.', DEBUG_DEVELOPER);
        break;
      }
    }

    if ($needrec) {
      $this->user = $DB->get_record('user', array('id'=>$user->id), self::fields('', array('idnumber', 'department')), MUST_EXIST);
    } else {
      $this->user = clone($user);
    }

    $this->link = $userpicture->link;
    $this->size = $userpicture->size;
    $this->alttext = $userpicture->alttext;
    $this->includefullname = $userpicture->includefullname;

    //parent::__construct($userpicture->user);
  }

  public function get_url(moodle_page $page, renderer_base $renderer = null) {
    global $CFG;

    if (is_null($renderer)) {
      $renderer = $page->get_renderer('core');
    }

    // Sort out the filename and size. Size is only required for the gravatar
    // implementation presently.
    if (empty($this->size)) {
      $filename = 'f2';
      $size = 35;
    } else if ($this->size === true or $this->size == 1) {
      $filename = 'f1';
      $size = 100;
    } else if ($this->size > 100) {
      $filename = 'f3';
      $size = (int)$this->size;
    } else if ($this->size >= 50) {
      $filename = 'f1';
      $size = (int)$this->size;
    } else {
      $filename = 'f2';
      $size = (int)$this->size;
    }

    $defaulturl = $renderer->image_url('u/'.$filename); // default image

    if ((!empty($CFG->forcelogin) and !isloggedin()) ||
      (!empty($CFG->forceloginforprofileimage) && (!isloggedin() || isguestuser()))) {
      // Protect images if login required and not logged in;
      // also if login is required for profile images and is not logged in or guest
      // do not use require_login() because it is expensive and not suitable here anyway.
      return $defaulturl;
    }

    // First try to detect deleted users - but do not read from database for performance reasons!
    if (!empty($this->user->deleted) or strpos($this->user->email, '@') === false) {
      // All deleted users should have email replaced by md5 hash,
      // all active users are expected to have valid email.
      return $defaulturl;
    }

    if(isset($this->user->idnumber) && $this->user->idnumber != '') {

      // Build a photos URL application ID and key
      $applicationId = 'itsmoodle';
      $applicationkey = '9J*#xAOsJWhms_CV0sUoG(p7Bd_oLCKx$WCr4(YU';
      $universityId = $this->user->idnumber;

      // Remove department code if any
      if (preg_match('/([a-zA-Z]{2}\d+)/', $universityId, $matches)) {
        $universityId = substr($universityId,2);
      }

      // Hash the application key and user id
      $md5 = md5($applicationkey . $universityId);

      return new moodle_url("https://photos.warwick.ac.uk/{$applicationId}/photo/{$md5}/{$universityId}");
    }

    // Did the user upload a picture?
    if ($this->user->picture > 0) {
      if (!empty($this->user->contextid)) {
        $contextid = $this->user->contextid;
      } else {
        $context = context_user::instance($this->user->id, IGNORE_MISSING);
        if (!$context) {
          // This must be an incorrectly deleted user, all other users have context.
          return $defaulturl;
        }
        $contextid = $context->id;
      }

      $path = '/';
      if (clean_param($page->theme->name, PARAM_THEME) == $page->theme->name) {
        // We append the theme name to the file path if we have it so that
        // in the circumstance that the profile picture is not available
        // when the user actually requests it they still get the profile
        // picture for the correct theme.
        $path .= $page->theme->name.'/';
      }
      // Set the image URL to the URL for the uploaded file and return.
      $url = moodle_url::make_pluginfile_url(
        $contextid, 'user', 'icon', null, $path, $filename, false, $this->includetoken);
      $url->param('rev', $this->user->picture);
      return $url;
    }

    if ($this->user->picture == 0 and !empty($CFG->enablegravatar)) {
      // Normalise the size variable to acceptable bounds
      if ($size < 1 || $size > 512) {
        $size = 35;
      }
      // Hash the users email address
      $md5 = md5(strtolower(trim($this->user->email)));
      // Build a gravatar URL with what we know.

      // Find the best default image URL we can (MDL-35669)
      if (empty($CFG->gravatardefaulturl)) {
        $absoluteimagepath = $page->theme->resolve_image_location('u/'.$filename, 'core');
        if (strpos($absoluteimagepath, $CFG->dirroot) === 0) {
          $gravatardefault = $CFG->wwwroot . substr($absoluteimagepath, strlen($CFG->dirroot));
        } else {
          $gravatardefault = $CFG->wwwroot . '/pix/u/' . $filename . '.png';
        }
      } else {
        $gravatardefault = $CFG->gravatardefaulturl;
      }

      // If the currently requested page is https then we'll return an
      // https gravatar page.
      if (is_https()) {
        return new moodle_url("https://secure.gravatar.com/avatar/{$md5}", array('s' => $size, 'd' => $gravatardefault));
      } else {
        return new moodle_url("http://www.gravatar.com/avatar/{$md5}", array('s' => $size, 'd' => $gravatardefault));
      }
    }

    return $defaulturl;
  }

}

class core_renderer extends \theme_boost\output\core_renderer  {

  /**
   * Wrapper for header elements.
   *
   * @return string HTML to display the main header.
   */
  public function full_header() {
    global $PAGE, $COURSE;

    $header = new \stdClass();
    $header->settingsmenu = $this->context_header_settings_menu();
    $header->contextheader = $this->context_header();
    $header->hasnavbar = empty($PAGE->layout_options['nonavbar']);
    $header->navbar = $this->navbar();
    $header->pageheadingbutton = $this->page_heading_button();
    $header->courseheader = $this->course_header();

    $header->courseid = $COURSE->id;

    // Is this course hidden?
    if ( (strpos($PAGE->url, '/course/view.php') == true) &&
         ($COURSE->visible == false)) {
      $header->hiddencourse = TRUE;
    } else {
      $header->hiddencourse = FALSE;
    }

    // Is this for a previous academic year?
    $header->previousacademicyear = FALSE;
    $matches=explode("\n", str_replace("\r","", get_config('theme_boostwarwick', 'currentyearpattern')));
    preg_match('#\d{2}/\d{2}#', $COURSE->idnumber, $regexmatch);
    if(isset($regexmatch[0])) {

      if((strpos($PAGE->url, '/course/view.php') == true) &&
        ($regexmatch[0]!=false) &&
        (in_array($regexmatch[0], $matches) == false)){
          $header->previousacademicyear = TRUE;
			}
    }

    // Is this course frozen?
    $header->coursefrozen = FALSE;
    $coursecontext = context_course::instance($COURSE->id);
    if( $coursecontext->locked ) {
      $header->coursefrozen = TRUE;
    }

    $header->displayalertmessage = FALSE;
    if ( get_config('theme_boostwarwick', 'alertmessageenabled') == TRUE ) {
      $header->displayalertmessage = TRUE;
      $header->alertmessage = get_config('theme_boostwarwick', 'alertmessage');
    }

    return $this->render_from_template('theme_boostwarwick/header', $header);
  }

  /**
   * Internal implementation of user image rendering.
   *
   * @param user_picture $userpicture
   * @return string
   */
  protected function render_user_picture(user_picture $userpicture) {
    global $CFG, $DB;

    // We need to recreate with correct type, to ensure we call the correct get_url()
    $userpicture = new user_picture_with_popover($userpicture);

    $user = $userpicture->user;
    $canviewfullnames = has_capability('moodle/site:viewfullnames', $this->page->context);

    if ($userpicture->alttext) {
      if (!empty($user->imagealt)) {
        $alt = $user->imagealt;
      } else {
        $alt = get_string('pictureof', '', fullname($user, $canviewfullnames));
      }
    } else {
      $alt = '';
    }

    if (empty($userpicture->size)) {
      $size = 35;
    } else if ($userpicture->size === true or $userpicture->size == 1) {
      $size = 100;
    } else {
      $size = $userpicture->size;
    }

    $class = $userpicture->class;

    if ($user->picture == 0) {
      $class .= ' defaultuserpic';
    }

    $src = $userpicture->get_url($this->page, $this);

    $attributes = array('src'=>$src, 'alt'=>$alt, 'title'=>$alt, 'class'=>$class, 'width'=>$size, 'height'=>$size);
    if (!$userpicture->visibletoscreenreaders) {
      $attributes['role'] = 'presentation';
    }

    // get the image html output fisrt
    $output = html_writer::empty_tag('img', $attributes);

    // Show fullname together with the picture when desired.
    if ($userpicture->includefullname) {
      $output .= fullname($userpicture->user, $canviewfullnames);
    }

    // then wrap it in link if needed
    if (!$userpicture->link) {
      return $output;
    }

    if (empty($userpicture->courseid)) {
      $courseid = $this->page->course->id;
    } else {
      $courseid = $userpicture->courseid;
    }

    if ($courseid == SITEID) {
      $url = new moodle_url('/user/profile.php', array('id' => $user->id));
    } else {
      $url = new moodle_url('/user/view.php', array('id' => $user->id, 'course' => $courseid));
    }

    $attributes = array('href'=>$url);
    if (!$userpicture->visibletoscreenreaders) {
      $attributes['tabindex'] = '-1';
      $attributes['aria-hidden'] = 'true';
    }

    if ($userpicture->popup) {
      $id = html_writer::random_id('userpicture');
      $attributes['id'] = $id;
      $this->add_action_handler(new popup_action('click', $url), $id);
    }

    $universityId = $user->idnumber;
    // Remove department code if any
    if (preg_match('/([a-zA-Z]{2}\d+)/', $universityId, $matches)) {
      $universityId = substr($universityId,2);
    }

    // Generate title
    $title = html_writer::tag('i', '', array('class' => 'fa fa-user fa-lg greypro-icon'));
    $title .= html_writer::tag('span', " " . $user->firstname . " " . $user->lastname,
      array('class' => 'warmoo-pro-pop-stress-main',
      'data-trigger' => 'hover',
      'data-html' => 'true'
      ));

    // Image HTML (for within popup)
    $userimg = html_writer::empty_tag('img', array('width'=>'90', 'height'=>'90', 'src'=>$src, 'alt'=>'Picture of'));

    $dataContent = html_writer::div($userimg, 'warmoo-pro-pop-pic userpicture', array('data-trigger' => 'hover', 'data-html' => 'true'));

    $dataContent .= html_writer::start_div();
    $dataContent .= html_writer::start_tag('ul', array('class' => 'warmoo-pro-po-lister list-unstyled'));

    // Name
    //$dataContent .= html_writer::start_tag('li');
    //$dataContent .= html_writer::tag('span', "Name: ", array('class' => 'warmoo-pro-pop-label'));
    //$dataContent .= html_writer::tag('span', $user->firstname . " " . $user->lastname, array('class' => 'warmoo-pro-pop-stress'));
    //$dataContent .= html_writer::end_tag('li');

    // University ID
    $dataContent .= html_writer::start_tag('li');
    $dataContent .= html_writer::tag('span', "University ID: ", array('class' => 'warmoo-pro-pop-label'));
    $dataContent .= html_writer::tag('span', $user->idnumber, array('class' => 'warmoo-pro-pop-stress'));
    $dataContent .= html_writer::end_tag('li');

    // Department
    if(isset($user->department)) {
      $dataContent .= html_writer::start_tag('li');
      $dataContent .= html_writer::tag('span', "Department: ", array('class' => 'warmoo-pro-pop-label'));
      $dataContent .= html_writer::tag('span', $user->department, array('class' => 'warmoo-pro-pop-stress'));
      $dataContent .= html_writer::end_tag('li');
    }

    // Tabula button
    $dataContent .= html_writer::start_tag('li');
    $dataContent .= html_writer::tag('a', "<i class='fa fa-external-link-square'></i> Tabula Profile",
      array('class' => 'btn btn-primary btn-moo-blue tab-prof-but', 'href' => 'https://tabula.warwick.ac.uk/profiles/view/' . $universityId,
        'target' => '_blank', 'role' => 'button'));
    $dataContent .= html_writer::end_tag('li');

    $dataContent .= html_writer::end_tag('ul');
    $dataContent .= html_writer::end_div();

    $attributes2 = array('tabindex'=>0, 'data-toggle'=>'popover', 'data-content'=>$dataContent, 'data-html'=>'true', 'data-trigger'=>'hover', 'title'=>"$title");

    // CHECK IF IMAGE SHOULD BE AN HYPERLINK
    if ($userpicture->link) {
      $attributes = array_merge($attributes, $attributes2); //if it's a link $attributes has been pre-filled
    } else {
      $attributes = $attributes2;
    }

    // Render the photo, name of user, and data content for popup
    $tmp_output = html_writer::tag('a', $output, $attributes);
    $div_attributes = array('id' => 'warmoo-pro-pop');
    $rendered_user_picture = html_writer::tag('div', $tmp_output, $div_attributes); // surround the <a> tag with a <div> tag
    return $rendered_user_picture;


    //return html_writer::tag('a', $output, $attributes);
  }



}



;