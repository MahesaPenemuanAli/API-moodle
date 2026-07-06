<?php
// This file is part of Moodle - https://moodle.org/

namespace local_api_moodle;

defined('MOODLE_INTERNAL') || die();

class helper {
    public const DEFAULT_LIMIT = 20;
    public const MAX_LIMIT = 100;

    public static function normalise_limit($limit, $default = self::DEFAULT_LIMIT): int {
        $limit = (int)$limit;
        if ($limit <= 0) {
            $limit = (int)$default;
        }
        return min($limit, self::MAX_LIMIT);
    }

    public static function normalise_offset($offset): int {
        return max(0, (int)$offset);
    }

    public static function require_authenticated_user(\context $context): void {
        if (!isloggedin() || isguestuser()) {
            throw new \moodle_exception('nopermissions', 'error', '', 'login required');
        }

        require_capability('local/api_moodle:use', $context);
    }

    public static function get_course_and_context(int $courseid): array {
        $course = get_course($courseid);
        $context = \context_course::instance($course->id);

        return [$course, $context];
    }

    public static function require_course_access(\stdClass $course, \context_course $context): void {
        require_login($course, false);

        if (!can_access_course($course)) {
            throw new \required_capability_exception($context, 'moodle/course:view', 'nopermissions', '');
        }
    }

    public static function user_payload(\stdClass $user): array {
        return [
            'id' => (int)$user->id,
            'username' => isset($user->username) ? (string)$user->username : '',
            'fullname' => fullname($user),
            'email' => isset($user->email) ? (string)$user->email : '',
            'lang' => isset($user->lang) ? (string)$user->lang : '',
            'timezone' => isset($user->timezone) ? (string)$user->timezone : '',
            'profileimageurl' => self::user_picture_url($user),
        ];
    }

    public static function user_picture_url(\stdClass $user): string {
        global $PAGE;

        try {
            $picture = new \user_picture($user);
            $picture->size = 100;
            return $picture->get_url($PAGE)->out(false);
        } catch (\Throwable $e) {
            return '';
        }
    }

    public static function plain_text($text, $format, \context $context): string {
        if ($text === null || $text === '') {
            return '';
        }

        $html = format_text($text, $format, [
            'context' => $context,
            'noclean' => false,
            'overflowdiv' => false,
            'filter' => true,
        ]);

        return trim(html_to_text($html, 0, false));
    }

    public static function course_payload(\stdClass $course, int $userid, bool $includesummary = true): array {
        $context = \context_course::instance($course->id);
        $categoryname = '';

        if (!empty($course->category)) {
            try {
                $category = \core_course_category::get((int)$course->category, IGNORE_MISSING, true);
                if ($category) {
                    $categoryname = format_string($category->name, true, ['context' => $category->get_context()]);
                }
            } catch (\Throwable $e) {
                $categoryname = '';
            }
        }

        $summary = '';
        if ($includesummary) {
            $summary = self::plain_text($course->summary ?? '', $course->summaryformat ?? FORMAT_HTML, $context);
        }

        return [
            'id' => (int)$course->id,
            'shortname' => format_string($course->shortname, true, ['context' => $context]),
            'fullname' => format_string($course->fullname, true, ['context' => $context]),
            'displayname' => get_course_display_name_for_list($course),
            'summary' => $summary,
            'categoryid' => (int)$course->category,
            'categoryname' => $categoryname,
            'startdate' => (int)($course->startdate ?? 0),
            'enddate' => (int)($course->enddate ?? 0),
            'visible' => (int)($course->visible ?? 1),
            'format' => isset($course->format) ? (string)$course->format : '',
            'imageurl' => self::course_image_url($course),
            'progress' => self::course_progress($course, $userid),
        ];
    }

    public static function course_image_url(\stdClass $course): string {
        $context = \context_course::instance($course->id, IGNORE_MISSING);
        if (!$context) {
            return '';
        }

        $fs = get_file_storage();
        $files = $fs->get_area_files(
            $context->id,
            'course',
            'overviewfiles',
            0,
            'sortorder, itemid, filepath, filename',
            false
        );

        foreach ($files as $file) {
            if (strpos((string)$file->get_mimetype(), 'image/') !== 0) {
                continue;
            }

            return \moodle_url::make_webservice_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                $file->get_itemid(),
                $file->get_filepath(),
                $file->get_filename()
            )->out(false);
        }

        return '';
    }

    public static function course_progress(\stdClass $course, int $userid): ?float {
        if (!class_exists('\\core_completion\\progress')) {
            return null;
        }

        try {
            $progress = \core_completion\progress::get_course_progress_percentage($course, $userid);
            if ($progress === null) {
                return null;
            }
            return round((float)$progress, 2);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function enrolled_courses(int $userid): array {
        global $USER;

        $fields = [
            'id',
            'category',
            'sortorder',
            'shortname',
            'fullname',
            'idnumber',
            'summary',
            'summaryformat',
            'format',
            'startdate',
            'enddate',
            'visible',
            'timemodified',
        ];

        $currentuser = (int)$USER->id;
        if ($currentuser !== $userid) {
            return [];
        }

        return enrol_get_my_courses(implode(',', $fields), 'visible DESC, sortorder ASC, fullname ASC');
    }

    public static function enrolled_course_ids(int $userid): array {
        $courses = self::enrolled_courses($userid);
        return array_map('intval', array_keys($courses));
    }

    public static function get_visible_cms_by_module(\stdClass $course, string $modname, int $userid, bool $includehidden = false): array {
        $context = \context_course::instance($course->id);
        $canviewhidden = has_capability('moodle/course:viewhiddenactivities', $context);
        $modinfo = get_fast_modinfo($course, $userid);
        $cms = [];

        if (empty($modinfo->instances[$modname])) {
            return $cms;
        }

        foreach ($modinfo->instances[$modname] as $cm) {
            if (!$cm->uservisible && !($includehidden && $canviewhidden)) {
                continue;
            }
            $cms[$cm->instance] = $cm;
        }

        return $cms;
    }

    public static function module_completion_payload(\stdClass $course, \cm_info $cm, int $userid): array {
        $payload = [
            'enabled' => false,
            'state' => 0,
            'timecompleted' => 0,
        ];

        try {
            $completion = new \completion_info($course);
            if (!$completion->is_enabled($cm)) {
                return $payload;
            }

            $data = $completion->get_data($cm, false, $userid);
            $payload['enabled'] = true;
            $payload['state'] = (int)$data->completionstate;
            $payload['timecompleted'] = (int)($data->timemodified ?? 0);
        } catch (\Throwable $e) {
            return $payload;
        }

        return $payload;
    }

    public static function unread_notification_count(int $userid): int {
        if (!class_exists('\\message_popup\\api')) {
            return 0;
        }

        return (int)\message_popup\api::count_unread_popup_notifications($userid);
    }

    public static function user_can_teach(\context_course $context): bool {
        return has_any_capability([
            'local/api_moodle:viewteaching',
            'moodle/course:update',
            'mod/assign:grade',
            'mod/quiz:grade',
        ], $context);
    }
}
