<!-- ══════════ TAB: OVERVIEW ══════════ -->
<div class="guide-section active" id="gs-intro">
    <div class="feature-grid mb-4">
        <?php $features = [
            ['fa-gauge-high','#4f46e5','eef2ff','Dashboard','KPI real-time, peta monitoring, recent activity, dan fleet status.'],
            ['fa-truck-ramp-box','#059669','d1fae5','Orders (SO/DO)','Buat & kelola transport order. Dispatch ke driver dan kendaraan.'],
            ['fa-signature','#f59e0b','fffbeb','Outbound POD','Digital signature pengirim & penerima. Validasi qty & exception handling.'],
            ['fa-truck','#06b6d4','cffafe','Fleet Management','Master data kendaraan internal & 3PL. Monitor status & dokumen.'],
            ['fa-users-gear','#7c3aed','ede9fe','Drivers','Registrasi driver, SIM, dan shipment aktif real-time.'],
            ['fa-file-invoice-dollar','#dc2626','fef2f2','Billing & Cost','Auto kalkulasi biaya per berat. Rate internal vs 3PL.'],
        ]; foreach($features as $f): ?>
        <div class="feature-box">
            <div class="fb-icon" style="background:#<?=$f[2]?>;color:<?=$f[1]?>;"><i class="fa <?=$f[0]?>"></i></div>
            <strong><?=$f[3]?></strong>
            <p><?=$f[4]?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="data-card p-4 mb-3">
        <h6 class="fw-bold mb-3"><i class="fa fa-route me-2 text-warning"></i>Alur Kerja End-to-End</h6>
        <table class="info-tbl">
            <thead><tr><th>#</th><th>Tahap</th><th>Aktor</th><th>Yang Dilakukan</th><th>Hasil</th></tr></thead>
            <tbody>
                <tr><td>1</td><td>SO Masuk</td><td>Admin / NAV</td><td>Pull data dari NAV atau input manual di halaman Orders</td><td>Order berstatus "New"</td></tr>
                <tr><td>2</td><td>Dispatch</td><td>Dispatcher</td><td>Assign driver + kendaraan ke order</td><td>Shipment dibuat, status "Planned"</td></tr>
                <tr><td>3</td><td>Release Gudang</td><td>Admin Gudang</td><td>Tanda tangan digital → Approve & Release di Outbound POD</td><td>DN status "In Transit"</td></tr>
                <tr><td>4</td><td>Perjalanan</td><td>Dispatcher</td><td>Update status shipment: In Transit → Arrived</td><td>Bisa dimonitor di dashboard</td></tr>
                <tr><td>5</td><td>Penerimaan</td><td>Store Manager</td><td>Cek qty item, input qty rusak, tanda tangan digital</td><td>DN status "Delivered" / Exception jika ada selisih</td></tr>
                <tr><td>6</td><td>Resolve Exception</td><td>Admin Logistik</td><td>Pilih tindakan: Backorder, Klaim Vendor, Write-Off, atau Resolved</td><td>Exception diselesaikan, hilang dari panel</td></tr>
                <tr><td>7</td><td>Billing</td><td>Finance</td><td>Auto kalkulasi atau input manual biaya pengiriman</td><td>Shipment "Completed", kendaraan kembali Available</td></tr>
            </tbody>
        </table>
    </div>

    <div class="gn yellow"><strong>ℹ️ Demo Mode:</strong> Tombol "Pull NAV Data" mensimulasikan sinkronisasi dari ERP Microsoft Dynamics NAV. Di lingkungan produksi, proses ini terhubung ke API NAV secara real-time. Akun demo: <strong>t4mu / Tamu123</strong></div>
</div>

<!-- ══════════ TAB: DASHBOARD ══════════ -->
<div class="guide-section" id="gs-dashboard">
    <div class="data-card p-4 mb-3">
        <h6 class="fw-bold mb-3"><i class="fa fa-gauge-high me-2 text-warning"></i>4 KPI Cards (Baris Atas)</h6>
        <table class="info-tbl">
            <thead><tr><th>KPI Card</th><th>Isi & Cara Baca</th><th>Kapan Perlu Diperhatikan</th></tr></thead>
            <tbody>
                <tr><td>Total Orders</td><td>Jumlah semua transport order yang pernah dibuat, semua status</td><td>Referensi volume — tidak perlu aksi</td></tr>
                <tr><td>Active Shipments</td><td>Shipment berstatus Planned + In Transit + Arrived. Border kuning kalau ada</td><td>Jika angka besar, artinya banyak pengiriman belum selesai</td></tr>
                <tr><td>On-Time Delivery %</td><td>% DN berstatus Delivered dari total DN. Makin tinggi makin baik</td><td>Di bawah 80% perlu investigasi penyebab keterlambatan</td></tr>
                <tr><td>Exceptions</td><td>Jumlah item dengan shortage atau damage yang belum di-resolve</td><td>Jika &gt; 0 → segera buka Outbound POD dan resolve</td></tr>
            </tbody>
        </table>
    </div>
    <div class="data-card p-4 mb-3">
        <h6 class="fw-bold mb-3"><i class="fa fa-map me-2 text-warning"></i>Live Monitoring Map</h6>
        <div class="step-item"><span class="step-num"><i class="fa fa-building" style="font-size:0.7rem;"></i></span><div><strong>Ikon 🏭 (gudang)</strong><span>Menandai lokasi warehouse. Klik untuk melihat nama dan alamat lengkap.</span></div></div>
        <div class="step-item"><span class="step-num"><i class="fa fa-store" style="font-size:0.7rem;"></i></span><div><strong>Ikon 🏪 (store)</strong><span>Menandai lokasi toko/store tujuan pengiriman.</span></div></div>
        <div class="step-item"><span class="step-num"><i class="fa fa-minus" style="font-size:0.7rem;"></i></span><div><strong>Garis putus-putus kuning</strong><span>Rute shipment yang sedang aktif (In Transit). Klik garis untuk melihat detail shipment, driver, dan plat kendaraan.</span></div></div>
        <div class="step-item"><span class="step-num"><i class="fa fa-minus" style="font-size:0.7rem;"></i></span><div><strong>Garis putus-putus abu-abu</strong><span>Rute shipment yang berstatus Planned (belum berangkat).</span></div></div>
    </div>
    <div class="data-card p-4">
        <h6 class="fw-bold mb-3"><i class="fa fa-chart-bar me-2 text-warning"></i>Widget Bawah</h6>
        <table class="info-tbl">
            <thead><tr><th>Widget</th><th>Isi</th></tr></thead>
            <tbody>
                <tr><td>Recent Activity</td><td>8 aktivitas terbaru dari seluruh sistem — gabungan shipment baru dan POD terbaru, urut dari terbaru</td></tr>
                <tr><td>Fleet Status</td><td>Status real-time semua kendaraan: Available (hijau), Busy (kuning), Maintenance (merah)</td></tr>
                <tr><td>Chart Shipment</td><td>Bar chart jumlah shipment yang dibuat per hari selama 7 hari terakhir</td></tr>
                <tr><td>Quick Stats</td><td>Ringkasan cepat: fleet tersedia, total driver, DN pending POD, dan shipment selesai</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ══════════ TAB: ORDERS ══════════ -->
<div class="guide-section" id="gs-orders">
    <div class="data-card p-4 mb-3">
        <h6 class="fw-bold mb-3">Tabel Orders — Penjelasan Kolom</h6>
        <table class="info-tbl">
            <thead><tr><th>Kolom</th><th>Penjelasan</th></tr></thead>
            <tbody>
                <tr><td>Order No</td><td>Nomor unik order (SO-xxx atau DO-xxx). Bisa dari NAV atau dibuat manual</td></tr>
                <tr><td>Type</td><td><span class="btn-doc blue">SALES</span> = pengiriman ke pelanggan/store. <span class="btn-doc yellow">TRANSFER</span> = perpindahan stok antar gudang</td></tr>
                <tr><td>Rute</td><td>Titik asal (gudang) → titik tujuan (store). Diambil dari tms_locations</td></tr>
                <tr><td>SLA Date</td><td>Tanggal target pengiriman — deadline harus sampai</td></tr>
                <tr><td>Berat</td><td>Total berat muatan dalam kg. Dipakai untuk kalkulasi biaya di Billing</td></tr>
                <tr><td>NAV Status</td><td><span class="btn-doc green">SYNCED</span> = data dari NAV. <span class="btn-doc outline">PENDING</span> = input manual, belum sinkron ke NAV</td></tr>
                <tr><td>Status Order</td><td>Status terakhir order (lihat tab Status Flow)</td></tr>
                <tr><td>Shipment</td><td>No. shipment yang dibuat, nama driver, dan plat kendaraan yang di-assign</td></tr>
            </tbody>
        </table>
    </div>
    <div class="data-card p-4 mb-3">
        <h6 class="fw-bold mb-3">Tombol-Tombol di Kolom Action</h6>
        <div class="step-item"><span class="step-num"><i class="fa fa-truck" style="font-size:0.7rem;"></i></span><div><strong><span class="btn-doc yellow">Dispatch</span> — Muncul hanya jika status order "New"</strong><span>Klik untuk membuka modal dispatch: pilih kendaraan yang Available dan driver, lalu klik "Assign & Dispatch". Sistem otomatis membuat shipment baru dan mengunci kendaraan jadi "Busy".</span></div></div>
        <div class="step-item"><span class="step-num"><i class="fa fa-arrows-rotate" style="font-size:0.7rem;"></i></span><div><strong><span class="btn-doc outline">Update Status</span> — Muncul jika sudah di-dispatch dan belum selesai</strong><span>Klik untuk membuka modal kecil ganti status shipment. Pilih status baru dan klik Update. Status Completed/Failed/Cancelled otomatis membebaskan kendaraan.</span></div></div>
        <div class="step-item"><span class="step-num"><i class="fa fa-check" style="font-size:0.7rem;"></i></span><div><strong><span class="btn-doc green">Selesai</span> — Muncul jika status Completed/Failed/Cancelled</strong><span>Tidak ada aksi — hanya sebagai indikator pengiriman sudah final.</span></div></div>
    </div>
    <div class="data-card p-4">
        <h6 class="fw-bold mb-3">Form Create Order — Penjelasan Field</h6>
        <table class="info-tbl">
            <thead><tr><th>Field</th><th>Penjelasan</th><th>Contoh</th></tr></thead>
            <tbody>
                <tr><td>No. SO / DO</td><td>Nomor dokumen — auto-generate dari timestamp tapi bisa diubah</td><td>SO-1742500000</td></tr>
                <tr><td>Tipe Transaksi</td><td>Sales = ke pelanggan, Transfer = antar gudang</td><td>Sales</td></tr>
                <tr><td>Asal / Tujuan</td><td>Pilih dari daftar lokasi yang sudah terdaftar di sistem</td><td>Gudang Pusat → RKM Store A</td></tr>
                <tr><td>SLA Date</td><td>Tanggal target pengiriman, wajib diisi</td><td>2026-03-25</td></tr>
                <tr><td>Berat (kg)</td><td>Total berat muatan — berpengaruh ke kalkulasi biaya di billing</td><td>500</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ══════════ TAB: OUTBOUND POD ══════════ -->
<div class="guide-section" id="gs-outbound">
    <div class="data-card p-4 mb-3">
        <h6 class="fw-bold mb-3"><i class="fa fa-cloud-arrow-down me-2 text-warning"></i>Tombol "Pull NAV Data"</h6>
        <p style="font-size:0.82rem; color:#374151;">Mensimulasikan sinkronisasi data dari ERP NAV. Setiap klik membuat satu paket data baru: SO header → DN → 2 LPN → 4 item (HPL White Gloss + Edging PVC 22mm per LPN). Di sistem produksi nyata, ini terhubung ke API NAV Business Central secara otomatis.</p>
    </div>
    <div class="data-card p-4 mb-3">
        <h6 class="fw-bold mb-3">Tabel Delivery Notes — Penjelasan Status & Tombol</h6>
        <table class="info-tbl">
            <thead><tr><th>Status DN</th><th>Artinya</th><th>Tombol yang Muncul</th></tr></thead>
            <tbody>
                <tr><td>1. Warehouse</td><td>DN baru dibuat, menunggu release dari gudang</td><td><span class="btn-doc yellow">Approve & Release</span></td></tr>
                <tr><td>2. On The Way</td><td>Barang sudah keluar gudang, dalam perjalanan</td><td><span class="btn-doc green">Receive</span></td></tr>
                <tr><td>3. Delivered</td><td>Barang sudah diterima di tujuan, POD lengkap</td><td><span class="btn-doc outline">Cetak SJ</span></td></tr>
            </tbody>
        </table>
    </div>
    <div class="data-card p-4 mb-3">
        <h6 class="fw-bold mb-3"><i class="fa fa-signature me-2 text-warning"></i>Proses Approve & Release (Sender)</h6>
        <div class="step-item"><span class="step-num">1</span><div><strong>Klik <span class="btn-doc yellow">Approve & Release</span></strong><span>Modal tanda tangan pengirim muncul. Berlaku untuk DN berstatus "Warehouse".</span></div></div>
        <div class="step-item"><span class="step-num">2</span><div><strong>Tanda tangan di canvas digital</strong><span>Gunakan mouse atau touchscreen. Admin gudang menandatangani sebagai bukti barang sudah diperiksa dan siap dikirim.</span></div></div>
        <div class="step-item"><span class="step-num">3</span><div><strong>Klik "Confirm & Release"</strong><span>Tanda tangan tersimpan sebagai base64 image. Status DN berubah dari "Warehouse" → "On The Way". Tombol Clear untuk menghapus tanda tangan dan mengulang.</span></div></div>
    </div>
    <div class="data-card p-4 mb-3">
        <h6 class="fw-bold mb-3"><i class="fa fa-box-open me-2 text-warning"></i>Proses Receive (Receiver) — Detail</h6>
        <div class="step-item"><span class="step-num">1</span><div><strong>Klik <span class="btn-doc green">Receive</span></strong><span>Modal penerimaan muncul dengan tabel semua item dari DN. Data item dimuat via AJAX dari get_dn_items.php.</span></div></div>
        <div class="step-item"><span class="step-num">2</span><div><strong>Isi "Qty Diterima" per item</strong><span>Default terisi sama dengan qty order. Ubah jika barang yang datang kurang dari dokumen. Sistem langsung menampilkan warning merah jika ada selisih.</span></div></div>
        <div class="step-item"><span class="step-num">3</span><div><strong>Isi "Qty Rusak" jika ada</strong><span>Input jumlah item yang rusak/cacat. Jika ada angka > 0, warning exception langsung muncul.</span></div></div>
        <div class="step-item"><span class="step-num">4</span><div><strong>Tanda tangan receiver</strong><span>Store Manager menandatangani sebagai bukti penerimaan. Wajib diisi sebelum bisa submit.</span></div></div>
        <div class="step-item"><span class="step-num">5</span><div><strong>Klik "Konfirmasi Penerimaan"</strong><span>Status DN → "Delivered". Jika ada exception, otomatis muncul di panel Exception Report dan badge merah di sidebar.</span></div></div>
        <div class="gn yellow"><strong>⚠️ Catatan:</strong> Exception tidak membatalkan pengiriman — DN tetap "Delivered". Exception dicatat terpisah dan harus di-resolve oleh admin.</div>
    </div>
    <div class="data-card p-4">
        <h6 class="fw-bold mb-3"><i class="fa fa-print me-2 text-warning"></i>Cetak Surat Jalan</h6>
        <p style="font-size:0.82rem; color:#374151;">Klik <span class="btn-doc outline">Cetak SJ</span> pada DN yang sudah Delivered untuk membuka halaman print. Dokumen berisi: header perusahaan, detail DN & SO, tabel item dengan qty, tanda tangan pengirim & penerima (sebagai gambar). Klik tombol "Cetak" di browser atau Ctrl+P.</p>
    </div>
</div>

<!-- ══════════ TAB: EXCEPTION ══════════ -->
<div class="guide-section" id="gs-exception">
    <div class="data-card p-4 mb-3">
        <h6 class="fw-bold mb-3"><i class="fa fa-triangle-exclamation me-2 text-danger"></i>Apa itu Exception?</h6>
        <p style="font-size:0.82rem; color:#374151; margin-bottom:12px;">Exception adalah kondisi di mana barang yang diterima store <strong>tidak sesuai</strong> dengan dokumen pengiriman. Ada dua jenis:</p>
        <table class="info-tbl">
            <thead><tr><th>Tipe</th><th>Kondisi</th><th>Contoh</th><th>Tindakan Umum</th></tr></thead>
            <tbody>
                <tr><td>Shortage</td><td>Qty Diterima &lt; Qty Order</td><td>SO minta 5 HPL, yang datang cuma 3</td><td>Backorder (kirim ulang sisa) atau Klaim Vendor</td></tr>
                <tr><td>Damage</td><td>Qty Rusak &gt; 0 saat penerimaan</td><td>2 lembar Edging PVC pecah/retak</td><td>Klaim Vendor/Transporter atau Write-Off</td></tr>
            </tbody>
        </table>
    </div>
    <div class="data-card p-4 mb-3">
        <h6 class="fw-bold mb-3">Panel Exception Report</h6>
        <p style="font-size:0.82rem; color:#374151;">Panel merah muncul di bagian atas halaman Outbound POD <strong>hanya jika ada exception yang belum di-resolve</strong>. Setiap kartu menampilkan: No. DN, Customer, jumlah item shortage, dan nama item yang damaged.</p>
        <div class="gn blue">Badge merah <strong>"Exceptions [angka]"</strong> di sidebar kiri juga muncul sebagai reminder — bisa diklik untuk langsung ke panel exception.</div>
    </div>
    <div class="data-card p-4">
        <h6 class="fw-bold mb-3"><i class="fa fa-wrench me-2 text-warning"></i>Cara Resolve Exception — Step by Step</h6>
        <div class="step-item"><span class="step-num">1</span><div><strong>Klik <span class="btn-doc red">Resolve</span> di kartu exception</strong><span>Modal Resolve Exception muncul dengan ringkasan masalah: berapa item shortage, item apa yang damaged.</span></div></div>
        <div class="step-item"><span class="step-num">2</span><div><strong>Pilih tindakan penanganan:</strong>
            <span>
                <br>📦 <strong>Backorder — Kirim Ulang:</strong> Item yang kurang akan dikirim di pengiriman berikutnya. Pilih ini jika stok di gudang masih ada dan ketinggalan saat loading.
                <br>📋 <strong>Klaim Vendor/Transporter:</strong> Laporkan ke pihak transporter atau vendor sebagai tanggung jawab mereka. Pilih ini jika kerusakan atau kehilangan terjadi selama perjalanan.
                <br>✏️ <strong>Write-Off / Catat Kerugian:</strong> Catat sebagai kerugian perusahaan. Pilih ini jika klaim tidak bisa dilakukan (barang memang sudah rusak dari gudang, force majeure).
                <br>✅ <strong>Mark as Resolved:</strong> Tandai sudah ditangani secara offline/manual di luar sistem. Pilih ini jika tindak lanjut sudah dilakukan di tempat.
            </span>
        </div></div>
        <div class="step-item"><span class="step-num">3</span><div><strong>Isi catatan tambahan (opsional)</strong><span>Contoh: "Sisa 2 item akan dikirim tanggal 25 Maret via Blindvan B1234RKM"</span></div></div>
        <div class="step-item"><span class="step-num">4</span><div><strong>Klik "Konfirmasi Resolve"</strong><span>Exception hilang dari panel. Badge merah di sidebar berkurang. Catatan resolusi tersimpan di remarks item terkait. DN status berubah ke "resolved" di database.</span></div></div>
        <div class="gn green"><strong>✅ Setelah resolve:</strong> Exception tidak lagi tampil di panel dan badge sidebar. Data resolusi tetap tersimpan di database untuk keperluan audit dan laporan.</div>
    </div>
</div>

<!-- ══════════ TAB: FLEET ══════════ -->
<div class="guide-section" id="gs-fleet">
    <div class="data-card p-4 mb-3">
        <h6 class="fw-bold mb-3">Tabel Fleet — Penjelasan Kolom</h6>
        <table class="info-tbl">
            <thead><tr><th>Kolom</th><th>Penjelasan</th></tr></thead>
            <tbody>
                <tr><td>Plate</td><td>Nomor polisi kendaraan — unik, tidak bisa duplikat</td></tr>
                <tr><td>Type</td><td>Jenis kendaraan: Blindvan (kapasitas kecil), CDE Box (engkel), CDD Box (double), Wingbox, Tronton</td></tr>
                <tr><td>Vendor</td><td>Pemilik kendaraan: <span class="btn-doc outline">Internal</span> = milik perusahaan, <span class="btn-doc yellow">3PL</span> = vendor/transporter luar</td></tr>
                <tr><td>Capacity</td><td>Kapasitas muatan dalam kg — diisi saat tambah kendaraan</td></tr>
                <tr><td>STNK / KIR</td><td>Tanggal expired dokumen. Merah + ⚠️ jika kurang dari 30 hari — perlu segera diperpanjang</td></tr>
                <tr><td>Status</td><td>Available (bisa di-dispatch), Busy (sedang dipakai), Maintenance (dalam servis)</td></tr>
                <tr><td>Action (dropdown)</td><td>Ubah status kendaraan langsung dari tabel — pilih status baru dan otomatis tersimpan</td></tr>
            </tbody>
        </table>
    </div>
    <div class="data-card p-4 mb-3">
        <h6 class="fw-bold mb-3">KPI Cards Fleet</h6>
        <table class="info-tbl">
            <thead><tr><th>KPI</th><th>Artinya</th></tr></thead>
            <tbody>
                <tr><td>Available</td><td>Kendaraan siap dipakai untuk pengiriman baru</td></tr>
                <tr><td>Busy / On Trip</td><td>Kendaraan sedang dalam pengiriman aktif</td></tr>
                <tr><td>Maintenance</td><td>Kendaraan sedang servis/perbaikan, tidak bisa digunakan</td></tr>
            </tbody>
        </table>
    </div>
    <div class="data-card p-4">
        <h6 class="fw-bold mb-3">Form Tambah Armada</h6>
        <table class="info-tbl">
            <thead><tr><th>Field</th><th>Penjelasan</th></tr></thead>
            <tbody>
                <tr><td>Nomor Polisi *</td><td>Wajib unik. Format: B 1234 XYZ</td></tr>
                <tr><td>Tipe Kendaraan</td><td>Pilih sesuai jenis armada</td></tr>
                <tr><td>Kapasitas (kg)</td><td>Kapasitas maksimal muatan — informatif, tidak ada validasi otomatis</td></tr>
                <tr><td>Vendor / Pemilik</td><td>Pilih Internal Fleet atau 3PL (vendor luar). Berpengaruh ke tarif billing</td></tr>
                <tr><td>STNK Exp. / KIR Exp.</td><td>Tanggal expired dokumen kendaraan — untuk monitoring perpanjangan</td></tr>
            </tbody>
        </table>
        <div class="gn blue">Status awal kendaraan baru selalu "Available" secara otomatis.</div>
    </div>
</div>

<!-- ══════════ TAB: DRIVERS ══════════ -->
<div class="guide-section" id="gs-drivers">
    <div class="data-card p-4 mb-3">
        <h6 class="fw-bold mb-3">Tabel Driver — Penjelasan Kolom</h6>
        <table class="info-tbl">
            <thead><tr><th>Kolom</th><th>Penjelasan</th></tr></thead>
            <tbody>
                <tr><td>Nama</td><td>Nama lengkap driver dengan foto avatar otomatis (generated dari nama)</td></tr>
                <tr><td>No. HP</td><td>Nomor WhatsApp driver — untuk koordinasi pengiriman</td></tr>
                <tr><td>SIM</td><td>Jenis SIM: B1 Polos, B1 Umum, atau B2 Umum</td></tr>
                <tr><td>No. SIM</td><td>Nomor fisik SIM driver — untuk keperluan dokumen</td></tr>
                <tr><td>Login</td><td>Username yang otomatis dibuat saat registrasi driver. Password default: <strong>driver123</strong></td></tr>
                <tr><td>Status Akun</td><td>Active = bisa login ke sistem driver (untuk update status shipment)</td></tr>
                <tr><td>Shipment Aktif</td><td>No. shipment yang sedang dijalankan driver. "Tidak ada" jika driver sedang free</td></tr>
            </tbody>
        </table>
    </div>
    <div class="data-card p-4">
        <h6 class="fw-bold mb-3">Form Registrasi Driver</h6>
        <table class="info-tbl">
            <thead><tr><th>Field</th><th>Penjelasan</th></tr></thead>
            <tbody>
                <tr><td>Nama Lengkap *</td><td>Nama resmi driver sesuai KTP</td></tr>
                <tr><td>No. HP *</td><td>Nomor WhatsApp aktif</td></tr>
                <tr><td>Jenis SIM</td><td>B1 Polos (CDD ke bawah), B1 Umum (untuk sewa), B2 Umum (tronton)</td></tr>
                <tr><td>No. SIM</td><td>Nomor fisik SIM — opsional tapi direkomendasikan untuk kelengkapan data</td></tr>
            </tbody>
        </table>
        <div class="gn yellow"><strong>⚠️ Otomatis dibuat:</strong> Setiap driver baru otomatis mendapat akun login dengan username dari nama + 2 angka random, password default: <strong>driver123</strong>. Informasikan ke driver untuk mengganti password setelah login pertama.</div>
    </div>
</div>

<!-- ══════════ TAB: BILLING ══════════ -->
<div class="guide-section" id="gs-billing">
    <div class="data-card p-4 mb-3">
        <h6 class="fw-bold mb-3">Tabel Billing — Penjelasan Kolom</h6>
        <table class="info-tbl">
            <thead><tr><th>Kolom</th><th>Penjelasan</th></tr></thead>
            <tbody>
                <tr><td>Shipment No</td><td>Nomor shipment yang terkait</td></tr>
                <tr><td>Order Ref</td><td>Nomor order yang dikirim dalam shipment ini</td></tr>
                <tr><td>Armada</td><td>Plat kendaraan + badge Internal/3PL — menentukan tarif yang dipakai</td></tr>
                <tr><td>Driver</td><td>Nama driver yang bertugas</td></tr>
                <tr><td>Berat (kg)</td><td>Total berat muatan dari data order</td></tr>
                <tr><td>Status</td><td>Status shipment saat ini</td></tr>
                <tr><td>Biaya</td><td>Angka tebal hijau = sudah diset. Angka abu-abu miring = estimasi otomatis (belum dikonfirmasi)</td></tr>
                <tr><td>Action</td><td><span class="btn-doc yellow">Set Biaya</span> untuk mengisi biaya. <span class="btn-doc green">Settled</span> jika sudah selesai</td></tr>
            </tbody>
        </table>
    </div>
    <div class="data-card p-4 mb-3">
        <h6 class="fw-bold mb-3"><i class="fa fa-calculator me-2 text-warning"></i>Tarif & Formula Kalkulasi</h6>
        <table class="info-tbl">
            <thead><tr><th>Komponen</th><th>Nilai</th><th>Keterangan</th></tr></thead>
            <tbody>
                <tr><td>Tarif Internal Fleet</td><td>Rp 1.500/kg</td><td>Berlaku untuk kendaraan milik perusahaan (vendor Internal)</td></tr>
                <tr><td>Tarif 3PL</td><td>Rp 2.500/kg</td><td>Berlaku untuk kendaraan vendor eksternal (3PL)</td></tr>
                <tr><td>Minimum Charge</td><td>Rp 50.000</td><td>Biaya minimal per shipment, berlaku jika hasil kalkulasi di bawah ini</td></tr>
                <tr><td>Formula</td><td colspan="2"><code>MAX(Rp 50.000, Berat kg × Rate/kg)</code></td></tr>
            </tbody>
        </table>
    </div>
    <div class="data-card p-4">
        <h6 class="fw-bold mb-3">Cara Set Biaya Shipment</h6>
        <div class="step-item"><span class="step-num">1</span><div><strong>Klik <span class="btn-doc yellow">Set Biaya</span></strong><span>Modal Set Biaya muncul. Sudah terisi berat dari data order dan estimasi otomatis berdasarkan tarif vendor.</span></div></div>
        <div class="step-item"><span class="step-num">A</span><div><strong>Opsi Auto Kalkulasi (panel kuning)</strong><span>Pastikan berat sudah benar. Klik "Auto Kalkulasi" — sistem menghitung dan menyimpan estimasi. Estimasi langsung update saat angka berat diubah.</span></div></div>
        <div class="step-item"><span class="step-num">B</span><div><strong>Opsi Input Manual (form putih)</strong><span>Isi biaya aktual di field "Biaya Manual (Rp)" lalu klik "Simpan & Selesaikan". Status shipment otomatis berubah ke "Completed" dan kendaraan kembali Available.</span></div></div>
        <div class="gn blue">Setelah klik "Simpan & Selesaikan", tombol action berubah menjadi badge <span class="btn-doc green">Settled</span> dan tidak bisa diubah lagi.</div>
    </div>
</div>

<!-- ══════════ TAB: STATUS FLOW ══════════ -->
<div class="guide-section" id="gs-status">
    <div class="data-card p-4 mb-3">
        <h6 class="fw-bold mb-3"><i class="fa fa-diagram-project me-2 text-warning"></i>Status Shipment — Lifecycle Lengkap</h6>
        <table class="info-tbl">
            <thead><tr><th>Status</th><th>Artinya</th><th>Trigger</th><th>Efek Lain</th></tr></thead>
            <tbody>
                <tr><td>Planned</td><td>Shipment dibuat, driver & kendaraan di-assign</td><td>Otomatis saat klik Dispatch di Orders</td><td>Kendaraan jadi "Busy"</td></tr>
                <tr><td>In Transit</td><td>Kendaraan sedang dalam perjalanan menuju tujuan</td><td>Manual via tombol "Update Status"</td><td>Garis rute di map berubah warna</td></tr>
                <tr><td>Arrived</td><td>Kendaraan sudah tiba di lokasi tujuan</td><td>Manual via tombol "Update Status"</td><td>—</td></tr>
                <tr><td>Completed</td><td>Pengiriman selesai penuh</td><td>Manual atau otomatis saat billing di-settle</td><td>Kendaraan kembali "Available"</td></tr>
                <tr><td>Failed</td><td>Pengiriman gagal (kecelakaan, bencana, dll)</td><td>Manual via tombol "Update Status"</td><td>Kendaraan kembali "Available"</td></tr>
                <tr><td>Cancelled</td><td>Pengiriman dibatalkan sebelum berangkat</td><td>Manual via tombol "Update Status"</td><td>Kendaraan kembali "Available"</td></tr>
            </tbody>
        </table>
    </div>
    <div class="data-card p-4 mb-3">
        <h6 class="fw-bold mb-3">Status Delivery Note (DN)</h6>
        <table class="info-tbl">
            <thead><tr><th>Status</th><th>Artinya</th><th>Trigger</th><th>Tombol Tersedia</th></tr></thead>
            <tbody>
                <tr><td>Draft (Warehouse)</td><td>DN baru dibuat dari NAV, menunggu release</td><td>Otomatis saat Pull NAV Data</td><td><span class="btn-doc yellow">Approve & Release</span></td></tr>
                <tr><td>In Transit (On The Way)</td><td>Barang keluar gudang, dalam perjalanan</td><td>Setelah tanda tangan pengirim</td><td><span class="btn-doc green">Receive</span></td></tr>
                <tr><td>Delivered</td><td>Barang diterima di tujuan, POD lengkap</td><td>Setelah tanda tangan penerima</td><td><span class="btn-doc outline">Cetak SJ</span></td></tr>
                <tr><td>Resolved</td><td>Exception sudah ditangani — tidak muncul di panel exception</td><td>Setelah klik Resolve di panel exception</td><td>—</td></tr>
            </tbody>
        </table>
    </div>
    <div class="data-card p-4">
        <h6 class="fw-bold mb-3">Status Order</h6>
        <table class="info-tbl">
            <thead><tr><th>Status</th><th>Artinya</th></tr></thead>
            <tbody>
                <tr><td>New</td><td>Order baru dibuat, belum di-dispatch</td></tr>
                <tr><td>Planned</td><td>Sudah di-dispatch, shipment sudah dibuat</td></tr>
                <tr><td>Completed</td><td>Pengiriman selesai</td></tr>
            </tbody>
        </table>
    </div>
</div>
