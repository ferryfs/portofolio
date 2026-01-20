<?php
// Panggil Kunci
require_once 'secrets.php';

// Endpoint buat minta daftar model
$url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $apiKey;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h1>Daftar Model AI untuk API Key Kamu:</h1>";

if ($httpCode == 200) {
    $data = json_decode($response, true);
    
    if (isset($data['models'])) {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; font-family: Arial;'>";
        echo "<tr style='background: #ddd;'><th>Nama Model (Copy Bagian Ini)</th><th>Versi/Deskripsi</th><th>Bisa Buat Chat?</th></tr>";
        
        foreach ($data['models'] as $model) {
            // Kita cuma butuh model yang support 'generateContent'
            $bisaChat = in_array("generateContent", $model['supportedGenerationMethods']) ? "✅ YA" : "❌ GAK";
            
            // Hapus prefix 'models/' biar gampang dibaca
            $cleanName = str_replace("models/", "", $model['name']);
            
            echo "<tr>";
            echo "<td><b>{$cleanName}</b></td>";
            echo "<td>{$model['displayName']} <br> <small>{$model['version']}</small></td>";
            echo "<td>{$bisaChat}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "Daftar kosong. Coba cek API Key.";
    }
} else {
    echo "<h3>Gagal mengambil data ($httpCode)</h3>";
    echo "Response: " . $response;
}
?>