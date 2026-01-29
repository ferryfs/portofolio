<?php
// Ganti ini dengan password yang Bos mau
$password_baru = "Operator123"; 

// Generate Hash yang Aman (Bcrypt)
$hash = password_hash($password_baru, PASSWORD_DEFAULT);

echo "<h3>Password Generator</h3>";
echo "Password Asli: <b>" . $password_baru . "</b><br><br>";
echo "Copy kode di bawah ini ke Database (tabel users > kolom password):<br>";
echo "<input type='text' value='" . $hash . "' style='width: 500px; padding: 10px; font-size: 16px;'>";
?>