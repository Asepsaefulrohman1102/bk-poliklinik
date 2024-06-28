<?php
include_once("../../../config/conn.php");
session_start();

// Handle AJAX request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id']) && isset($_POST['status']) && isset($_POST['id_dokter'])) {
    $id = $_POST['id'];
    $status = $_POST['status'];
    $id_dokter = $_POST['id_dokter'];

    // Ubah status pada jadwal yang lain menjadi 'T' jika status baru adalah 'Y' dan sesuai id_dokter
    if ($status == 'Y') {
        $stmt = $pdo->prepare("UPDATE jadwal_periksa SET aktif = 'T' WHERE aktif = 'Y' AND id_dokter = ?");
        $stmt->execute([$id_dokter]);
    }

    // Update status jadwal yang dipilih sesuai dengan id_dokter
    $stmt = $pdo->prepare("UPDATE jadwal_periksa SET aktif = ? WHERE id = ? AND id_dokter = ?");
    $stmt->execute([$status, $id, $id_dokter]);

    if ($stmt->rowCount()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit; // End the script after handling AJAX request
}



if (isset($_SESSION['login'])) {
  $_SESSION['login'] = true;
} else {
  echo "<meta http-equiv='refresh' content='0; url=..'>";
  die();
}

$nama = $_SESSION['username'];
$akses = $_SESSION['akses'];
$id = $_SESSION['id'];

if ($akses != 'dokter') {
  echo "<meta http-equiv='refresh' content='0; url=..'>";
  die();
}
?>

<?php
$title = 'Poliklinik | Jadwal Periksa';
// Breadcrumb section
ob_start(); ?>
<ol class="breadcrumb float-sm-right">
  <li class="breadcrumb-item"><a href="<?= $base_dokter; ?>">Home</a></li>
  <li class="breadcrumb-item active">Jadwal Periksa</li>
</ol>
<?php
$breadcrumb = ob_get_clean();
ob_flush();

// Title Section
ob_start(); ?>
Jadwal Periksa
<?php
$main_title = ob_get_clean();
ob_flush();

// Content section
ob_start();
?>
<div class="card">
  <div class="card-header">
    <div class="row">
      <div class="col-6">
        <h3 class="card-title">Daftar Jadwal Periksa</h3>
      </div>
      <div class="col-6">
        <a href="create.php" class="btn btn-primary btn-sm float-right"><i class="fa fa-plus"></i> Tambah Jadwal Periksa</a>
      </div>
    </div>
  </div>
  <div class="card-body">
    <table id="tabel-jadwal-periksa" class="table table-bordered table-striped">
      <thead>
        <tr>
          <th>No</th>
          <th>Nama Dokter</th>
          <th>Hari</th>
          <th>Jam Mulai</th>
          <th>Jam Selesai</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $no = 1;
        $data = $pdo->prepare("SELECT 
                        d.nama AS nama_dokter, 
                        p.id AS id,
                        p.hari AS hari,
                        p.jam_mulai AS jam_mulai,
                        p.jam_selesai AS jam_selesai,
                        p.aktif AS aktif,
                        p.id_dokter AS id_dokter -- Pastikan kolom ini ditambahkan
                        FROM jadwal_periksa p 
                        INNER JOIN dokter d ON p.id_dokter = d.id
                        WHERE d.id = :id_dokter");
        $data->execute(['id_dokter' => $id]);

        if ($data->rowCount() == 0) {
          echo "<tr><td colspan='7' align='center'>Tidak ada data</td></tr>";
        } else {
          while ($d = $data->fetch()) {
        ?>
            <tr>
              <td><?= $no++; ?></td>
              <td><?= $d['nama_dokter'] ?></td>
              <td><?= $d['hari'] ?></td>
              <td><?= $d['jam_mulai'] ?></td>
              <td><?= $d['jam_selesai'] ?></td>
              <!-- aksi diubah menjadi button -->
              <td>
                <button class="btn btn-status btn-sm" data-id="<?= $d['id'] ?>" data-status="<?= $d['aktif'] ?>" data-id-dokter="<?= $d['id_dokter'] ?>">
                  <?= $d['aktif'] == 'Y' ? 'Aktif' : 'Tidak Aktif' ?>
                </button>
              </td>
              
              <td>
                <a href="edit.php/<?= $d['id'] ?>" class="btn btn-primary btn-sm"><i class="fa fa-edit"></i> Edit</a>
              </td>

            </tr>
        <?php
          }
        }
        ?>
      </tbody>
    </table>
  </div>
</div>
<?php
$content = ob_get_clean();
ob_flush();

// JS Section
ob_start(); ?>
<script>
  $(document).ready(function() {
    $('.delete-button').on('click', function(e) {
      return confirm('Apakah anda yakin ingin menghapus data ini?');
    });
  });
</script>

<!-- change -->
<script>
  $(document).ready(function() {
    $('.btn-status').on('click', function(e) {
      e.preventDefault();
      let button = $(this);
      let id = button.data('id');
      let currentStatus = button.data('status');
      let newStatus = currentStatus === 'Y' ? 'T' : 'Y';
      let idDokter = button.data('id-dokter'); // Ambil id_dokter dari tombol

      $.ajax({
        url: '', // The current file will handle the request
        type: 'POST',
        data: {
          id: id,
          status: newStatus,
          id_dokter: idDokter // Kirim id_dokter bersama data lainnya
        },
        success: function(response) {
          let result = JSON.parse(response);
          if (result.success) {
            if (newStatus === 'Y') {
              // Set all other buttons with the same id_dokter to 'Tidak Aktif'
              $('.btn-status').each(function() {
                let otherButton = $(this);
                if (otherButton.data('id-dokter') === idDokter && otherButton.data('id') !== id) {
                  otherButton.text('Tidak Aktif');
                  otherButton.data('status', 'T');
                }
              });
            }
            // Update the clicked button
            button.text(newStatus === 'Y' ? 'Aktif' : 'Tidak Aktif');
            button.data('status', newStatus);
          } else {
            alert('Gagal mengubah status');
          }
        },
        error: function() {
          alert('Terjadi kesalahan pada server.');
        }
      });
    });
  });
</script>



<?php
$js = ob_get_clean();
ob_flush();
?>

<?php include_once("../../../layouts/index.php"); ?>