<?php
// =========================================================================
// PANGGIL FILE KONEKSI
// =========================================================================
require_once 'koneksi.php'; 

// Inisialisasi variabel pesan (dari proses POST)
$message = ""; 

// LOGIKA INPUT DATA (Sama seperti sebelumnya)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil dan bersihkan data dari form
    $nama_lengkap = $conn->real_escape_string($_POST['nama_lengkap']);
    $asal_sekolah = $conn->real_escape_string($_POST['asal_sekolah']);
    $nilai_asesmen = floatval($_POST['nilai_asesmen']); 

    if (!empty($nama_lengkap) && !empty($asal_sekolah) && $nilai_asesmen >= 0) {
        // Query untuk INSERT data menggunakan prepared statement
        $stmt = $conn->prepare("INSERT INTO pendaftar (nama_lengkap, asal_sekolah, nilai_asesmen) VALUES (?, ?, ?)");
        $stmt->bind_param("ssd", $nama_lengkap, $asal_sekolah, $nilai_asesmen); 

        if ($stmt->execute()) {
            $message = "<div class='alert success'>✅ Data berhasil disimpan!</div>";
        } else {
            $message = "<div class='alert error'>❌ Error saat menyimpan: " . $stmt->error . "</div>";
        }
        $stmt->close();
    } else {
        $message = "<div class='alert warning'>⚠️ Mohon isi semua kolom dengan benar.</div>";
    }
}

// =========================================================================
// LOGIKA PAGINASI
// =========================================================================

$data_per_halaman = 5; // Tentukan berapa data per halaman

// 1. Hitung total data
$total_data_query = $conn->query("SELECT COUNT(id) AS total FROM pendaftar");
$total_data = $total_data_query->fetch_assoc()['total'];
$total_halaman = ceil($total_data / $data_per_halaman);

// 2. Tentukan halaman saat ini
$halaman_saat_ini = (isset($_GET['halaman']) && is_numeric($_GET['halaman'])) ? (int)$_GET['halaman'] : 1;

// Pastikan halaman tidak kurang dari 1 atau melebihi total halaman
if ($halaman_saat_ini < 1) $halaman_saat_ini = 1;
if ($halaman_saat_ini > $total_halaman) $halaman_saat_ini = $total_halaman;
if ($total_data == 0) $halaman_saat_ini = 1;

// 3. Hitung offset (awal data yang diambil)
$offset = ($halaman_saat_ini - 1) * $data_per_halaman;
if ($offset < 0) $offset = 0; // Pastikan offset tidak negatif

// =========================================================================
// LOGIKA TAMPIL DATA (Dengan LIMIT dan OFFSET)
// =========================================================================
$data_pendaftar = [];
// Perhatikan penggunaan LIMIT dan OFFSET di sini untuk paginasi
$sql_select = "SELECT nama_lengkap, asal_sekolah, tanggal_input FROM pendaftar ORDER BY tanggal_input DESC LIMIT $offset, $data_per_halaman"; 
$result = $conn->query($sql_select);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $data_pendaftar[] = $row;
    }
}

$conn->close(); 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Hasil Asesmen Guru</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
   <style>
        /* CSS DARI KODE SEBELUMNYA DITEMPATKAN DI SINI */
        :root {
            --primary-color: #4a90e2; 
            --secondary-color: #50e3c2; 
            --text-color: #333;
            --bg-color: #f4f7f6;
            --card-bg: #ffffff;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: transparent;
            color: var(--text-color);
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .container {
            width: 100%;
            max-width: 900px;
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
        }

        @media (min-width: 768px) {
            .container {
                grid-template-columns: 1fr 2fr; 
            }
        }

        .card {
            background-color: var(--card-bg);
            padding: 30px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
             transform: translateY(-5px);
          	box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        h2 {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 25px;
            border-bottom: 3px solid var(--secondary-color);
            padding-bottom: 5px;
        }

        /* Gaya Form */
        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }

        input[type="text"], input[type="number"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 16px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        input[type="text"]:focus, input[type="number"]:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 4px rgba(74, 144, 226, 0.25);
        }

        button {
            background-color: var(--primary-color);
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background-color 0.3s, transform 0.2s;
        }

        button:hover {
            background-color: #3b7ad0;
            transform: translateY(-2px);
        }

        /* Gaya Alert */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
        }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .warning { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }

        /* Gaya Tabel Tampilan Data */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .data-table th, .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .data-table th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
        }

        .data-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .data-table tr:hover {
            background-color: #f1f1f1;
        }
        
        /* Gaya Paginasi */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            flex-wrap: wrap; /* Agar responsif */
        }

        .pagination a, .pagination span {
            color: var(--primary-color);
            padding: 8px 16px;
            text-decoration: none;
            transition: background-color 0.3s;
            margin: 0 4px;
            border-radius: 6px;
            font-weight: 600;
            border: 1px solid #ddd;
        }

        .pagination a:hover {
            background-color: #eee;
        }

        .pagination span.active {
            background-color: var(--primary-color);
            color: white;
            border: 1px solid var(--primary-color);
        }
        .pagination span.disabled {
            color: #ccc;
            cursor: not-allowed;
        }
     /* GAYA VIDEO BACKGROUND */
      #myVideo {
          position: fixed; /* Memastikan video tetap di tempatnya saat scroll */
          right: 0;
          bottom: 0;
          min-width: 100%; 
          min-height: 100%;
          width: auto;
          height: auto;
          z-index: -100; /* Memindahkan video ke lapisan paling belakang */
          background-size: cover;
          filter: brightness(0.6); /* Menggelapkan video agar teks di atasnya lebih mudah dibaca */
      }
     
    </style>
</head>
<body>
<video autoplay muted loop id="myVideo">
  <source src="https://cdn.pixabay.com/video/2022/10/08/134046-760489590_tiny.mp4" type="video/mp4">
  
  Browser Anda tidak mendukung tag video.
</video>
<div class="container">
    <div class="card form-section">
        <h2>Formulir Hasil Asesmen Guru</h2>
        
        <?php echo $message; // Menampilkan pesan sukses/error ?>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="nama_lengkap">Nama Lengkap</label>
                <input type="text" id="nama_lengkap" name="nama_lengkap" required>
            </div>
            
            <div class="form-group">
                <label for="asal_sekolah">Asal Sekolah</label>
                <input type="text" id="asal_sekolah" name="asal_sekolah" required>
            </div>
            
            <div class="form-group">
                <label for="nilai_asesmen">Nilai Asesmen (0-100)</label>
                <input type="number" id="nilai_asesmen" name="nilai_asesmen" min="0" max="100" step="0.01" required>
            </div>
            
            <button type="submit">Kirim Data</button>
        </form>
    </div>

    <div class="card data-section">
        <h2>Data Masuk (Halaman <?php echo $halaman_saat_ini; ?> dari <?php echo $total_halaman; ?>)</h2>
        <?php if (count($data_pendaftar) > 0): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nama Lengkap</th>
                        <th>Asal Sekolah</th>
                      	<th>Tanggal Input</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data_pendaftar as $data): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($data['nama_lengkap']); ?></td>
                            <td><?php echo htmlspecialchars($data['asal_sekolah']); ?></td>
                          	<td><?php echo htmlspecialchars($data['tanggal_input']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="pagination">
                <?php 
                // Link Sebelumnya
                if ($halaman_saat_ini > 1) {
                    echo '<a href="?halaman=' . ($halaman_saat_ini - 1) . '">« Sebelumnya</a>';
                } else {
                    echo '<span class="disabled">« Sebelumnya</span>';
                }

                // Tautan Angka Halaman
                for ($i = 1; $i <= $total_halaman; $i++) {
                    if ($i == $halaman_saat_ini) {
                        echo '<span class="active">' . $i . '</span>';
                    } else {
                        echo '<a href="?halaman=' . $i . '">' . $i . '</a>';
                    }
                }

                // Link Selanjutnya
                if ($halaman_saat_ini < $total_halaman) {
                    echo '<a href="?halaman=' . ($halaman_saat_ini + 1) . '">Selanjutnya »</a>';
                } else {
                    echo '<span class="disabled">Selanjutnya »</span>';
                }
                ?>
            </div>
            <p style="margin-top: 15px; font-size: 0.9em; color: #777;">*Nilai asesmen tidak ditampilkan untuk menjaga kerahasiaan.</p>
        <?php else: ?>
            <p>Belum ada data yang ditampilkan.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>