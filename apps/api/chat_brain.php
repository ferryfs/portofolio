<?php
// ============================================================
// 🛡️ CONFIG & SECURITY
// ============================================================

// Daftar domain yang BOLEH akses
$allowed_origins = [
    'http://localhost', 
    'http://127.0.0.1',
    'https://ferryfernando.com', // Ganti domain asli nanti
];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
}

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// ============================================================
// 🛡️ RATE LIMITING
// ============================================================
session_start();

$limit_seconds = 1; 

if (isset($_SESSION['last_chat_time'])) {
    $seconds_since_last = time() - $_SESSION['last_chat_time'];
    if ($seconds_since_last < $limit_seconds) {
        session_write_close();
        echo json_encode(['reply' => 'Sabar bro, jangan spam ya. Tunggu bentar ☕']);
        exit;
    }
}

$_SESSION['last_chat_time'] = time();
session_write_close(); 

// ============================================================
// 1. SETUP & INPUT HANDLING
// ============================================================
require_once 'secrets.php'; 

if (!isset($apiKey) || empty($apiKey)) {
    error_log("FerryAI Critical: API Key Missing");
    echo json_encode(['reply' => 'Sistem Error: API Key tidak ditemukan.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$rawMessage = $input['message'] ?? '';
$userMessage = htmlspecialchars(strip_tags(trim($rawMessage)), ENT_QUOTES, 'UTF-8');

if (empty($userMessage)) {
    echo json_encode(['reply' => 'Ketik sesuatu dulu dong bro.']);
    exit;
}

// ============================================================
// 2. OTAK & DATA (SYSTEM PROMPT)
// ============================================================
$resumeData = "
DATA RESMI FERRY FERNANDO:
- Role: IT Functional Analyst Supervisor di PT Tangkas Cipta Optimal.
- Skill: Analisa Sistem (BRD, FSD), Project Management (Agile), Tech (PHP, MySQL, JS), Enterprise (SAP, WMS, TMS).
- Kontak: siahaanferryfernando@gmail.com.
";

$systemInstruction = "
PERAN: Kamu adalah 'Ferry AI', asisten virtual profesional Ferry Fernando.

ATURAN BAHASA (PRIORITAS TERTINGGI):
1. DETEKSI bahasa yang digunakan User dalam pesan TERAKHIRNYA.
2. JIKA User pakai Bahasa Inggris -> JAWAB FULL BAHASA INGGRIS.
3. JIKA User pakai Bahasa Indonesia -> JAWAB FULL BAHASA INDONESIA.
4. Jangan campur kode bahasa. Ikuti lawan bicara.

IDENTITAS:
- Pencipta: FERRY FERNANDO. (Jangan sebut Google/Gemini).
- Gaya: Santai, Profesional, Bermanfaat. Panggil 'Bro' atau 'Kak'.

BATASAN:
- Hanya jawab seputar profil Ferry, Skill, dan Project.
- Jangan halusinasi. Kalau tidak ada di data, bilang tidak tahu.

DATA FERRY:
" . $resumeData;

// ============================================================
// 3. KOMUNIKASI KE GEMINI
// ============================================================

// 🔥 PILIHAN MANTEP: GEMINI 2.0 FLASH (STABIL & PINTER) 🔥
$model = "gemini-2.5-flash"; 
$url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $apiKey;

$data = [
    "system_instruction" => [
        "parts" => [ ["text" => $systemInstruction] ]
    ],
    "contents" => [
        [
            "role" => "user",
            "parts" => [ ["text" => $userMessage] ]
        ]
    ],
    "generationConfig" => [
        "temperature" => 0.7, 
        "maxOutputTokens" => 500,
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// ============================================================
// 4. ERROR HANDLING & OUTPUT
// ============================================================

if ($curlError) {
    echo json_encode(['reply' => "Maaf, server lagi gangguan koneksi."]);
    exit;
}

if ($httpCode !== 200) {
    error_log("FerryAI API Error ($httpCode): " . $response);

    if ($httpCode == 429) {
        echo json_encode(['reply' => 'Waduh, antrian lagi penuh banget. Coba 1 menit lagi ya.']);
    } else if ($httpCode == 404) {
        // Fallback kalau-kalau akun lu belum dapet akses 2.0 (Jaga-jaga)
        echo json_encode(['reply' => 'Fitur AI terbaru lagi maintenance (404). Hubungi Admin.']);
    } else {
        echo json_encode(['reply' => "Maaf, ada gangguan teknis ($httpCode)."]);
    }
    exit;
}

$result = json_decode($response, true);

if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
    $aiReply = $result['candidates'][0]['content']['parts'][0]['text'];
    $aiReply = str_replace(['**', '##'], '', $aiReply); 
    echo json_encode(['reply' => $aiReply]);
} else {
    echo json_encode(['reply' => 'Pertanyaanmu agak sensitif atau membingungkan nih, ganti topik yuk?']);
}
?>