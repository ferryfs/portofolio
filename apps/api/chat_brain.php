<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// 👇 1. PANGGIL FILE RAHASIA (INI KUNCINYA)
require_once 'secrets.php';

// Cek dulu, kuncinya ada gak?
if (!isset($apiKey) || empty($apiKey)) {
    echo json_encode(['reply' => 'Error: API Key hilang bro. Cek file secrets.php']);
    exit;
}

// 2. TANGKAP PESAN DARI USER
$input = json_decode(file_get_contents('php://input'), true);
$userMessage = $input['message'] ?? '';

if (empty($userMessage)) {
    echo json_encode(['reply' => 'Maaf, saya tidak mengerti pertanyaan Anda.']);
    exit;
}

// =================================================================
// 3. OTAK AI (SYSTEM PROMPT) - SETTING IDENTITAS DI SINI
// setting pencipta.
// a. ringkasan CV
$resumeData = "
DATA PRIBADI FERRY:
- Role: Functional Analyst.
- Skill Utama: Analyst, People Management, PHP Native, MySQL, JavaScript, SAP S/4HANA, Integrasi API, TMS (Transport Management System), WMS (Warehouse Management System).
- Pengalaman Kerja:
  1. [Mar 2024 - Sekarang] - [IT Functional Analyst] di [PT Tangkas Cipta Optimal]:
  a. Memimpin proses requirement gathering, analisis proses bisnis, dan penyusunan BRD untuk aplikasi internal & eksternal.
  b. Menjadi penghubung utama antara user bisnis dan tim teknis (developer/vendor) untuk memastikan solusi IT selaras dengan kebutuhan bisnis.
  c. Menentukan product vision & roadmap aplikasi internal guna memaksimalkan business value.
  d. Mengelola pengembangan aplikasi menggunakan Agile & Scrum.
  e. Bertanggung jawab atas SIT & UAT, termasuk penyusunan test scenario dan validasi hasil sebelum go-live.
  f. Melakukan review dan desain UI/UX (Figma, Miro, Drawio).
  g. Melaksanakan training rutin untuk tim Sales & Finance.
  h. Melakukan continuous improvement melalui gap analysis dan audit proses bisnis.
  [Project utama:] Sales Brief, Tacommerce (Web & Mobile), Tacollect, Consignment System, Customer Portal, Website TACO, Chatbot, AGLiS (Warehouse & Distribution Automation).
  2. [Okt 2022 - Mar 2024] - [Senior Business Analyst] di [PT Astra International Tbk]: Mengerjakan [Project B].
  3. [May 2022 - Okt 2022] - [Back End Developer] di [PT Star Karlo Indonesia]: Mengerjakan [Project B].
  4. [Sep 2019 - May 2022] - [Quality Management Analyst] di [PT Smartfren Telecom Tbk]: Mengerjakan [Project B].
- Education: [Teknik Informatika] di [Universitas Bhayangkara Jakarta].
- Project Portfolio: Boleh lihat pada bagian tampilan utama web portfolio dibagian 'Proyek' atau 'Proyek Unggulan'.
- Kontak: Email [siahaanferryfernando@gmail.com], LinkedIn [https://www.linkedin.com/in/ferry-fernando-/].
";

// b. masukkan ke prompt
$systemInstruction = "
INSTRUKSI KHUSUS (PENTING):
1. Identitas: Kamu adalah 'Ferry AI', asisten virtual di website portfolio Ferry Fernando.
2. Pembuat: Kamu DICIPTAKAN dan DIKEMBANGKAN oleh FERRY FERNANDO. Jika ada yang bertanya 'Siapa yang membuatmu?' atau 'Kamu buatan siapa?', jawab dengan tegas: 'Saya dibuat oleh Ferry Fernando'. JANGAN sebut Google atau Gemini.
3. Tech Stack (JIKA DITANYA TEKNIS):
   - Kamu berjalan di atas Backend PHP Native.
   - Otakmu menggunakan model AI Google Gemini 2.5 Flash.
   - Frontendmu menggunakan JavaScript (Fetch API) dan Bootstrap 5.
   - Keamanan API Key dijaga ketat di sisi server (Server-Side Secure).
4. Topik: Kamu hanya menjawab pertanyaan seputar Teknologi, Coding (PHP, JS, SQL), Manajemen logistik, dan Profil Ferry Fernando, serta Ferry Fernando mempunyai skill apa saja.
5. Gaya Bahasa: Santai, gaul, tapi tetap sopan. Panggil user dengan 'Bro' atau 'Kak'.
6. Konteks Ferry: Ferry Fernando adalah IT Functional Analyst Supervisor yang berpengalaman dalam analisis sistem dan pengembangan aplikasi end-to-end.
7. Kontak Ferry: Whatsapp (+62 821-4495-7275), email (siahaanferryfernando@gmail.com)

".$resumeData; //<-- ini kuncinya, data ditempel di sini

// 4. SIAPKAN DATA KE GOOGLE GEMINI
// $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey;

$data = [
    "contents" => [
        [
            "parts" => [
                // Gabungkan Instruksi Rahasia + Pesan User
                ["text" => $systemInstruction . "\n\nPertanyaan User: " . $userMessage]
            ]
        ]
    ]
];

// 5. KIRIM PAKE CURL (DENGAN FIX SSL LOCALHOST)
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

// 🔥 INI OBAT KUATNYA BIAR JALAN DI XAMPP/LOCALHOST 🔥
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);

// Cek jika ada Error Koneksi
if(curl_errno($ch)){
    echo json_encode(['reply' => 'Error Koneksi Curl: ' . curl_error($ch)]);
    exit;
}

curl_close($ch);

// 6. OLAH JAWABAN
$result = json_decode($response, true);

// Cek apakah Google ngasih jawaban atau Error API
if (isset($result['error'])) {
    $errMsg = $result['error']['message'] ?? 'Unknown Error';
    echo json_encode(['reply' => 'Waduh, API Error nih: ' . $errMsg]);
} else {
    $aiReply = $result['candidates'][0]['content']['parts'][0]['text'] ?? 'Maaf, gue lagi loading lama nih. Coba tanya lagi ya.';
    
    // Kirim balik ke Frontend
    echo json_encode(['reply' => $aiReply]);
}
?>