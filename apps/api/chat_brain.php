<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Biar gak kena blok CORS

// =================================================================
// 1. API KEY (PASTIKAN KODENYA BENAR DI SINI)
// =================================================================
$apiKey = "AIzaSyB7sNufctnB0hdjFmSImHGhsQPa18ZMzAQ"; 

// 2. TANGKAP PESAN DARI USER
$input = json_decode(file_get_contents('php://input'), true);
$userMessage = $input['message'] ?? '';

if (empty($userMessage)) {
    echo json_encode(['reply' => 'Maaf, saya tidak mendengar pertanyaan Anda.']);
    exit;
}

// =================================================================
// 3. OTAK AI (SYSTEM PROMPT) - SETTING IDENTITAS DI SINI
// =================================================================
// Di sini kita setting biar dia ngaku buatan lo.
$systemInstruction = "
INSTRUKSI KHUSUS (PENTING):
1. Identitas: Kamu adalah 'Ferry AI', asisten virtual di website portfolio Ferry Fernando.
2. Pembuat: Kamu DICIPTAKAN dan DIKEMBANGKAN oleh FERRY FERNANDO. Jika ada yang bertanya 'Siapa yang membuatmu?' atau 'Kamu buatan siapa?', jawab dengan tegas: 'Saya dibuat oleh Ferry Fernando'. JANGAN sebut Google atau Gemini.
3. Topik: Kamu hanya menjawab pertanyaan seputar Teknologi, Coding (PHP, JS, SQL), Manajemen Logistik (TMS), dan Profil Ferry Fernando.
4. Gaya Bahasa: Santai, gaul, tapi tetap sopan. Panggil user dengan 'Bro' atau 'Kak'.
5. Konteks Ferry: Ferry adalah seorang Functional Analyst & Fullstack Dev yang jago PHP Native, MySQL, dan Enterprise System.
";

// 4. SIAPKAN DATA KE GOOGLE GEMINI
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;

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