<?php
// This file is part of Moodle - https://moodle.org/

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/course/lib.php');

class local_api_moodle_external extends external_api {
    private static function system_context(): context_system {
        $context = context_system::instance();
        self::validate_context($context);
        \local_api_moodle\helper::require_authenticated_user($context);

        return $context;
    }

    private static function user_structure(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'User id.'),
            'username' => new external_value(PARAM_USERNAME, 'Username.'),
            'fullname' => new external_value(PARAM_TEXT, 'Full name.'),
            'email' => new external_value(PARAM_EMAIL, 'Email address.'),
            'lang' => new external_value(PARAM_ALPHANUMEXT, 'Preferred language.'),
            'timezone' => new external_value(PARAM_TEXT, 'User timezone.'),
            'profileimageurl' => new external_value(PARAM_RAW, 'Profile image URL.'),
        ]);
    }

    private static function course_structure(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Course id.'),
            'shortname' => new external_value(PARAM_TEXT, 'Course shortname.'),
            'fullname' => new external_value(PARAM_TEXT, 'Course fullname.'),
            'displayname' => new external_value(PARAM_TEXT, 'Course display name.'),
            'summary' => new external_value(PARAM_RAW, 'Plain text course summary.'),
            'categoryid' => new external_value(PARAM_INT, 'Course category id.'),
            'categoryname' => new external_value(PARAM_TEXT, 'Course category name.'),
            'startdate' => new external_value(PARAM_INT, 'Course start timestamp.'),
            'enddate' => new external_value(PARAM_INT, 'Course end timestamp.'),
            'visible' => new external_value(PARAM_INT, 'Course visibility flag.'),
            'format' => new external_value(PARAM_RAW, 'Course format plugin name.'),
            'imageurl' => new external_value(PARAM_RAW, 'Course overview image URL.'),
            'progress' => new external_value(PARAM_FLOAT, 'Completion percentage.', VALUE_REQUIRED, null, NULL_ALLOWED),
        ]);
    }

    private static function category_structure(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Category id.'),
            'name' => new external_value(PARAM_TEXT, 'Category name.'),
            'idnumber' => new external_value(PARAM_RAW, 'Category idnumber.'),
            'parent' => new external_value(PARAM_INT, 'Parent category id.'),
            'path' => new external_value(PARAM_RAW, 'Category path.'),
            'depth' => new external_value(PARAM_INT, 'Category depth.'),
            'coursecount' => new external_value(PARAM_INT, 'Visible course count stored by Moodle.'),
        ]);
    }

    private static function completion_structure(): external_single_structure {
        return new external_single_structure([
            'enabled' => new external_value(PARAM_BOOL, 'Whether completion is enabled for this activity.'),
            'state' => new external_value(PARAM_INT, 'Completion state.'),
            'timecompleted' => new external_value(PARAM_INT, 'Completion timestamp or zero.'),
        ]);
    }

    private static function module_structure(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Course module id.'),
            'instanceid' => new external_value(PARAM_INT, 'Activity instance id.'),
            'section' => new external_value(PARAM_INT, 'Section number.'),
            'modname' => new external_value(PARAM_PLUGIN, 'Activity module name.'),
            'name' => new external_value(PARAM_TEXT, 'Activity name.'),
            'description' => new external_value(PARAM_RAW, 'Plain text label/content summary.'),
            'url' => new external_value(PARAM_RAW, 'Activity URL.'),
            'iconurl' => new external_value(PARAM_RAW, 'Activity icon URL.'),
            'visible' => new external_value(PARAM_INT, 'Raw Moodle visibility flag.'),
            'uservisible' => new external_value(PARAM_BOOL, 'Whether the activity is visible to the current user.'),
            'availabilityinfo' => new external_value(PARAM_RAW, 'Plain text availability information.'),
            'completion' => self::completion_structure(),
        ]);
    }

    private static function section_structure(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Section id.'),
            'section' => new external_value(PARAM_INT, 'Section number.'),
            'name' => new external_value(PARAM_TEXT, 'Section name.'),
            'summary' => new external_value(PARAM_RAW, 'Plain text section summary.'),
            'visible' => new external_value(PARAM_INT, 'Section visibility flag.'),
            'uservisible' => new external_value(PARAM_BOOL, 'Whether section is visible to the current user.'),
            'modulecount' => new external_value(PARAM_INT, 'Visible activity count.'),
            'modules' => new external_multiple_structure(self::module_structure(), 'Section modules.', VALUE_DEFAULT, []),
        ]);
    }

    private static function assignment_structure(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Assignment id.'),
            'cmid' => new external_value(PARAM_INT, 'Course module id.'),
            'courseid' => new external_value(PARAM_INT, 'Course id.'),
            'coursename' => new external_value(PARAM_TEXT, 'Course display name.'),            
            'name' => new external_value(PARAM_TEXT, 'Assignment name.'),
            'intro' => new external_value(PARAM_RAW, 'Plain text intro when requested.'),
            'duedate' => new external_value(PARAM_INT, 'Due timestamp.'),
            'allowsubmissionsfromdate' => new external_value(PARAM_INT, 'Submission open timestamp.'),
            'cutoffdate' => new external_value(PARAM_INT, 'Cutoff timestamp.'),
            'grade' => new external_value(PARAM_FLOAT, 'Maximum grade.'),
            'url' => new external_value(PARAM_RAW, 'Assignment URL.'),
            'submissionstatus' => new external_value(PARAM_ALPHA, 'Current user submission status.'),
            'submitted' => new external_value(PARAM_BOOL, 'Whether the current user has submitted.'),
            'submissionmodified' => new external_value(PARAM_INT, 'Submission modified timestamp.'),
            'usergrade' => new external_value(PARAM_FLOAT, 'Current user grade.', VALUE_REQUIRED, null, NULL_ALLOWED),
            'grademodified' => new external_value(PARAM_INT, 'Grade modified timestamp.'),
        ]);
    }

    private static function quiz_structure(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Quiz id.'),
            'cmid' => new external_value(PARAM_INT, 'Course module id.'),
            'courseid' => new external_value(PARAM_INT, 'Course id.'),
            'coursename' => new external_value(PARAM_TEXT, 'Course display name.'),
            'name' => new external_value(PARAM_TEXT, 'Quiz name.'),
            'intro' => new external_value(PARAM_RAW, 'Plain text intro when requested.'),
            'timeopen' => new external_value(PARAM_INT, 'Open timestamp.'),
            'timeclose' => new external_value(PARAM_INT, 'Close timestamp.'),
            'timelimit' => new external_value(PARAM_INT, 'Time limit in seconds.'),
            'attemptsallowed' => new external_value(PARAM_INT, 'Attempts allowed, zero means unlimited.'),
            'grade' => new external_value(PARAM_FLOAT, 'Maximum grade.'),
            'url' => new external_value(PARAM_RAW, 'Quiz URL.'),
            'lastattemptstate' => new external_value(PARAM_ALPHAEXT, 'Latest attempt state.'),
            'lastattemptmodified' => new external_value(PARAM_INT, 'Latest attempt modified timestamp.'),
            'attemptcount' => new external_value(PARAM_INT, 'Number of attempts by current user.'),
            'usergrade' => new external_value(PARAM_FLOAT, 'Current user quiz grade.', VALUE_REQUIRED, null, NULL_ALLOWED),
        ]);
    }

    private static function grade_item_structure(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Grade item id.'),
            'itemname' => new external_value(PARAM_TEXT, 'Grade item name.'),
            'itemtype' => new external_value(PARAM_ALPHA, 'Grade item type.'),
            'itemmodule' => new external_value(PARAM_RAW, 'Module name.'),
            'iteminstance' => new external_value(PARAM_INT, 'Module instance id.'),
            'grademax' => new external_value(PARAM_FLOAT, 'Maximum grade.'),
            'finalgrade' => new external_value(PARAM_FLOAT, 'Final grade.', VALUE_REQUIRED, null, NULL_ALLOWED),
            'percentage' => new external_value(PARAM_FLOAT, 'Final grade percentage.', VALUE_REQUIRED, null, NULL_ALLOWED),
            'hidden' => new external_value(PARAM_BOOL, 'Whether grade item is hidden.'),
            'timemodified' => new external_value(PARAM_INT, 'Grade modified timestamp.'),
        ]);
    }

    private static function calendar_event_structure(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Event id.'),
            'name' => new external_value(PARAM_TEXT, 'Event name.'),
            'description' => new external_value(PARAM_RAW, 'Plain text event description.'),
            'courseid' => new external_value(PARAM_INT, 'Course id.'),
            'coursename' => new external_value(PARAM_TEXT, 'Course name.'),
            'eventtype' => new external_value(PARAM_ALPHAEXT, 'Event type.'),
            'modulename' => new external_value(PARAM_RAW, 'Related module name.'),
            'instance' => new external_value(PARAM_INT, 'Related module instance id.'),
            'timestart' => new external_value(PARAM_INT, 'Start timestamp.'),
            'timeduration' => new external_value(PARAM_INT, 'Duration in seconds.'),
            'url' => new external_value(PARAM_RAW, 'Event URL.'),
        ]);
    }

    private static function notification_structure(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Core notification id.'),
            'notificationid' => new external_value(PARAM_INT, 'Core notification id.'),
            'subject' => new external_value(PARAM_TEXT, 'Notification subject.'),
            'smallmessage' => new external_value(PARAM_RAW, 'Short message.'),
            'contexturl' => new external_value(PARAM_RAW, 'Context URL.'),
            'contexturlname' => new external_value(PARAM_TEXT, 'Context URL label.'),
            'timecreated' => new external_value(PARAM_INT, 'Created timestamp.'),
            'timeread' => new external_value(PARAM_INT, 'Read timestamp.'),
            'read' => new external_value(PARAM_BOOL, 'Whether notification has been read.'),
        ]);
    }

    private static function teaching_course_structure(): external_single_structure {
        return new external_single_structure([
            'course' => self::course_structure(),
            'enrolledusers' => new external_value(PARAM_INT, 'Total enrolled users.'),
            'students' => new external_value(PARAM_INT, 'Users with student-like submit capability.'),
            'assignments' => new external_value(PARAM_INT, 'Assignment count.'),
            'quizzes' => new external_value(PARAM_INT, 'Quiz count.'),
        ]);
    }

    public static function get_site_structure_parameters(): external_function_parameters {
        return new external_function_parameters([
            'parentid' => new external_value(PARAM_INT, 'Parent category id, -1 for all categories.', VALUE_DEFAULT, -1),
            'limit' => new external_value(PARAM_INT, 'Result limit.', VALUE_DEFAULT, 100),
            'offset' => new external_value(PARAM_INT, 'Result offset.', VALUE_DEFAULT, 0),
        ]);
    }

    public static function get_site_structure($parentid = -1, $limit = 100, $offset = 0): array {
        global $DB;

        self::system_context();
        $params = self::validate_parameters(self::get_site_structure_parameters(), [
            'parentid' => $parentid,
            'limit' => $limit,
            'offset' => $offset,
        ]);

        $limit = \local_api_moodle\helper::normalise_limit($params['limit'], 100);
        $offset = \local_api_moodle\helper::normalise_offset($params['offset']);
        $conditions = ['visible = 1'];
        $sqlparams = [];

        if ((int)$params['parentid'] >= 0) {
            $conditions[] = 'parent = :parentid';
            $sqlparams['parentid'] = (int)$params['parentid'];
        }

        $where = implode(' AND ', $conditions);
        $total = $DB->count_records_select('course_categories', $where, $sqlparams);
        $records = $DB->get_records_select(
            'course_categories',
            $where,
            $sqlparams,
            'sortorder ASC, name ASC',
            'id, name, idnumber, parent, path, depth, coursecount',
            $offset,
            $limit
        );

        $categories = [];
        foreach ($records as $record) {
            $context = context_coursecat::instance($record->id, IGNORE_MISSING);
            $categories[] = [
                'id' => (int)$record->id,
                'name' => $context ? format_string($record->name, true, ['context' => $context]) : format_string($record->name),
                'idnumber' => (string)$record->idnumber,
                'parent' => (int)$record->parent,
                'path' => (string)$record->path,
                'depth' => (int)$record->depth,
                'coursecount' => (int)$record->coursecount,
            ];
        }

        return [
            'total' => (int)$total,
            'categories' => $categories,
            'warnings' => [],
        ];
    }

    public static function get_site_structure_returns(): external_single_structure {
        return new external_single_structure([
            'total' => new external_value(PARAM_INT, 'Total matching categories.'),
            'categories' => new external_multiple_structure(self::category_structure()),
            'warnings' => new external_warnings(),
        ]);
    }

    public static function search_courses_parameters(): external_function_parameters {
        return new external_function_parameters([
            'query' => new external_value(PARAM_TEXT, 'Search keyword.', VALUE_DEFAULT, ''),
            'categoryid' => new external_value(PARAM_INT, 'Optional category id.', VALUE_DEFAULT, 0),
            'limit' => new external_value(PARAM_INT, 'Result limit.', VALUE_DEFAULT, 20),
            'offset' => new external_value(PARAM_INT, 'Result offset.', VALUE_DEFAULT, 0),
        ]);
    }

    public static function search_courses($query = '', $categoryid = 0, $limit = 20, $offset = 0): array {
        global $DB, $SITE, $USER;

        self::system_context();
        $params = self::validate_parameters(self::search_courses_parameters(), [
            'query' => $query,
            'categoryid' => $categoryid,
            'limit' => $limit,
            'offset' => $offset,
        ]);

        $limit = \local_api_moodle\helper::normalise_limit($params['limit']);
        $offset = \local_api_moodle\helper::normalise_offset($params['offset']);
        $conditions = ['c.id <> :siteid', 'c.visible = 1'];
        $sqlparams = ['siteid' => (int)$SITE->id];
        $query = trim($params['query']);

        if ($query !== '') {
            $conditions[] = '(' . $DB->sql_like('c.fullname', ':fullname', false) .
                ' OR ' . $DB->sql_like('c.shortname', ':shortname', false) . ')';
            $escaped = '%' . $DB->sql_like_escape($query) . '%';
            $sqlparams['fullname'] = $escaped;
            $sqlparams['shortname'] = $escaped;
        }

        if ((int)$params['categoryid'] > 0) {
            $conditions[] = 'c.category = :categoryid';
            $sqlparams['categoryid'] = (int)$params['categoryid'];
        }

        $where = implode(' AND ', $conditions);
        $fields = 'c.id, c.category, c.sortorder, c.shortname, c.fullname, c.idnumber, c.summary, c.summaryformat, ' .
            'c.format, c.startdate, c.enddate, c.visible, c.timemodified';
        $total = $DB->count_records_sql("SELECT COUNT(1) FROM {course} c WHERE {$where}", $sqlparams);
        $records = $DB->get_records_sql(
            "SELECT {$fields} FROM {course} c WHERE {$where} ORDER BY c.fullname ASC",
            $sqlparams,
            $offset,
            $limit
        );

        $courses = [];
        foreach ($records as $course) {
            $courses[] = \local_api_moodle\helper::course_payload($course, (int)$USER->id, false);
        }

        return [
            'total' => (int)$total,
            'courses' => $courses,
            'warnings' => [],
        ];
    }

    public static function search_courses_returns(): external_single_structure {
        return new external_single_structure([
            'total' => new external_value(PARAM_INT, 'Total matching courses.'),
            'courses' => new external_multiple_structure(self::course_structure()),
            'warnings' => new external_warnings(),
        ]);
    }

    public static function get_mobile_dashboard_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courselimit' => new external_value(PARAM_INT, 'Course card limit.', VALUE_DEFAULT, 8),
            'eventlimit' => new external_value(PARAM_INT, 'Upcoming event limit.', VALUE_DEFAULT, 10),
        ]);
    }

    public static function get_mobile_dashboard($courselimit = 8, $eventlimit = 10): array {
        global $CFG, $SITE, $USER;

        self::system_context();
        $params = self::validate_parameters(self::get_mobile_dashboard_parameters(), [
            'courselimit' => $courselimit,
            'eventlimit' => $eventlimit,
        ]);

        $courselimit = \local_api_moodle\helper::normalise_limit($params['courselimit'], 8);
        $eventlimit = \local_api_moodle\helper::normalise_limit($params['eventlimit'], 10);
        $allcourses = \local_api_moodle\helper::enrolled_courses((int)$USER->id);
        $courses = [];

        foreach (array_slice($allcourses, 0, $courselimit, true) as $course) {
            $courses[] = \local_api_moodle\helper::course_payload($course, (int)$USER->id, false);
        }

        $events = self::collect_calendar_events((int)$USER->id, time(), time() + DAYSECS * 60, 0, $eventlimit);

        return [
            'site' => [
                'name' => format_string($SITE->fullname, true, ['context' => context_course::instance($SITE->id)]),
                'shortname' => format_string($SITE->shortname, true, ['context' => context_course::instance($SITE->id)]),
                'url' => $CFG->wwwroot,
                'lang' => current_language(),
            ],
            'user' => \local_api_moodle\helper::user_payload($USER),
            'counters' => [
                'courses' => count($allcourses),
                'unreadnotifications' => \local_api_moodle\helper::unread_notification_count((int)$USER->id),
                'upcomingevents' => count($events),
            ],
            'courses' => $courses,
            'events' => $events,
            'warnings' => [],
        ];
    }

    public static function get_mobile_dashboard_returns(): external_single_structure {
        return new external_single_structure([
            'site' => new external_single_structure([
                'name' => new external_value(PARAM_TEXT, 'Site full name.'),
                'shortname' => new external_value(PARAM_TEXT, 'Site short name.'),
                'url' => new external_value(PARAM_RAW, 'Site URL.'),
                'lang' => new external_value(PARAM_ALPHANUMEXT, 'Current language.'),
            ]),
            'user' => self::user_structure(),
            'counters' => new external_single_structure([
                'courses' => new external_value(PARAM_INT, 'Enrolled course count.'),
                'unreadnotifications' => new external_value(PARAM_INT, 'Unread notification count.'),
                'upcomingevents' => new external_value(PARAM_INT, 'Returned upcoming event count.'),
            ]),
            'courses' => new external_multiple_structure(self::course_structure()),
            'events' => new external_multiple_structure(self::calendar_event_structure()),
            'warnings' => new external_warnings(),
        ]);
    }

    public static function get_my_courses_parameters(): external_function_parameters {
        return new external_function_parameters([
            'limit' => new external_value(PARAM_INT, 'Result limit.', VALUE_DEFAULT, 20),
            'offset' => new external_value(PARAM_INT, 'Result offset.', VALUE_DEFAULT, 0),
            'includesummary' => new external_value(PARAM_BOOL, 'Include plain text summary.', VALUE_DEFAULT, false),
        ]);
    }

    public static function get_my_courses($limit = 20, $offset = 0, $includesummary = false): array {
        global $USER;

        self::system_context();
        $params = self::validate_parameters(self::get_my_courses_parameters(), [
            'limit' => $limit,
            'offset' => $offset,
            'includesummary' => $includesummary,
        ]);

        $limit = \local_api_moodle\helper::normalise_limit($params['limit']);
        $offset = \local_api_moodle\helper::normalise_offset($params['offset']);
        $allcourses = \local_api_moodle\helper::enrolled_courses((int)$USER->id);
        $courses = [];

        foreach (array_slice($allcourses, $offset, $limit, true) as $course) {
            $courses[] = \local_api_moodle\helper::course_payload($course, (int)$USER->id, (bool)$params['includesummary']);
        }

        return [
            'total' => count($allcourses),
            'courses' => $courses,
            'warnings' => [],
        ];
    }

    public static function get_my_courses_returns(): external_single_structure {
        return new external_single_structure([
            'total' => new external_value(PARAM_INT, 'Total enrolled courses.'),
            'courses' => new external_multiple_structure(self::course_structure()),
            'warnings' => new external_warnings(),
        ]);
    }

    public static function get_course_detail_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id.'),
            'includesections' => new external_value(PARAM_BOOL, 'Include section summary.', VALUE_DEFAULT, true),
        ]);
    }

    public static function get_course_detail($courseid, $includesections = true): array {
        global $USER;

        self::system_context();
        $params = self::validate_parameters(self::get_course_detail_parameters(), [
            'courseid' => $courseid,
            'includesections' => $includesections,
        ]);

        [$course, $context] = \local_api_moodle\helper::get_course_and_context((int)$params['courseid']);
        self::validate_context($context);
        \local_api_moodle\helper::require_course_access($course, $context);

        $sections = [];
        if ((bool)$params['includesections']) {
            $sections = self::build_sections($course, (int)$USER->id, false, false);
        }

        return [
            'course' => \local_api_moodle\helper::course_payload($course, (int)$USER->id, true),
            'sections' => $sections,
            'warnings' => [],
        ];
    }

    public static function get_course_detail_returns(): external_single_structure {
        return new external_single_structure([
            'course' => self::course_structure(),
            'sections' => new external_multiple_structure(self::section_structure()),
            'warnings' => new external_warnings(),
        ]);
    }

    public static function get_course_contents_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id.'),
            'includehidden' => new external_value(PARAM_BOOL, 'Include hidden sections/modules when the user may view them.', VALUE_DEFAULT, false),
        ]);
    }

    public static function get_course_contents($courseid, $includehidden = false): array {
        global $USER;

        self::system_context();
        $params = self::validate_parameters(self::get_course_contents_parameters(), [
            'courseid' => $courseid,
            'includehidden' => $includehidden,
        ]);

        [$course, $context] = \local_api_moodle\helper::get_course_and_context((int)$params['courseid']);
        self::validate_context($context);
        \local_api_moodle\helper::require_course_access($course, $context);

        return [
            'courseid' => (int)$course->id,
            'sections' => self::build_sections($course, (int)$USER->id, (bool)$params['includehidden'], true),
            'warnings' => [],
        ];
    }

    public static function get_course_contents_returns(): external_single_structure {
        return new external_single_structure([
            'courseid' => new external_value(PARAM_INT, 'Course id.'),
            'sections' => new external_multiple_structure(self::section_structure()),
            'warnings' => new external_warnings(),
        ]);
    }

    public static function get_assignments_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id, zero for all enrolled courses.', VALUE_DEFAULT, 0),
            'onlyactive' => new external_value(PARAM_BOOL, 'Exclude assignments past cutoff/due date.', VALUE_DEFAULT, false),
            'includeintro' => new external_value(PARAM_BOOL, 'Include plain text intro.', VALUE_DEFAULT, false),
            'limit' => new external_value(PARAM_INT, 'Result limit.', VALUE_DEFAULT, 20),
            'offset' => new external_value(PARAM_INT, 'Result offset.', VALUE_DEFAULT, 0),
        ]);
    }

    public static function get_assignments($courseid = 0, $onlyactive = false, $includeintro = false, $limit = 20, $offset = 0): array {
        global $DB, $USER;

        self::system_context();
        $params = self::validate_parameters(self::get_assignments_parameters(), [
            'courseid' => $courseid,
            'onlyactive' => $onlyactive,
            'includeintro' => $includeintro,
            'limit' => $limit,
            'offset' => $offset,
        ]);

        $courses = self::target_courses((int)$params['courseid']);
        $items = [];
        $now = time();

        foreach ($courses as $course) {
            $context = context_course::instance($course->id);
            $cms = \local_api_moodle\helper::get_visible_cms_by_module($course, 'assign', (int)$USER->id);
            if (!$cms) {
                continue;
            }

            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($cms), SQL_PARAMS_NAMED, 'assignid');
            $assignments = $DB->get_records_select(
                'assign',
                "id {$insql}",
                $inparams,
                'duedate ASC, name ASC',
                'id, course, name, intro, introformat, duedate, allowsubmissionsfromdate, cutoffdate, grade'
            );

            foreach ($assignments as $assignment) {
                if ((bool)$params['onlyactive']) {
                    $deadline = (int)$assignment->cutoffdate ?: (int)$assignment->duedate;
                    if ($deadline > 0 && $deadline < $now) {
                        continue;
                    }
                }

                $cm = $cms[$assignment->id];
                $submission = self::latest_assignment_submission((int)$assignment->id, (int)$USER->id);
                $grade = $DB->get_record('assign_grades', [
                    'assignment' => $assignment->id,
                    'userid' => $USER->id,
                ], 'grade, timemodified', IGNORE_MULTIPLE);

                $items[] = [
                    'id' => (int)$assignment->id,
                    'cmid' => (int)$cm->id,
                    'courseid' => (int)$course->id,
                    'coursename' => get_course_display_name_for_list($course),
                    'name' => format_string($assignment->name, true, ['context' => $context]),
                    'intro' => $params['includeintro'] ? \local_api_moodle\helper::plain_text($assignment->intro, $assignment->introformat, $context) : '',
                    'duedate' => (int)$assignment->duedate,
                    'allowsubmissionsfromdate' => (int)$assignment->allowsubmissionsfromdate,
                    'cutoffdate' => (int)$assignment->cutoffdate,
                    'grade' => (float)$assignment->grade,
                    'url' => $cm->url ? $cm->url->out(false) : '',
                    'submissionstatus' => $submission ? (string)$submission->status : 'new',
                    'submitted' => $submission && $submission->status === 'submitted',
                    'submissionmodified' => $submission ? (int)$submission->timemodified : 0,
                    'usergrade' => ($grade && $grade->grade !== null && (float)$grade->grade >= 0) ? (float)$grade->grade : null,
                    'grademodified' => $grade ? (int)$grade->timemodified : 0,
                ];
            }
        }

        usort($items, static function(array $a, array $b): int {
            $adue = $a['duedate'] ?: PHP_INT_MAX;
            $bdue = $b['duedate'] ?: PHP_INT_MAX;
            if ($adue === $bdue) {
                return strcmp($a['name'], $b['name']);
            }
            return $adue <=> $bdue;
        });

        $total = count($items);
        $limit = \local_api_moodle\helper::normalise_limit($params['limit']);
        $offset = \local_api_moodle\helper::normalise_offset($params['offset']);

        return [
            'total' => $total,
            'assignments' => array_slice($items, $offset, $limit),
            'warnings' => [],
        ];
    }

    public static function get_assignments_returns(): external_single_structure {
        return new external_single_structure([
            'total' => new external_value(PARAM_INT, 'Total matching assignments.'),
            'assignments' => new external_multiple_structure(self::assignment_structure()),
            'warnings' => new external_warnings(),
        ]);
    }

    public static function get_quizzes_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id, zero for all enrolled courses.', VALUE_DEFAULT, 0),
            'onlyactive' => new external_value(PARAM_BOOL, 'Exclude quizzes past close date.', VALUE_DEFAULT, false),
            'includeintro' => new external_value(PARAM_BOOL, 'Include plain text intro.', VALUE_DEFAULT, false),
            'limit' => new external_value(PARAM_INT, 'Result limit.', VALUE_DEFAULT, 20),
            'offset' => new external_value(PARAM_INT, 'Result offset.', VALUE_DEFAULT, 0),
        ]);
    }

    public static function get_quizzes($courseid = 0, $onlyactive = false, $includeintro = false, $limit = 20, $offset = 0): array {
        global $DB, $USER;

        self::system_context();
        $params = self::validate_parameters(self::get_quizzes_parameters(), [
            'courseid' => $courseid,
            'onlyactive' => $onlyactive,
            'includeintro' => $includeintro,
            'limit' => $limit,
            'offset' => $offset,
        ]);

        $courses = self::target_courses((int)$params['courseid']);
        $items = [];
        $now = time();

        foreach ($courses as $course) {
            $context = context_course::instance($course->id);
            $cms = \local_api_moodle\helper::get_visible_cms_by_module($course, 'quiz', (int)$USER->id);
            if (!$cms) {
                continue;
            }

            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($cms), SQL_PARAMS_NAMED, 'quizid');
            $quizzes = $DB->get_records_select(
                'quiz',
                "id {$insql}",
                $inparams,
                'timeclose ASC, name ASC',
                'id, course, name, intro, introformat, timeopen, timeclose, timelimit, attempts, grade'
            );

            foreach ($quizzes as $quiz) {
                if ((bool)$params['onlyactive'] && (int)$quiz->timeclose > 0 && (int)$quiz->timeclose < $now) {
                    continue;
                }

                $cm = $cms[$quiz->id];
                $lastattempt = self::latest_quiz_attempt((int)$quiz->id, (int)$USER->id);
                $attemptcount = $DB->count_records('quiz_attempts', [
                    'quiz' => $quiz->id,
                    'userid' => $USER->id,
                    'preview' => 0,
                ]);
                $grade = $DB->get_record('quiz_grades', [
                    'quiz' => $quiz->id,
                    'userid' => $USER->id,
                ], 'grade, timemodified', IGNORE_MISSING);

                $items[] = [
                    'id' => (int)$quiz->id,
                    'cmid' => (int)$cm->id,
                    'courseid' => (int)$course->id,
                    'coursename' => get_course_display_name_for_list($course),
                    'name' => format_string($quiz->name, true, ['context' => $context]),
                    'intro' => $params['includeintro'] ? \local_api_moodle\helper::plain_text($quiz->intro, $quiz->introformat, $context) : '',
                    'timeopen' => (int)$quiz->timeopen,
                    'timeclose' => (int)$quiz->timeclose,
                    'timelimit' => (int)$quiz->timelimit,
                    'attemptsallowed' => (int)$quiz->attempts,
                    'grade' => (float)$quiz->grade,
                    'url' => $cm->url ? $cm->url->out(false) : '',
                    'lastattemptstate' => $lastattempt ? (string)$lastattempt->state : '',
                    'lastattemptmodified' => $lastattempt ? (int)$lastattempt->timemodified : 0,
                    'attemptcount' => (int)$attemptcount,
                    'usergrade' => ($grade && $grade->grade !== null) ? (float)$grade->grade : null,
                ];
            }
        }

        usort($items, static function(array $a, array $b): int {
            $aclose = $a['timeclose'] ?: PHP_INT_MAX;
            $bclose = $b['timeclose'] ?: PHP_INT_MAX;
            if ($aclose === $bclose) {
                return strcmp($a['name'], $b['name']);
            }
            return $aclose <=> $bclose;
        });

        $total = count($items);
        $limit = \local_api_moodle\helper::normalise_limit($params['limit']);
        $offset = \local_api_moodle\helper::normalise_offset($params['offset']);

        return [
            'total' => $total,
            'quizzes' => array_slice($items, $offset, $limit),
            'warnings' => [],
        ];
    }

    public static function get_quizzes_returns(): external_single_structure {
        return new external_single_structure([
            'total' => new external_value(PARAM_INT, 'Total matching quizzes.'),
            'quizzes' => new external_multiple_structure(self::quiz_structure()),
            'warnings' => new external_warnings(),
        ]);
    }

    public static function get_grades_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id.'),
        ]);
    }

    public static function get_grades($courseid): array {
        global $DB, $USER;

        self::system_context();
        $params = self::validate_parameters(self::get_grades_parameters(), [
            'courseid' => $courseid,
        ]);

        [$course, $context] = \local_api_moodle\helper::get_course_and_context((int)$params['courseid']);
        self::validate_context($context);
        \local_api_moodle\helper::require_course_access($course, $context);

        if (!has_any_capability(['moodle/grade:view', 'moodle/grade:viewall'], $context)) {
            throw new required_capability_exception($context, 'moodle/grade:view', 'nopermissions', '');
        }

        $canviewhidden = has_capability('moodle/grade:viewhidden', $context);
        $sql = "SELECT gi.id, gi.itemname, gi.itemtype, gi.itemmodule, gi.iteminstance, gi.grademax, gi.hidden,
                       gg.finalgrade, gg.timemodified
                  FROM {grade_items} gi
             LEFT JOIN {grade_grades} gg ON gg.itemid = gi.id AND gg.userid = :userid
                 WHERE gi.courseid = :courseid
                   AND gi.itemtype <> :category
              ORDER BY gi.sortorder ASC";
        $records = $DB->get_records_sql($sql, [
            'userid' => (int)$USER->id,
            'courseid' => (int)$course->id,
            'category' => 'category',
        ]);

        $items = [];
        foreach ($records as $record) {
            $hidden = !empty($record->hidden);
            if ($hidden && !$canviewhidden) {
                continue;
            }

            $itemname = (string)$record->itemname;
            if ($record->itemtype === 'course' && $itemname === '') {
                $itemname = get_course_display_name_for_list($course);
            }

            $finalgrade = $record->finalgrade !== null ? (float)$record->finalgrade : null;
            $grademax = (float)$record->grademax;
            $percentage = null;
            if ($finalgrade !== null && $grademax > 0) {
                $percentage = round(($finalgrade / $grademax) * 100, 2);
            }

            $items[] = [
                'id' => (int)$record->id,
                'itemname' => format_string($itemname, true, ['context' => $context]),
                'itemtype' => (string)$record->itemtype,
                'itemmodule' => (string)$record->itemmodule,
                'iteminstance' => (int)$record->iteminstance,
                'grademax' => $grademax,
                'finalgrade' => $finalgrade,
                'percentage' => $percentage,
                'hidden' => $hidden,
                'timemodified' => (int)$record->timemodified,
            ];
        }

        return [
            'course' => \local_api_moodle\helper::course_payload($course, (int)$USER->id, false),
            'items' => $items,
            'warnings' => [],
        ];
    }

    public static function get_grades_returns(): external_single_structure {
        return new external_single_structure([
            'course' => self::course_structure(),
            'items' => new external_multiple_structure(self::grade_item_structure()),
            'warnings' => new external_warnings(),
        ]);
    }

    public static function get_calendar_events_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id, zero for all enrolled courses.', VALUE_DEFAULT, 0),
            'timestart' => new external_value(PARAM_INT, 'Start timestamp, zero for now.', VALUE_DEFAULT, 0),
            'timeend' => new external_value(PARAM_INT, 'End timestamp, zero for 60 days from start.', VALUE_DEFAULT, 0),
            'limit' => new external_value(PARAM_INT, 'Result limit.', VALUE_DEFAULT, 20),
        ]);
    }

    public static function get_calendar_events($courseid = 0, $timestart = 0, $timeend = 0, $limit = 20): array {
        global $USER;

        self::system_context();
        $params = self::validate_parameters(self::get_calendar_events_parameters(), [
            'courseid' => $courseid,
            'timestart' => $timestart,
            'timeend' => $timeend,
            'limit' => $limit,
        ]);

        if ((int)$params['courseid'] > 0) {
            [$course, $context] = \local_api_moodle\helper::get_course_and_context((int)$params['courseid']);
            self::validate_context($context);
            \local_api_moodle\helper::require_course_access($course, $context);
        }

        $start = (int)$params['timestart'] ?: time();
        $end = (int)$params['timeend'] ?: $start + DAYSECS * 60;
        $limit = \local_api_moodle\helper::normalise_limit($params['limit']);

        return [
            'events' => self::collect_calendar_events((int)$USER->id, $start, $end, (int)$params['courseid'], $limit),
            'warnings' => [],
        ];
    }

    public static function get_calendar_events_returns(): external_single_structure {
        return new external_single_structure([
            'events' => new external_multiple_structure(self::calendar_event_structure()),
            'warnings' => new external_warnings(),
        ]);
    }

    public static function get_notifications_parameters(): external_function_parameters {
        return new external_function_parameters([
            'unreadonly' => new external_value(PARAM_BOOL, 'Return unread notifications only.', VALUE_DEFAULT, false),
            'limit' => new external_value(PARAM_INT, 'Result limit.', VALUE_DEFAULT, 20),
            'offset' => new external_value(PARAM_INT, 'Result offset.', VALUE_DEFAULT, 0),
        ]);
    }

    public static function get_notifications($unreadonly = false, $limit = 20, $offset = 0): array {
        global $DB, $USER;

        self::system_context();
        $params = self::validate_parameters(self::get_notifications_parameters(), [
            'unreadonly' => $unreadonly,
            'limit' => $limit,
            'offset' => $offset,
        ]);

        if (!$DB->get_manager()->table_exists('message_popup_notifications')) {
            return [
                'unreadcount' => 0,
                'notifications' => [],
                'warnings' => [[
                    'item' => 'message_popup_notifications',
                    'itemid' => 0,
                    'warningcode' => 'tablenotavailable',
                    'message' => 'Popup notification table is not available.',
                ]],
            ];
        }

        $where = ['n.useridto = :userid'];
        $sqlparams = ['userid' => (int)$USER->id];
        if ((bool)$params['unreadonly']) {
            $where[] = 'n.timeread IS NULL';
        }

        $limit = \local_api_moodle\helper::normalise_limit($params['limit']);
        $offset = \local_api_moodle\helper::normalise_offset($params['offset']);
        $select = implode(' AND ', $where);
        $sql = "SELECT n.id, n.id AS notificationid, n.subject, n.smallmessage, n.contexturl, n.contexturlname,
                       n.timecreated, n.timeread
                  FROM {notifications} n
                 WHERE n.id IN (SELECT notificationid FROM {message_popup_notifications})
                   AND {$select}
              ORDER BY n.timecreated DESC, n.id DESC";
        $records = $DB->get_records_sql(
            $sql,
            $sqlparams,
            $offset,
            $limit
        );

        $notifications = [];
        foreach ($records as $record) {
            $timeread = (int)($record->timeread ?? 0);
            $notifications[] = [
                'id' => (int)$record->id,
                'notificationid' => (int)$record->notificationid,
                'subject' => format_string((string)$record->subject),
                'smallmessage' => trim(html_to_text((string)$record->smallmessage, 0, false)),
                'contexturl' => (string)$record->contexturl,
                'contexturlname' => format_string((string)$record->contexturlname),
                'timecreated' => (int)$record->timecreated,
                'timeread' => $timeread,
                'read' => $timeread > 0,
            ];
        }

        return [
            'unreadcount' => \local_api_moodle\helper::unread_notification_count((int)$USER->id),
            'notifications' => $notifications,
            'warnings' => [],
        ];
    }

    public static function get_notifications_returns(): external_single_structure {
        return new external_single_structure([
            'unreadcount' => new external_value(PARAM_INT, 'Unread notification count.'),
            'notifications' => new external_multiple_structure(self::notification_structure()),
            'warnings' => new external_warnings(),
        ]);
    }

    public static function mark_notification_read_parameters(): external_function_parameters {
        return new external_function_parameters([
            'notificationid' => new external_value(PARAM_INT, 'Core notification id returned by get_notifications.'),
        ]);
    }

    public static function mark_notification_read($notificationid): array {
        global $DB, $USER;

        self::system_context();
        $params = self::validate_parameters(self::mark_notification_read_parameters(), [
            'notificationid' => $notificationid,
        ]);

        $notification = $DB->get_record('notifications', [
            'id' => (int)$params['notificationid'],
            'useridto' => (int)$USER->id,
        ], '*', MUST_EXIST);

        if (empty($notification->timeread)) {
            $notification->timeread = time();
            \core_message\api::mark_notification_as_read($notification, $notification->timeread);
        }

        return [
            'status' => true,
            'timeread' => (int)$notification->timeread,
            'warnings' => [],
        ];
    }

    public static function mark_notification_read_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'Operation status.'),
            'timeread' => new external_value(PARAM_INT, 'Read timestamp.'),
            'warnings' => new external_warnings(),
        ]);
    }

    public static function get_teaching_overview_parameters(): external_function_parameters {
        return new external_function_parameters([
            'limit' => new external_value(PARAM_INT, 'Result limit.', VALUE_DEFAULT, 20),
            'offset' => new external_value(PARAM_INT, 'Result offset.', VALUE_DEFAULT, 0),
        ]);
    }

    public static function get_teaching_overview($limit = 20, $offset = 0): array {
        global $DB, $USER;

        self::system_context();
        $params = self::validate_parameters(self::get_teaching_overview_parameters(), [
            'limit' => $limit,
            'offset' => $offset,
        ]);

        $courses = \local_api_moodle\helper::enrolled_courses((int)$USER->id);
        $items = [];

        foreach ($courses as $course) {
            $context = context_course::instance($course->id);
            if (!\local_api_moodle\helper::user_can_teach($context)) {
                continue;
            }

            $items[] = [
                'course' => \local_api_moodle\helper::course_payload($course, (int)$USER->id, false),
                'enrolledusers' => count_enrolled_users($context, '', 0, true),
                'students' => count_enrolled_users($context, 'mod/assign:submit', 0, true),
                'assignments' => $DB->count_records('assign', ['course' => $course->id]),
                'quizzes' => $DB->count_records('quiz', ['course' => $course->id]),
            ];
        }

        $total = count($items);
        $limit = \local_api_moodle\helper::normalise_limit($params['limit']);
        $offset = \local_api_moodle\helper::normalise_offset($params['offset']);

        return [
            'total' => $total,
            'courses' => array_slice($items, $offset, $limit),
            'warnings' => [],
        ];
    }

    public static function get_teaching_overview_returns(): external_single_structure {
        return new external_single_structure([
            'total' => new external_value(PARAM_INT, 'Total teaching courses.'),
            'courses' => new external_multiple_structure(self::teaching_course_structure()),
            'warnings' => new external_warnings(),
        ]);
    }

    private static function target_courses(int $courseid): array {
        global $USER;

        if ($courseid > 0) {
            [$course, $context] = \local_api_moodle\helper::get_course_and_context($courseid);
            self::validate_context($context);
            \local_api_moodle\helper::require_course_access($course, $context);
            return [$course->id => $course];
        }

        return \local_api_moodle\helper::enrolled_courses((int)$USER->id);
    }

    private static function build_sections(\stdClass $course, int $userid, bool $includehidden, bool $includemodules): array {
        $context = context_course::instance($course->id);
        $canviewhidden = has_capability('moodle/course:viewhiddenactivities', $context);
        $modinfo = get_fast_modinfo($course, $userid);
        $sections = [];

        foreach ($modinfo->get_section_info_all() as $sectionnum => $section) {
            if (!$section->uservisible && !($includehidden && $canviewhidden)) {
                continue;
            }

            $modules = [];
            foreach ($modinfo->sections[$sectionnum] ?? [] as $cmid) {
                if (empty($modinfo->cms[$cmid])) {
                    continue;
                }
                $cm = $modinfo->cms[$cmid];
                if (!$cm->uservisible && !($includehidden && $canviewhidden)) {
                    continue;
                }

                if ($includemodules) {
                    $availabilityinfo = '';
                    if (!empty($cm->availableinfo)) {
                        $availabilityinfo = \local_api_moodle\helper::plain_text($cm->availableinfo, FORMAT_HTML, $context);
                    }

                    $description = '';
                    if ($cm->modname === 'label' && !empty($cm->content)) {
                        $description = \local_api_moodle\helper::plain_text($cm->content, FORMAT_HTML, $context);
                    }

                    $iconurl = '';
                    try {
                        $iconurl = $cm->get_icon_url()->out(false);
                    } catch (Throwable $e) {
                        $iconurl = '';
                    }

                    $modules[] = [
                        'id' => (int)$cm->id,
                        'instanceid' => (int)$cm->instance,
                        'section' => (int)$sectionnum,
                        'modname' => (string)$cm->modname,
                        'name' => format_string($cm->name, true, ['context' => $context]),
                        'description' => $description,
                        'url' => $cm->url ? $cm->url->out(false) : '',
                        'iconurl' => $iconurl,
                        'visible' => (int)$cm->visible,
                        'uservisible' => (bool)$cm->uservisible,
                        'availabilityinfo' => $availabilityinfo,
                        'completion' => \local_api_moodle\helper::module_completion_payload($course, $cm, $userid),
                    ];
                }
            }

            $summary = '';
            if (!empty($section->summary)) {
                $summary = \local_api_moodle\helper::plain_text($section->summary, $section->summaryformat, $context);
            }

            $sections[] = [
                'id' => (int)$section->id,
                'section' => (int)$sectionnum,
                'name' => format_string(get_section_name($course, $section), true, ['context' => $context]),
                'summary' => $summary,
                'visible' => (int)$section->visible,
                'uservisible' => (bool)$section->uservisible,
                'modulecount' => count($modules),
                'modules' => $modules,
            ];
        }

        return $sections;
    }

    private static function latest_assignment_submission(int $assignmentid, int $userid): ?stdClass {
        global $DB;

        $sql = "SELECT id, status, timemodified
                  FROM {assign_submission}
                 WHERE assignment = :assignment
                   AND userid = :userid
              ORDER BY latest DESC, timemodified DESC";
        $records = $DB->get_records_sql($sql, [
            'assignment' => $assignmentid,
            'userid' => $userid,
        ], 0, 1);

        return $records ? reset($records) : null;
    }

    private static function latest_quiz_attempt(int $quizid, int $userid): ?stdClass {
        global $DB;

        $sql = "SELECT id, state, timemodified
                  FROM {quiz_attempts}
                 WHERE quiz = :quiz
                   AND userid = :userid
                   AND preview = 0
              ORDER BY attempt DESC, timemodified DESC";
        $records = $DB->get_records_sql($sql, [
            'quiz' => $quizid,
            'userid' => $userid,
        ], 0, 1);

        return $records ? reset($records) : null;
    }

    private static function collect_calendar_events(int $userid, int $timestart, int $timeend, int $courseid, int $limit): array {
        global $DB, $SITE;

        $courseids = [];
        if ($courseid > 0) {
            $courseids[] = $courseid;
        } else {
            $courseids = \local_api_moodle\helper::enrolled_course_ids($userid);
        }

        $conditions = [
            'e.visible = 1',
            'e.timestart >= :timestart',
            'e.timestart <= :timeend',
            'e.groupid = 0',
        ];
        $params = [
            'timestart' => $timestart,
            'timeend' => $timeend,
            'userid' => $userid,
            'siteid' => (int)$SITE->id,
        ];
        $visibilityconditions = ['e.userid = :userid', 'e.courseid = :siteid'];

        if ($courseids) {
            [$insql, $inparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'eventcourse');
            $visibilityconditions[] = "e.courseid {$insql}";
            $params += $inparams;
        }

        $conditions[] = '(' . implode(' OR ', $visibilityconditions) . ')';
        $where = implode(' AND ', $conditions);
        $sql = "SELECT e.id, e.name, e.description, e.format, e.courseid, e.eventtype, e.modulename,
                       e.instance, e.timestart, e.timeduration, c.fullname AS coursename
                  FROM {event} e
             LEFT JOIN {course} c ON c.id = e.courseid
                 WHERE {$where}
              ORDER BY e.timestart ASC, e.name ASC";

        $records = $DB->get_records_sql($sql, $params, 0, $limit);
        $events = [];

        foreach ($records as $event) {
            $context = context_system::instance();
            if (!empty($event->courseid) && (int)$event->courseid !== (int)$SITE->id) {
                $context = context_course::instance($event->courseid, IGNORE_MISSING) ?: context_system::instance();
            }

            $url = '';
            if (!empty($event->modulename) && !empty($event->instance) && !empty($event->courseid)) {
                $cm = get_coursemodule_from_instance(
                    $event->modulename,
                    $event->instance,
                    $event->courseid,
                    false,
                    IGNORE_MISSING
                );
                if ($cm) {
                    $url = (new moodle_url('/mod/' . $event->modulename . '/view.php', ['id' => $cm->id]))->out(false);
                }
            }

            $events[] = [
                'id' => (int)$event->id,
                'name' => format_string($event->name, true, ['context' => $context]),
                'description' => \local_api_moodle\helper::plain_text($event->description, $event->format ?? FORMAT_HTML, $context),
                'courseid' => (int)$event->courseid,
                'coursename' => isset($event->coursename) ? format_string((string)$event->coursename, true, ['context' => $context]) : '',
                'eventtype' => (string)$event->eventtype,
                'modulename' => (string)$event->modulename,
                'instance' => (int)$event->instance,
                'timestart' => (int)$event->timestart,
                'timeduration' => (int)$event->timeduration,
                'url' => $url,
            ];
        }

        return $events;
    }
}
