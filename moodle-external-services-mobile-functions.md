# Dokumentasi Moodle External Services untuk Mobile App

Dokumen ini menjelaskan kegunaan function bawaan Moodle yang disarankan untuk service `USU Mobile API`. Pendekatan ini tidak membuat plugin API custom, tetapi menggunakan External Services bawaan Moodle melalui REST.

## Endpoint

Base endpoint REST:

```text
http://localhost/webservice/rest/server.php
```

Format request:

```text
wstoken=TOKEN
wsfunction=NAMA_FUNCTION
moodlewsrestformat=json
```

Login token:

```text
http://localhost/login/token.php
```

Body:

```text
username=USERNAME
password=PASSWORD
service=usu_mobile_api
```

## Function Minimum

Function berikut cukup untuk MVP mobile mahasiswa: login, profil, dashboard, course, materi, tugas, quiz, nilai, kalender, dan notifikasi.

| Function | Kegunaan di Mobile | Dipakai Saat |
|---|---|---|
| `core_webservice_get_site_info` | Mengambil info situs Moodle dan user yang sedang login, termasuk `userid`, fullname, username, site name, dan daftar function yang tersedia untuk token tersebut. | Setelah login/token valid untuk inisialisasi session mobile. |
| `core_course_get_categories` | Mengambil struktur kategori course, misalnya fakultas, program studi, semester, atau tahun ajaran seperti pola struktur Kelas USU. | Halaman eksplor course atau filter kategori. |
| `core_course_search_courses` | Mencari course berdasarkan keyword. | Fitur search mata kuliah/open course. |
| `core_course_get_courses_by_field` | Mengambil detail course berdasarkan field tertentu seperti `id`, `shortname`, `idnumber`, atau `category`. | Saat butuh detail satu course atau daftar course dalam kategori tertentu. |
| `core_enrol_get_users_courses` | Mengambil daftar course yang diikuti user tertentu. | Halaman "My Courses" mahasiswa/dosen. |
| `core_course_get_enrolled_courses_by_timeline_classification` | Mengambil course user berdasarkan klasifikasi timeline: `inprogress`, `past`, atau `future`. | Dashboard mobile untuk course aktif, lampau, dan mendatang. |
| `core_course_get_contents` | Mengambil isi course: section/topik, activity/module, resource, URL file, completion info, dan visibility. | Halaman detail mata kuliah/materi. |
| `core_course_get_course_module` | Mengambil detail satu course module berdasarkan `cmid`. | Saat user membuka satu aktivitas/materi dari daftar course content. |
| `core_completion_get_activities_completion_status` | Mengambil status completion semua aktivitas dalam course untuk user. | Progress belajar per aktivitas. |
| `core_completion_get_course_completion_status` | Mengambil status completion course secara keseluruhan. | Progress/ringkasan penyelesaian mata kuliah. |
| `core_calendar_get_calendar_upcoming_view` | Mengambil tampilan kalender upcoming milik user. | Widget deadline/event di dashboard. |
| `core_calendar_get_action_events_by_timesort` | Mengambil action events yang terurut waktu, biasanya deadline tugas/quiz yang perlu tindakan. | Daftar tugas/quiz terdekat. |
| `message_popup_get_popup_notifications` | Mengambil daftar notifikasi popup user. | Halaman notifikasi. |
| `message_popup_get_unread_popup_notification_count` | Mengambil jumlah notifikasi popup yang belum dibaca. | Badge notifikasi di header/tab mobile. |
| `core_message_mark_notification_read` | Menandai satu notifikasi sebagai sudah dibaca. | Saat user membuka/membaca notifikasi. |
| `gradereport_overview_get_course_grades` | Mengambil ringkasan nilai akhir user pada course-course yang diikuti. | Halaman ringkasan nilai semua mata kuliah. |
| `gradereport_user_get_grade_items` | Mengambil detail item nilai dalam satu course. | Halaman detail nilai per mata kuliah. |
| `mod_assign_get_assignments` | Mengambil daftar assignment dari course tertentu atau beberapa course. | Halaman daftar tugas. |
| `mod_assign_get_submission_status` | Mengambil status submission user untuk satu assignment. | Detail tugas: sudah submit, draft, belum submit, status grading. |
| `mod_assign_save_submission` | Menyimpan submission assignment user. | Saat mahasiswa mengirim teks/file submission. |
| `mod_assign_submit_for_grading` | Mengubah submission menjadi final untuk dinilai. | Tombol "Submit for grading". |
| `mod_assign_view_assign` | Mencatat assignment sebagai viewed dan memperbarui completion bila applicable. | Saat user membuka detail assignment. |
| `mod_quiz_get_quizzes_by_courses` | Mengambil daftar quiz dari course tertentu atau beberapa course. | Halaman daftar quiz/assessment. |
| `mod_quiz_get_user_attempts` | Mengambil attempt quiz milik user. | Detail quiz: attempt terakhir, jumlah attempt, status attempt. |
| `mod_quiz_get_user_best_grade` | Mengambil nilai terbaik user pada quiz tertentu. | Menampilkan ringkasan nilai quiz. |
| `mod_quiz_get_quiz_access_information` | Mengambil informasi akses quiz, seperti apakah user boleh attempt/review. | Sebelum membuka atau memulai quiz. |

## Function Tambahan Yang Disarankan

Tambahkan function ini jika mobile app akan lebih dekat dengan pengalaman Moodle mobile resmi.

| Function | Kegunaan di Mobile | Dipakai Saat |
|---|---|---|
| `core_course_get_enrolled_courses_with_action_events_by_timeline_classification` | Mengambil course berdasarkan timeline sekaligus action events/deadline terkait. Lebih kaya untuk dashboard daripada timeline course biasa. | Dashboard course aktif dengan deadline/action event. |
| `core_files_get_unused_draft_itemid` | Membuat draft area untuk upload file. | Sebelum upload file assignment. |
| `core_files_upload` | Upload file ke draft file area Moodle. | Upload file tugas, avatar, atau private files. |
| `core_files_get_files` | Mengambil daftar file dari area Moodle yang diizinkan. | Menampilkan file materi/private files bila diperlukan. |
| `core_files_delete_draft_files` | Menghapus file dari draft area. | Saat user membatalkan upload file sebelum submit. |

## Alur Penggunaan Mobile

### 1. Login

Panggil:

```text
POST /login/token.php
```

Body:

```text
username=nim_or_username
password=password
service=usu_mobile_api
```

Response sukses:

```json
{
  "token": "TOKEN"
}
```

Simpan token di secure storage mobile.

### 2. Inisialisasi Session

Panggil:

```text
core_webservice_get_site_info
```

Kegunaan:

- Validasi token.
- Ambil `userid`.
- Ambil nama user.
- Cek function yang tersedia untuk token.

Contoh:

```text
http://localhost/webservice/rest/server.php?wstoken=TOKEN&wsfunction=core_webservice_get_site_info&moodlewsrestformat=json
```

### 3. Dashboard Mahasiswa

Function utama:

```text
core_course_get_enrolled_courses_by_timeline_classification
core_calendar_get_action_events_by_timesort
message_popup_get_unread_popup_notification_count
gradereport_overview_get_course_grades
```

Kegunaan dashboard:

- Course aktif.
- Deadline terdekat.
- Badge notifikasi.
- Ringkasan nilai.

### 4. My Courses

Function:

```text
core_enrol_get_users_courses
```

Parameter penting:

```text
userid=USER_ID
```

Gunakan `userid` dari `core_webservice_get_site_info`.

### 5. Struktur Course dan Materi

Function:

```text
core_course_get_contents
```

Parameter:

```text
courseid=COURSE_ID
```

Data penting untuk mobile:

- Section/topik.
- Module/activity.
- Nama activity.
- `modname`, misalnya `assign`, `quiz`, `resource`, `page`, `url`, `forum`.
- `cmid`.
- URL file/resource.
- Completion status.

Catatan:

- Jangan tampilkan semua HTML mentah tanpa sanitasi di mobile.
- Untuk file Moodle, gunakan URL yang dikembalikan Moodle dan sertakan token bila diperlukan.

### 6. Assignment

Function list assignment:

```text
mod_assign_get_assignments
```

Parameter umum:

```text
courseids[0]=COURSE_ID
```

Function status submission:

```text
mod_assign_get_submission_status
```

Dipakai untuk:

- Cek status submission.
- Cek grade.
- Cek feedback.
- Cek apakah masih bisa submit.

Function submit:

```text
mod_assign_save_submission
mod_assign_submit_for_grading
```

Alur upload file assignment:

1. `core_files_get_unused_draft_itemid`
2. `core_files_upload`
3. `mod_assign_save_submission`
4. `mod_assign_submit_for_grading`

### 7. Quiz

Function list quiz:

```text
mod_quiz_get_quizzes_by_courses
```

Function detail akses:

```text
mod_quiz_get_quiz_access_information
```

Function attempt user:

```text
mod_quiz_get_user_attempts
```

Function nilai terbaik:

```text
mod_quiz_get_user_best_grade
```

Catatan:

- Untuk MVP, lebih aman membuka quiz via webview Moodle.
- Jika quiz dikerjakan native dari mobile, perlu tambahan function attempt seperti `mod_quiz_start_attempt`, `mod_quiz_get_attempt_data`, `mod_quiz_save_attempt`, dan `mod_quiz_process_attempt`.

### 8. Nilai

Ringkasan semua course:

```text
gradereport_overview_get_course_grades
```

Detail nilai satu course:

```text
gradereport_user_get_grade_items
```

Catatan:

- Moodle otomatis membatasi nilai sesuai permission dan visibility gradebook.
- Mobile tidak perlu mengambil grade hidden.

### 9. Calendar dan Deadline

Upcoming calendar:

```text
core_calendar_get_calendar_upcoming_view
```

Deadline/action event:

```text
core_calendar_get_action_events_by_timesort
```

Gunakan untuk:

- Deadline assignment.
- Close date quiz.
- Event course.
- Event user.

### 10. Notifikasi

List notifikasi:

```text
message_popup_get_popup_notifications
```

Unread count:

```text
message_popup_get_unread_popup_notification_count
```

Mark read:

```text
core_message_mark_notification_read
```

## Function Untuk Dosen

Tambahkan function berikut hanya jika aplikasi mobile punya mode dosen.

| Function | Kegunaan |
|---|---|
| `core_enrol_get_enrolled_users` | Mengambil daftar peserta course. |
| `core_course_get_enrolled_users_by_cmid` | Mengambil user yang enrolled/terkait pada activity tertentu. |
| `core_user_get_course_user_profiles` | Mengambil profil user dalam course. |
| `mod_assign_list_participants` | Mengambil peserta assignment. |
| `mod_assign_get_submissions` | Mengambil submission assignment peserta. |
| `mod_assign_get_grades` | Mengambil grade assignment. |
| `mod_assign_save_grade` | Menyimpan nilai satu mahasiswa. |
| `mod_assign_save_grades` | Menyimpan nilai banyak mahasiswa. |
| `mod_assign_view_grading_table` | Mencatat/menampilkan akses grading table. |
| `core_grades_get_gradable_users` | Mengambil user yang bisa dinilai pada gradebook. |
| `core_grades_get_gradeitems` | Mengambil item grade dalam course. |
| `gradereport_grader_get_users_in_report` | Mengambil user dalam grader report. |

## Function Yang Tidak Disarankan Untuk MVP

Jangan tambahkan function admin/management berikut kecuali benar-benar diperlukan:

```text
core_user_create_users
core_user_update_users
core_user_delete_users
core_course_create_courses
core_course_update_courses
core_course_delete_courses
core_enrol_unenrol_user_enrolment
core_grades_update_grades
```

Alasannya:

- Risiko keamanan tinggi.
- Tidak dibutuhkan aplikasi mahasiswa.
- Bisa mengubah data akademik secara luas.

## Data Yang Perlu Diambil Mobile

Ambil data berikut:

- `userid`, fullname, username.
- Course id, fullname, shortname, category, progress.
- Section, module id, `modname`, activity name.
- Assignment due date, submission status, grade.
- Quiz open/close date, attempt status, best grade.
- Calendar action events.
- Notification id, subject, timecreated, read status.
- Grade item yang visible.

Hindari mengambil data berikut untuk menjaga API ringan:

- Daftar seluruh user site.
- Log aktivitas mentah.
- Detail question bank.
- Semua attempt quiz jika tidak diperlukan.
- Grade hidden.
- File binary besar langsung dari API; gunakan file URL Moodle.

## Testing Dengan Postman

Method:

```text
POST
```

URL:

```text
http://localhost/webservice/rest/server.php
```

Body type:

```text
x-www-form-urlencoded
```

Contoh site info:

```text
wstoken=TOKEN
wsfunction=core_webservice_get_site_info
moodlewsrestformat=json
```

Contoh my courses:

```text
wstoken=TOKEN
wsfunction=core_enrol_get_users_courses
moodlewsrestformat=json
userid=USER_ID
```

Contoh course contents:

```text
wstoken=TOKEN
wsfunction=core_course_get_contents
moodlewsrestformat=json
courseid=COURSE_ID
```

Contoh assignments:

```text
wstoken=TOKEN
wsfunction=mod_assign_get_assignments
moodlewsrestformat=json
courseids[0]=COURSE_ID
```

Contoh quizzes:

```text
wstoken=TOKEN
wsfunction=mod_quiz_get_quizzes_by_courses
moodlewsrestformat=json
courseids[0]=COURSE_ID
```

## Error Umum

| Error | Penyebab | Solusi |
|---|---|---|
| `invalidtoken` | Token salah, expired, atau bukan untuk service ini. | Generate token ulang untuk service `usu_mobile_api`. |
| `servicenotavailable` | External service belum enabled. | Enable service di External services. |
| `accessexception` | User tidak punya permission untuk function/context. | Cek role, capability, enrolment course, dan authorised users. |
| `accessdenied` | User mencoba membaca data yang bukan haknya. | Pastikan user enrolled di course dan function sesuai role. |
| `invalidparameter` | Format parameter salah. | Cek nama parameter array seperti `courseids[0]`. |
| `Function is not available` | Function belum ditambahkan ke service. | Tambahkan function ke `USU Mobile API`. |

## Rekomendasi Keamanan Production

- Gunakan HTTPS.
- Jangan gunakan token admin untuk aplikasi mobile.
- Gunakan service khusus `usu_mobile_api`.
- Aktifkan `Authorised users only`.
- Tambahkan hanya function yang benar-benar dipakai.
- Pisahkan service mahasiswa dan service dosen jika fitur dosen sudah luas.
- Gunakan token per user, bukan satu token global untuk semua user.
- Batasi lifetime token bila kebijakan kampus mengharuskan.
