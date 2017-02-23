<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Block "course overview (campus)" - Library
 *
 * @package    block_course_overview_campus
 * @copyright  2013 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Display overview for courses (copied from /blocks/course_overview/locallib.php)
 *
 * @param array $courses courses for which overview needs to be shown
 * @return array html overview
 */
function block_course_overview_campus_get_overviews($courses, $skip) {
    $htmlarray = array();
    if ($modules = get_plugin_list_with_function('mod', 'print_overview')) {
        // Remove modules which should be skipped
        $skipmodules = explode(',', $skip);
        if (is_array($skipmodules)) {
            foreach($skipmodules as $s) {
                unset($modules[$s]);
            }
        }

        // Split courses list into batches with no more than MAX_MODINFO_CACHE_SIZE courses in one batch.
        // Otherwise we exceed the cache limit in get_fast_modinfo() and rebuild it too often.
        if (defined('MAX_MODINFO_CACHE_SIZE') && MAX_MODINFO_CACHE_SIZE > 0 && count($courses) > MAX_MODINFO_CACHE_SIZE) {
            $batches = array_chunk($courses, MAX_MODINFO_CACHE_SIZE, true);
        } else {
            $batches = array($courses);
        }
        foreach ($batches as $courses) {
            foreach ($modules as $fname) {
                $fname($courses, $htmlarray);
            }
        }
    }
    return $htmlarray;
}


/**
 * Check if the configured term dates make sense
 *
 * @param object $coc_config The config object
 * @return bool
 */
function block_course_overview_campus_check_term_config($coc_config) {
    if ($coc_config->termmode == 1) {
        return true;
    }
    else if ($coc_config->termmode == 2 &&
        intval(date('z', strtotime('2003-'.$coc_config->term1startday))) < intval(date('z', strtotime('2003-'.$coc_config->term2startday)))) {
            return true;
    }
    else if ($coc_config->termmode == 3 &&
        intval(date('z', strtotime('2003-'.$coc_config->term1startday))) < intval(date('z', strtotime('2003-'.$coc_config->term2startday))) &&
        intval(date('z', strtotime('2003-'.$coc_config->term2startday))) < intval(date('z', strtotime('2003-'.$coc_config->term3startday)))) {
            return true;
    }
    else if ($coc_config->termmode == 4 &&
        intval(date('z', strtotime('2003-'.$coc_config->term1startday))) < intval(date('z', strtotime('2003-'.$coc_config->term2startday))) &&
        intval(date('z', strtotime('2003-'.$coc_config->term2startday))) < intval(date('z', strtotime('2003-'.$coc_config->term3startday))) &&
        intval(date('z', strtotime('2003-'.$coc_config->term3startday))) < intval(date('z', strtotime('2003-'.$coc_config->term4startday)))) {
            return true;
    }
    else {
        return false;
    }
}


/**
 * Take array of teacher objects and return a string of names, sorted by relevance and name
 *
 * @param array $teachers Array of teachers
 * @return string String with concatenated teacher names
 */
function block_course_overview_campus_get_teachername_string($teachers) {
    global $coc_config;

    // If given array is empty, return empty string
    if (empty($teachers))
        return '';

    // Sort all teachers by relevance and name, return empty string when sorting fails
    $success = usort($teachers, "block_course_overview_campus_compare_teachers");
    if (!$success) {
        return '';
    }

    // Get all teachers' names as an array according the teacher name style setting
    $teachernames = array_map(function($obj) {
        global $coc_config;

        // Display fullname
        if ($coc_config->secondrowshowteachernamestyle == 1) {
            return $obj->firstname.' '.$obj->lastname;
        }
        // Display lastname
        else if ($coc_config->secondrowshowteachernamestyle == 2) {
            return $obj->lastname;
        }
        // Display firstname
        else if ($coc_config->secondrowshowteachernamestyle == 3) {
            return $obj->firstname;
        }
        // Display fullnamedisplay
        else if ($coc_config->secondrowshowteachernamestyle == 4) {
            return fullname($obj);
        }
        // Fallback: Display lastname
        else {
            return $obj->lastname;
        }
    }, $teachers);

    // Implode teachers' names to a single string
    $teachernames = implode(", ", $teachernames);

    return $teachernames;
}


/**
 * Take term name and year(s) and return displayname for term filter based on plugin configuration
 *
 * @param string $termname The term's name
 * @param string $year The term's year
 * @param string $year2 The term's second year (optional)(
 * @return string String with the term's displayname
 */
function block_course_overview_campus_get_term_displayname($termname, $year, $year2='') {
    global $coc_config;

    // Build the first year - second year combination
    $displayname = $year;
    if ($year2 != '') {
        // Hyphen separation
        if ($coc_config->termyearseparation == 1) {
            $displayname = $year.'-'.$year2;
        }
        // Slash separation
        else if ($coc_config->termyearseparation == 2) {
            $displayname = $year.'/'.$year2;
        }
        // Underscore separation
        else if ($coc_config->termyearseparation == 3) {
            $displayname = $year.'_'.$year2;
        }
        // No second year
        else if ($coc_config->termyearseparation == 4) {
            $displayname = $year;
        }
        // This shouldn't happen
        else {
            $displayname = $year.'/'.$year2;
        }
    }

    // Add the term name
    // Prefix with space
    if ($coc_config->termyearpos == 1) {
        $displayname = $displayname.' '.$termname;
    }
    // Prefix without space
    else if ($coc_config->termyearpos == 2) {
        $displayname = $displayname.$termname;
    }
    // Suffix with space
    else if ($coc_config->termyearpos == 3) {
        $displayname = $termname.' '.$displayname;
    }
    // Suffix without space
    else if ($coc_config->termyearpos == 4) {
        $displayname = $termname.$displayname;
    }
    // This shouldn't happen
    else {
        $displayname = $termname. ' '.$termname;
    }

    return $displayname;
}


/**
 * Compare teacher by relevance helper function
 *
 * @param object $a Teacher A
 * @param object $b Teacher B
 * @return int
 */
function block_course_overview_campus_compare_teachers($a, $b) {
    // compare relevance of teachers' roles
    if ($a->sortorder < $b->sortorder) {
        return -1;
    }
    else if ($a->sortorder > $b->sortorder) {
        return 1;
    }
    else if ($a->sortorder == $b->sortorder) {
        // teachers' roles are equal, then compare lastnames
        return strcasecmp($a->lastname, $b->lastname);
    }
    else {
        // This should never happen
        return 0;
    }
}


/**
 * Compare category by sortorder helper function
 *
 * @param object $a Category A
 * @param object $b Category B
 * @return int
 */
function block_course_overview_campus_compare_categories($a, $b) {
    // compare sortorder of categories
    if ($a->sortorder < $b->sortorder) {
        return -1;
    }
    else if ($a->sortorder > $b->sortorder) {
        return 1;
    }
    else if ($a->sortorder == $b->sortorder) {
        // Category sortorders are equal - this shouldn't happen, but if it does then compare category names alphabetically
        return strcasecmp(format_string($a->name), format_string($b->name));
    }
    else {
        // This should never happen
        return 0;
    }
}

/**
 * Display notifications for all new mod (include in $resource_counter array) since the last visit of the user in a particular course.
 * (one part of this function has been copied from /moodle/course/resources.php)
 *
 * @param int course id ($course)
 * @return html snippet containing notifications with label (bootstrap)
 * @author : Sébastien Mehr
 */
function block_course_overview_campus_get_resources($course) {

  global $DB, $USER;

  // get all mods of the course
  $modinfo = get_fast_modinfo($course);

  // Initialize counters for all mod we want to display notification
  $resources_counter = array(
    'book' => 0,
    'folder' => 0,
    'page' => 0,
    'resource' => 0,
    'url' => 0
  );

  $html = '';

  // get the last user's access to the course
  $sql = 'SELECT timeaccess FROM {user_lastaccess} WHERE userid = ? AND courseid = ?';
  $lastaccess = $DB->get_record_sql($sql, array($USER->id,$course));

  // if the user never connected to the course, set to 0
  if ($lastaccess!==false) {
    $user_timeaccess = $lastaccess->timeaccess;
  }
  else {
    $user_timeaccess = 0;
  }

  foreach ($modinfo->cms as $cm) {

    if (!$cm->uservisible) {
      // Exclude hiding or not avalaible mod
      continue;
    }
    if (!$cm->has_view()) {
      // Exclude label and similar
      continue;
    }

    // get the mod information corresponding to our $resources_counter array
    if (array_key_exists($cm->modname, $resources_counter)) {
      $dateadded = $cm->added;
      // if the mod is newer than the user's last access of the course
      if ($dateadded > $user_timeaccess) {
        // add +1 in the mod's counter
        $resources_counter[$cm->modname] += 1;
      }
    }
  }

  if (array_sum($resources_counter) > 0) {
    $html .= '<div class="coc-notification hidden-phone">';
    // build a html snippet with the new $resources_counter array containing the number of each new mods
    foreach ($resources_counter as $resource => $counter) {
      if ($counter == 0 ) {
        $html .= '';
      }
      elseif ($counter == 1) {
        $html .= '<span class="badge badge-important">';
        $html .= $counter;
        $html .= ' ';
        $html .= get_string('modulename', 'mod_'.$resource);
        $html .= '</span> ';
      }
      else {
        $html .= '<span class="badge badge-important">';
        $html .= $counter;
        $html .= ' ';
        $html .= get_string('modulenameplural', 'mod_'.$resource);
        $html .= '</span> ';
      }
    }
    $html .= '</div>';
  }
  return $html;
}


/**
* Display the parentcode of a childcourse using metalink enrolment
*
* @param int course id ($course)
* @return html snippet containing parentcode's shortname
* @author Sébastien Mehr
*/
function block_course_overview_campus_get_metalink($course) {

  global $DB;

  $html = '';
  // get course meta links of the current course
  $sql = 'SELECT e.customint1 parentid, p.shortname parentcode, e.courseid child
          FROM {enrol} e
          INNER JOIN {course} p ON p.id=e.customint1
          INNER JOIN {course} c ON c.id=e.courseid
          WHERE enrol = ?
          AND e.courseid = ?';
  $parentcodes = $DB->get_records_sql($sql, array('meta',$course));

  if($parentcodes) {
    $html .= '<div class="coc-metalink hidden-phone">'.get_string('metalink', 'block_course_overview_campus');
    $html .= ' ';
    foreach ($parentcodes as $id => $record) {
      $html .=  '<span class="label label-warning">'.$record->parentcode.'</span>';
      $html .=  ' ';
    }
    $html .= '</div>';
  }

  return $html;
}
