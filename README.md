# Moodle Local Plugin: Mobile API Gateway

Komponen: `local_api_moodle`

Plugin ini menyediakan REST API Gateway berbasis Moodle External Services untuk aplikasi mobile mahasiswa dan dosen. Semua endpoint tetap melewati token Moodle Web Services, capability Moodle, validasi context, dan data access bawaan Moodle.

> Catatan penting: nama folder Moodle production harus `api_moodle`, sehingga path akhirnya adalah `MOODLE_ROOT/local/api_moodle`. Jika source ini masih berada di `local/API-moodle`, rename folder tersebut sebelum install.

## Analisis Kelas USU

Berdasarkan halaman publik `https://kelas.usu.ac.id/`, LMS menggunakan brand Digital Class Universitas Sumatera Utara dan menonjolkan pembelajaran digital yang bisa diakses kapan saja. Navigasi utamanya terdiri dari daftar fakultas, Open Courses, Assessment, multi bahasa, login, pencarian course, upcoming events, online users, dan course cards.

Struktur kategori terlihat mengikuti pola:

- Fakultas, misalnya Medicine, Computer Science & IT, Economics & Business, Engineering, Social & Politics, Dentistry, dan fakultas lain.
- Program studi/jenjang, misalnya S-1, Profesi, S-2, S-3, Sp-1, Sp-2.
- Semester dan periode akademik, misalnya Semester 1, Semester 3, Ganjil T.A. 2025/2026, Genap T.A. 2025/2026.
- Area khusus seperti Open Courses/Public dan Assessment.

Fitur utama pengguna yang perlu didukung mobile:

- Login berbasis token web service.
- Dashboard ringkas: profil, course aktif, event dekat, notifikasi.
- Course list dan search course.
- Course content: section, aktivitas, resource, URL aktivitas.
- Assignment: due date, status submit, grade ringkas.
- Quiz/assessment: jadwal buka/tutup, attempt terakhir, grade ringkas.
- Gradebook ringkas per course.
- Calendar event.
- Notification.
- Teaching overview untuk dosen.

Kebutuhan mahasiswa:

- Melihat mata kuliah yang diikuti.
- Melihat materi per section/topik.
- Memantau deadline tugas dan kuis.
- Melihat status submission/attempt.
- Melihat nilai yang memang sudah visible.
- Menerima notifikasi dan event akademik.

Kebutuhan dosen:

- Melihat daftar course yang diajar.
- Melihat jumlah peserta, assignment, quiz.
- Mengakses course content dari mobile.
- Memantau aktivitas assessment secara ringkas.

Data penting dikirim API:

- Identitas user sendiri: id, username, fullname, email, bahasa, timezone, avatar.
- Metadata course: id, shortname, fullname, category, start/end date, image, progress.
- Section dan activity minimal: cmid, instanceid, modname, name, url, icon, visibility, completion.
- Assignment/quiz: waktu buka/tutup/deadline, status user, grade ringkas.
- Grade item yang visible untuk user.
- Calendar event visible.
- Notifikasi popup user.

Data yang sengaja tidak dikirim agar API ringan:

- HTML penuh course/modul kecuali intro plain text bila diminta.
- File binary; API hanya mengirim URL pluginfile/webservice.
- Daftar peserta lengkap.
- Log aktivitas mentah.
- Detail attempt quiz, jawaban, question bank.
- Grade hidden yang user tidak berhak lihat.
- Data user lain, kecuali agregat dosen seperti jumlah enrolled users.

## Endpoint

Base URL Moodle REST:

```text
https://kelas.usu.ac.id/webservice/rest/server.php
```

Parameter umum:

```text
wstoken=TOKEN
moodlewsrestformat=json
wsfunction=NAMA_FUNCTION
```

Daftar function:

- `local_api_moodle_get_site_structure`
- `local_api_moodle_search_courses`
- `local_api_moodle_get_mobile_dashboard`
- `local_api_moodle_get_my_courses`
- `local_api_moodle_get_course_detail`
- `local_api_moodle_get_course_contents`
- `local_api_moodle_get_assignments`
- `local_api_moodle_get_quizzes`
- `local_api_moodle_get_grades`
- `local_api_moodle_get_calendar_events`
- `local_api_moodle_get_notifications`
- `local_api_moodle_mark_notification_read`
- `local_api_moodle_get_teaching_overview`

## Penjelasan File

- `version.php`: metadata plugin, component name, versi, requirement Moodle 4.3.
- `db/services.php`: registrasi External Functions dan satu External Service bernama `USU Mobile API Gateway`.
- `db/access.php`: capability `local/api_moodle:use` dan `local/api_moodle:viewteaching`.
- `externallib.php`: implementasi semua endpoint External Services beserta struktur parameter dan return value.
- `classes/helper.php`: helper validasi user, mapping course, image URL, completion, course enrollment, notification count.
- `classes/privacy/provider.php`: privacy provider Moodle; plugin tidak menyimpan data baru.
- `lang/en/local_api_moodle.php`: language strings bahasa Inggris.
- `lang/id/local_api_moodle.php`: language strings bahasa Indonesia.
- `README.md`: dokumentasi install, konfigurasi, testing, dan contoh client.

## Install Plugin

1. Pastikan folder plugin bernama:

```text
MOODLE_ROOT/local/api_moodle
```

2. Login sebagai administrator.
3. Buka:

```text
Site administration > Notifications
```

4. Ikuti proses upgrade database Moodle.
5. Purge cache:

```text
Site administration > Development > Purge caches
```

Alternatif CLI dari root Moodle:

```bash
php admin/cli/upgrade.php
php admin/cli/purge_caches.php
```

## Enable External Services

1. Buka:

```text
Site administration > Advanced features
```

2. Aktifkan `Enable web services`.
3. Buka:

```text
Site administration > Server > Web services > Manage protocols
```

4. Enable protocol `REST`.
5. Buka:

```text
Site administration > Server > Web services > External services
```

6. Enable service `USU Mobile API Gateway`.
7. Karena service dibuat dengan `restrictedusers = 1`, tambahkan user yang diizinkan lewat menu `Authorised users`.
8. Pastikan role user punya capability:

```text
local/api_moodle:use
webservice/rest:use
```

Untuk dosen yang memakai teaching overview, pastikan di context course punya salah satu:

```text
local/api_moodle:viewteaching
moodle/course:update
mod/assign:grade
mod/quiz:grade
```

## Generate Token

Admin:

```text
Site administration > Server > Web services > Manage tokens > Add
```

Pilih:

- User: user mahasiswa/dosen/app service account.
- Service: `USU Mobile API Gateway`.
- Optional: IP restriction dan valid until untuk production.

Login token endpoint Moodle:

```http
POST https://kelas.usu.ac.id/login/token.php
Content-Type: application/x-www-form-urlencoded

username=USERNAME&password=PASSWORD&service=local_api_moodle_mobile
```

Response:

```json
{
  "token": "TOKEN",
  "privatetoken": "OPTIONAL_PRIVATE_TOKEN"
}
```

## Testing Postman

Method: `POST`

URL:

```text
https://kelas.usu.ac.id/webservice/rest/server.php
```

Body type: `x-www-form-urlencoded`

Dashboard:

```text
wstoken=TOKEN
wsfunction=local_api_moodle_get_mobile_dashboard
moodlewsrestformat=json
courselimit=8
eventlimit=10
```

My courses:

```text
wstoken=TOKEN
wsfunction=local_api_moodle_get_my_courses
moodlewsrestformat=json
limit=20
offset=0
includesummary=0
```

Course contents:

```text
wstoken=TOKEN
wsfunction=local_api_moodle_get_course_contents
moodlewsrestformat=json
courseid=123
includehidden=0
```

Assignments:

```text
wstoken=TOKEN
wsfunction=local_api_moodle_get_assignments
moodlewsrestformat=json
courseid=0
onlyactive=1
includeintro=0
limit=20
offset=0
```

Grades:

```text
wstoken=TOKEN
wsfunction=local_api_moodle_get_grades
moodlewsrestformat=json
courseid=123
```

Mark notification read:

```text
wstoken=TOKEN
wsfunction=local_api_moodle_mark_notification_read
moodlewsrestformat=json
notificationid=456
```

## Contoh Flutter

```dart
import 'dart:convert';
import 'package:http/http.dart' as http;

class MoodleApi {
  MoodleApi({required this.baseUrl, required this.token});

  final String baseUrl;
  final String token;

  Future<Map<String, dynamic>> call(
    String function, {
    Map<String, String> params = const {},
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/webservice/rest/server.php'),
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: {
        'wstoken': token,
        'wsfunction': function,
        'moodlewsrestformat': 'json',
        ...params,
      },
    );

    final data = jsonDecode(response.body) as Map<String, dynamic>;
    if (data.containsKey('exception')) {
      throw Exception('${data['errorcode']}: ${data['message']}');
    }
    return data;
  }

  Future<Map<String, dynamic>> dashboard() {
    return call('local_api_moodle_get_mobile_dashboard', params: {
      'courselimit': '8',
      'eventlimit': '10',
    });
  }

  Future<Map<String, dynamic>> courseContents(int courseId) {
    return call('local_api_moodle_get_course_contents', params: {
      'courseid': '$courseId',
      'includehidden': '0',
    });
  }
}
```

## Contoh React Native

```javascript
const MOODLE_URL = 'https://kelas.usu.ac.id';
const TOKEN = 'TOKEN';

async function callMoodle(wsfunction, params = {}) {
  const body = new URLSearchParams({
    wstoken: TOKEN,
    wsfunction,
    moodlewsrestformat: 'json',
    ...params,
  });

  const response = await fetch(`${MOODLE_URL}/webservice/rest/server.php`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: body.toString(),
  });

  const data = await response.json();
  if (data.exception) {
    throw new Error(`${data.errorcode}: ${data.message}`);
  }
  return data;
}

export async function getDashboard() {
  return callMoodle('local_api_moodle_get_mobile_dashboard', {
    courselimit: '8',
    eventlimit: '10',
  });
}

export async function getAssignments(courseid = 0) {
  return callMoodle('local_api_moodle_get_assignments', {
    courseid: String(courseid),
    onlyactive: '1',
    includeintro: '0',
    limit: '20',
    offset: '0',
  });
}
```

## Production Notes

- Wajib HTTPS.
- Jangan pakai token admin untuk mobile user umum.
- Gunakan restricted users dan token expiry.
- Batasi CORS di reverse proxy bila aplikasi mobile memakai webview/browser layer.
- Log dan monitor response time endpoint yang mengumpulkan semua course.
- Untuk payload besar, selalu gunakan pagination dan `includeintro=0`/`includesummary=0`.
