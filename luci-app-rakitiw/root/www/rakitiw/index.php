<?php
// Lokasi file bash
$bash_file = '/usr/bin/rakitanmanager.sh';

// Baca file bash
$bash_content = file_get_contents($bash_file);

// Ekstrak variabel dari file bash
preg_match_all('/(\w+)="(.*)"/', $bash_content, $matches);

// Buat array untuk menyimpan variabel
$variables = array();
for ($i = 0; $i < count($matches[1]); $i++) {
    if ($matches[1][$i] !== 'connect') {
        $variables[$matches[1][$i]] = $matches[2][$i];
    }
}
// Fungsi untuk membaca data modem dari file JSON
function bacaDataModem()
{
    $file = 'data_modem.json';
    if (file_exists($file)) {
        $data = file_get_contents($file);
        $decoded_data = json_decode($data, true);
        if (isset($decoded_data['modems'])) {
            return $decoded_data['modems'];
        }
    }
    return [];
}

// Fungsi untuk menyimpan data modem ke file JSON
function simpanDataModem($modems)
{
    $file = 'data_modem.json';
    $data = json_encode(['modems' => $modems], JSON_PRETTY_PRINT);
    file_put_contents($file, $data);
}

// Periksa apakah ada pengiriman formulir tambah modem
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["tambah_modem"])) {
    $modems = bacaDataModem();
    $modems[] = [
        "jenis" => $_POST["jenis"],
        "nama" => $_POST["nama"],
        "apn" => $_POST["apn"],
        "interface" => $_POST["interface"],
        "portat" => $_POST["portat"],
        "iporbit" => $_POST["iporbit"],
        "usernameorbit" => $_POST["usernameorbit"],
        "passwordorbit" => $_POST["passwordorbit"],
        "hostbug" => $_POST["hostbug"],
        "devicemodem" => $_POST["devicemodem"],
        "delayping" => $_POST["delayping"]
    ];
    simpanDataModem($modems);
}

// Periksa apakah ada pengiriman formulir edit modem
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["edit_modem"])) {
    $index = $_POST["index"];
    $modems = bacaDataModem();
    if (isset($modems[$index])) {
        $modems[$index]["nama"] = $_POST["edit_nama"];
        $modems[$index]["apn"] = $_POST["edit_apn"];
        $modems[$index]["interface"] = $_POST["edit_interface"];
        $modems[$index]["portat"] = $_POST["edit_portat"];
        $modems[$index]["iporbit"] = $_POST["edit_iporbit"];
        $modems[$index]["usernameorbit"] = $_POST["edit_usernameorbit"];
        $modems[$index]["passwordorbit"] = $_POST["edit_passwordorbit"];
        $modems[$index]["hostbug"] = $_POST["edit_hostbug"];
        $modems[$index]["devicemodem"] = $_POST["edit_devicemodem"];
        $modems[$index]["delayping"] = $_POST["edit_delayping"];
        simpanDataModem($modems);
    }
}

// Periksa apakah ada permintaan penghapusan modem
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["hapus_modem"])) {
    $index = $_GET["hapus_modem"];
    $modems = bacaDataModem();
    if (isset($modems[$index])) {
        unset($modems[$index]);
        simpanDataModem($modems);
    }
}

// Baca data modem
$modems = bacaDataModem();
$modem_count = count($modems); // Hitung jumlah modem yang tersimpan

// Periksa apakah ada modem yang tersimpan
if ($modem_count == 0) {
    $start_button_disabled = 'disabled'; // Nonaktifkan tombol jika tidak ada modem yang tersimpan
} else {
    $start_button_disabled = ''; // Aktifkan tombol jika ada modem yang tersimpan
}

if (isset($_POST['enable'])) {
    $log_message = shell_exec("date '+%Y-%m-%d %H:%M:%S'") . " - Script Telah Di Aktifkan\n";
    file_put_contents('/var/log/rakitanmanager.log', $log_message, FILE_APPEND);
    $variables['modem_status'] = 'Enabled';
    shell_exec('/usr/bin/rakitanmanager.sh -s');
    $updated_content = $bash_content;
    foreach ($variables as $key => $value) {
        $updated_content = preg_replace('/' . $key . '=".*"/', $key . '="' . $value . '"', $updated_content);
    }
    file_put_contents($bash_file, $updated_content);
} elseif (isset($_POST['disable'])) {
    exec('killall -9 rakitanmanager.sh');
    exec('rm /var/log/rakitanmanager.log');
    $log_message = shell_exec("date '+%Y-%m-%d %H:%M:%S'") . " - Script Telah Di Nonaktifkan\n";
    file_put_contents('/var/log/rakitanmanager.log', $log_message, FILE_APPEND);
    $variables['modem_status'] = 'Disabled';
    // Update variabel dalam file bash
    $updated_content = $bash_content;
    foreach ($variables as $key => $value) {
        $updated_content = preg_replace('/' . $key . '=".*"/', $key . '="' . $value . '"', $updated_content);
    }
    file_put_contents($bash_file, $updated_content);
}



$contnetwork = file_get_contents('/etc/config/network'); // Membaca isi file
$linesnetwork = explode("\n", $contnetwork); // Memisahkan setiap baris

$interface_modem = [];
foreach ($linesnetwork as $linenetwork) {
    if (strpos($linenetwork, 'config interface') !== false) {
        // Menemukan baris yang berisi 'config interface'
        $parts = explode(' ', $linenetwork);
        $interface = trim(end($parts), "'"); // Menghapus tanda petik
        $interface_modem[] = $interface; // Menambahkan nama interface ke array
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Modem</title>
    <?php
    include ("head.php");
    exec('chmod -R 755 /usr/bin/rakitanmanager.sh');
    exec('chmod -R 755 /usr/bin/modem-orbit.py');
    ?>
    <script src="lib/vendor/jquery/jquery-3.6.0.slim.min.js"></script>
    <script>
        $(document).ready(function () {
            var previousContent = "";
            setInterval(function () {
                $.get("log.php", function (data) {
                    // Jika konten berubah, lakukan update dan scroll
                    if (data !== previousContent) {
                        previousContent = data;
                        $("#logContent").html(data);
                        var elem = document.getElementById('logContent');
                        elem.scrollTop = elem.scrollHeight;
                    }
                });
            }, 1000);
        });
    </script>
</head>

<body>
    <div id="app">
        <?php include ('navbar.php'); ?>
        <form id="myForm" method="POST" class="mt-5">
            <div class="container-fluid">
                <div class="row py-2">
                    <div class="col-lg-8 col-md-9 mx-auto mt-3">
                        <div class="card">
                            <div class="card-header">
                                <div class="text-center">
                                    <h4><i class="fa fa-home"></i> RAKITAN MANAGER</h4>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="card-body py-0 px-0">
                                    <div class="body">
                                        <div class="text-center">
                                            <img src="curent.svg" alt="Curent Version">
                                            <img alt="Latest Version"
                                                src="https://img.shields.io/github/v/release/rtaserver/luci-app-rakitiw?display_name=tag&logo=openwrt&label=Latest%20Version&color=dark-green">
                                        </div>
                                        <br>
                                    </div>
                                    <div class="container-fluid">
                                        <div class="container mt-5">

                                            <div class="container">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <button type="button" class="btn btn-primary btn-block mb-3"
                                                            data-toggle="modal" data-target="#tambahModemModal" <?php if ($variables['modem_status'] == 'Enabled')
                                                                echo 'disabled'; ?>>Tambah Modem</button>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <form method="POST">
                                                            <?php if ($variables['modem_status'] == 'Enabled'): ?>
                                                                <button type="submit" class="btn btn-danger btn-block mb-3"
                                                                    name="disable">Stop Modem</button>
                                                            <?php else: ?>
                                                                <button type="submit" class="btn btn-success btn-block mb-3"
                                                                    name="enable" <?php echo $start_button_disabled; ?>>Start Modem</button>
                                                            <?php endif; ?>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th scope="col">Nama</th>
                                                        <th scope="col">Jenis Modem</th>
                                                        <th scope="col">Host</th>
                                                        <th scope="col">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($modems as $index => $modem): ?>
                                                        <tr>
                                                            <td><?= $modem["nama"] ?></td>
                                                            <td><?= $modem["jenis"] ?></td>
                                                            <td><?= $modem["hostbug"] ?></td>
                                                            <td>
                                                                <button type="button" class="btn btn-primary btn-sm"
                                                                    onclick="editModem(<?= $index ?>)" <?php if ($variables['modem_status'] == 'Enabled')
                                                                          echo 'disabled'; ?>>Edit</button>
                                                                <button type="button" class="btn btn-danger btn-sm"
                                                                    onclick="hapusModem(<?= $index ?>)" <?php if ($variables['modem_status'] == 'Enabled')
                                                                          echo 'disabled'; ?>>Hapus</button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                            <form method="POST" class="mt-5">
                                                <div class="row">
                                                    <div class="col pt-2">
                                                        <pre id="logContent" class="form-control text-left"
                                                            style="height: 200px; width: auto; font-size:80%; background-image-position: center; background-color: #f8f9fa "></pre>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>

                                        <!-- Modal Tambah Modem -->
                                        <div class="modal fade" id="tambahModemModal" tabindex="-1"
                                            aria-labelledby="tambahModemModalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="tambahModemModalLabel">Tambah Modem
                                                        </h5>
                                                        <button type="button" class="close" data-dismiss="modal"
                                                            aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <form id="tambahModemForm" onsubmit="return validateFormTambah()"
                                                        method="post">
                                                        <div class="modal-body">
                                                            <div class="form-group">
                                                                <label for="jenis">Jenis Modem:</label><br>
                                                                <div class="form-check form-check-inline">
                                                                    <input class="form-check-input" type="radio"
                                                                        name="jenis" id="rakitan" value="rakitan"
                                                                        checked>
                                                                    <label class="form-check-label" for="rakitan">Modem
                                                                        Rakitan</label>
                                                                </div>
                                                                <div class="form-check form-check-inline">
                                                                    <input class="form-check-input" type="radio"
                                                                        name="jenis" id="hp" value="hp">
                                                                    <label class="form-check-label" for="hp">Modem
                                                                        HP</label>
                                                                </div>
                                                                <div class="form-check form-check-inline">
                                                                    <input class="form-check-input" type="radio"
                                                                        name="jenis" id="orbit" value="orbit">
                                                                    <label class="form-check-label" for="orbit">Modem
                                                                        Orbit</label>
                                                                </div>
                                                            </div>
                                                            <div class="form-group">
                                                                <label for="nama">Nama Modem:</label>
                                                                <input type="text" id="nama" name="nama"
                                                                    class="form-control" placeholder="Nama Bebas">
                                                            </div>
                                                            <div class="form-group" id="rakitan_field">
                                                                <label for="apn">APN:</label>
                                                                <input type="text" id="apn" name="apn"
                                                                    class="form-control" placeholder="internet">
                                                                <label for="interface">Interface Modem:</label>
                                                                <select name="interface" id="interface"
                                                                    class="form-control">
                                                                    <?php
                                                                    foreach ($interface_modem as $interface) {
                                                                        echo "<option value=\"$interface\"";
                                                                        echo ">$interface</option>";
                                                                    }
                                                                    ?>
                                                                </select>
                                                                <label for="portat">Port Modem AT Command:</label>
                                                                <input type="text" id="portat" name="portat"
                                                                    class="form-control" placeholder="/dev/ttyUSB0">
                                                            </div>
                                                            <div class="form-group" id="orbit_field">
                                                                <label for="iporbit">IP Modem:</label>
                                                                <input type="text" id="iporbit" name="iporbit"
                                                                    class="form-control" placeholder="192.168.8.1">
                                                                <label for="usernameorbit">Username:</label>
                                                                <input type="text" id="usernameorbit"
                                                                    name="usernameorbit" class="form-control"
                                                                    placeholder="admin">
                                                                <label for="passwordorbit">Password:</label>
                                                                <input type="text" id="passwordorbit"
                                                                    name="passwordorbit" class="form-control"
                                                                    placeholder="admin">
                                                            </div>
                                                            <div class="form-group">
                                                                <label for="hostbug">Host / Bug Untuk Ping | Multi
                                                                    Host:</label>
                                                                <input type="text" id="hostbug" name="hostbug"
                                                                    class="form-control"
                                                                    placeholder="1.1.1.1 8.8.8.8 google.com">
                                                                <label for="devicemodem">Device Modem Untuk Cek
                                                                    PING:</label>
                                                                <input type="text" id="devicemodem" name="devicemodem"
                                                                    class="form-control" placeholder="eth1">
                                                                <label for="delayping">Jeda Waktu Detik | Sebelum
                                                                    Melanjutkan Cek PING:</label>
                                                                <input type="text" id="delayping" name="delayping"
                                                                    class="form-control" placeholder="15">
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary"
                                                                data-dismiss="modal">Tutup</button>
                                                            <button type="submit" name="tambah_modem"
                                                                class="btn btn-primary">Simpan</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="modal fade" id="editModemModal" tabindex="-1"
                                            aria-labelledby="editModemModalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="editModemModalLabel">Edit Modem</h5>
                                                        <button type="button" class="close" data-dismiss="modal"
                                                            aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <form id="editModemForm" onsubmit="return validateFormEdit()"
                                                        method="post">
                                                        <div class="modal-body">
                                                            <div class="form-group">
                                                                <label for="edit_jenis">Jenis Modem:</label><br>
                                                                <div class="form-check form-check-inline">
                                                                    <input class="form-check-input" type="radio"
                                                                        name="edit_jenis" id="edit_rakitan"
                                                                        value="rakitan" disabled>
                                                                    <label class="form-check-label"
                                                                        for="edit_rakitan">Modem Rakitan</label>
                                                                </div>
                                                                <div class="form-check form-check-inline">
                                                                    <input class="form-check-input" type="radio"
                                                                        name="edit_jenis" id="edit_hp" value="hp"
                                                                        disabled>
                                                                    <label class="form-check-label" for="edit_hp">Modem
                                                                        HP</label>
                                                                </div>
                                                                <div class="form-check form-check-inline">
                                                                    <input class="form-check-input" type="radio"
                                                                        name="edit_jenis" id="edit_orbit" value="orbit"
                                                                        disabled>
                                                                    <label class="form-check-label"
                                                                        for="edit_orbit">Modem Orbit</label>
                                                                </div>
                                                            </div>
                                                            <div class="form-group">
                                                                <label for="edit_nama">Nama Modem:</label>
                                                                <input type="text" id="edit_nama" name="edit_nama"
                                                                    class="form-control" placeholder="Nama Bebas">
                                                            </div>
                                                            <div class="form-group" id="edit_rakitan_field">
                                                                <label for="edit_apn">APN:</label>
                                                                <input type="text" id="edit_apn" name="edit_apn"
                                                                    class="form-control" placeholder="internet">
                                                                <label for="edit_interface">Interface Modem:</label>
                                                                <select name="edit_interface" id="edit_interface"
                                                                    class="form-control">
                                                                    <?php
                                                                    foreach ($interface_modem as $interface) {
                                                                        echo "<option value=\"$interface\"";
                                                                        echo ">$interface</option>";
                                                                    }
                                                                    ?>
                                                                </select>
                                                                <label for="edit_portat">Port Modem AT Command:</label>
                                                                <input type="text" id="edit_portat" name="edit_portat"
                                                                    class="form-control" placeholder="/dev/ttyUSB0">
                                                            </div>
                                                            <div class="form-group" id="edit_orbit_field">
                                                                <label for="edit_iporbit">IP Modem:</label>
                                                                <input type="text" id="edit_iporbit" name="edit_iporbit"
                                                                    class="form-control" placeholder="192.168.8.1">
                                                                <label for="edit_usernameorbit">Username:</label>
                                                                <input type="text" id="edit_usernameorbit"
                                                                    name="edit_usernameorbit" class="form-control"
                                                                    placeholder="admin">
                                                                <label for="edit_passwordorbit">Password:</label>
                                                                <input type="text" id="edit_passwordorbit"
                                                                    name="edit_passwordorbit" class="form-control"
                                                                    placeholder="admin">
                                                            </div>
                                                            <div class="form-group">
                                                                <label for="edit_hostbug">Host / Bug Untuk Ping | Multi
                                                                    Host:</label>
                                                                <input type="text" id="edit_hostbug" name="edit_hostbug"
                                                                    class="form-control"
                                                                    placeholder="1.1.1.1 8.8.8.8 google.com">
                                                                <label for="edit_devicemodem">Device Modem Untuk Cek
                                                                    PING:</label>
                                                                <input type="text" id="edit_devicemodem"
                                                                    name="edit_devicemodem" class="form-control"
                                                                    placeholder="eth1">
                                                                <label for="edit_delayping">Jeda Waktu Detik | Sebelum
                                                                    Melanjutkan Cek PING:</label>
                                                                <input type="text" id="edit_delayping"
                                                                    name="edit_delayping" class="form-control"
                                                                    placeholder="15">
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary"
                                                                data-dismiss="modal">Tutup</button>
                                                            <button type="submit" name="edit_modem"
                                                                class="btn btn-primary">Simpan</button>
                                                            <input type="hidden" name="index" id="editIndex">
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php include ('footer.php'); ?>
            </div>
        </form>
    </div>
    <?php include ("javascript.php"); ?>
    <script>
        function editModem(index) {
            var modem = <?= json_encode($modems) ?>[index];
            $('#edit_nama').val(modem.nama);
            $('#edit_apn').val(modem.apn);
            $('#edit_interface').val(modem.interface);
            $('#edit_portat').val(modem.portat);
            $('#edit_iporbit').val(modem.iporbit);
            $('#edit_usernameorbit').val(modem.usernameorbit);
            $('#edit_passwordorbit').val(modem.passwordorbit);
            $('#edit_hostbug').val(modem.hostbug);
            $('#edit_devicemodem').val(modem.devicemodem);
            $('#edit_delayping').val(modem.delayping);
            $('input[name="edit_jenis"][value="' + modem.jenis + '"]').prop('checked', true);
            if (modem.jenis === 'rakitan') {
                $('#edit_rakitan_field').show();
                $('#edit_orbit_field').hide();
            } else if (modem.jenis === 'orbit') {
                $('#edit_rakitan_field').hide();
                $('#edit_orbit_field').show();
            } else {
                $('#edit_rakitan_field').hide();
                $('#edit_orbit_field').hide();
            }
            $('#editIndex').val(index);
            $('#editModemModal').modal('show');
        }

        function hapusModem(index) {
            if (confirm('Apakah Anda yakin ingin menghapus modem ini?')) {
                window.location.href = '?hapus_modem=' + index;
            }
        }

        $(document).ready(function () {
            // Sembunyikan bidang non-rakitan dan non-orbit secara default
            $('#rakitan_field, #orbit_field').hide();

            // Tampilkan bidang rakitan saat halaman dimuat karena itu default
            $('#rakitan_field').show();

            $('#rakitan').change(function () {
                if ($(this).is(':checked')) {
                    $('#rakitan_field').show();
                    $('#orbit_field').hide();
                }
            });

            $('#hp').change(function () {
                if ($(this).is(':checked')) {
                    $('#rakitan_field, #orbit_field').hide();
                }
            });

            $('#orbit').change(function () {
                if ($(this).is(':checked')) {
                    $('#orbit_field').show();
                    $('#rakitan_field').hide();
                }
            });

            // Menampilkan bidang sesuai dengan pilihan radio button yang terpilih saat edit
            $('input[name="edit_jenis"]').change(function () {
                if ($(this).val() === 'rakitan') {
                    $('#edit_rakitan_field').show();
                    $('#edit_orbit_field').hide();
                } else if ($(this).val() === 'hp') {
                    $('#edit_rakitan_field, #edit_orbit_field').hide();
                } else if ($(this).val() === 'orbit') {
                    $('#edit_orbit_field').show();
                    $('#edit_rakitan_field').hide();
                }
            });

            // Tambahkan fungsi untuk mengubah status tombol Mulai dan label Status saat diklik
            var statusBerjalan = false;
            $('#mulaiStopButton').click(function () {
                if (!statusBerjalan) {
                    $(this).text('Berhenti').removeClass('btn-primary').addClass('btn-danger');
                    $('.status-label').text('Berjalan').css('color', 'green');
                    // Nonaktifkan semua tombol Edit dan Hapus
                    $('.btn-primary, .btn-danger').prop('disabled', true);
                } else {
                    $(this).text('Mulai').removeClass('btn-danger').addClass('btn-primary');
                    $('.status-label').text('Berhenti').css('color', 'black');
                    // Aktifkan kembali semua tombol Edit dan Hapus
                    $('.btn-primary, .btn-danger').prop('disabled', false);
                }
                statusBerjalan = !statusBerjalan;
            });
        });

        // Function to validate form fields
        function validateFormTambah() {
            var jenis = document.querySelector('input[name="jenis"]:checked');
            var nama = document.getElementById("nama").value.trim();
            var apn = document.getElementById("apn").value.trim();
            var interface = document.getElementById("interface").value.trim();
            var portat = document.getElementById("portat").value.trim();
            var iporbit = document.getElementById("iporbit").value.trim();
            var usernameorbit = document.getElementById("usernameorbit").value.trim();
            var passwordorbit = document.getElementById("passwordorbit").value.trim();
            var hostbug = document.getElementById("hostbug").value.trim();
            var devicemodem = document.getElementById("devicemodem").value.trim();
            var delayping = document.getElementById("delayping").value.trim();

            if (!jenis) {
                alert("Pilih jenis modem!");
                return false;
            }
            if (nama === "") {
                alert("Nama modem harus diisi!");
                return false;
            }
            if (jenis.value === "rakitan" && apn === "") {
                alert("APN harus diisi untuk modem rakitan!");
                return false;
            }
            if (jenis.value === "rakitan" && portat === "") {
                alert("Port modem AT Command harus diisi untuk modem rakitan!");
                return false;
            }
            if (jenis.value === "orbit") {
                if (iporbit === "" || usernameorbit === "" || passwordorbit === "") {
                    alert("Semua bidang IP Modem, Username, dan Password harus diisi untuk modem orbit!");
                    return false;
                }
            }
            if (hostbug === "") {
                alert("Host / Bug untuk ping harus diisi!");
                return false;
            }
            if (devicemodem === "") {
                alert("Device modem untuk cek PING harus diisi!");
                return false;
            }
            if (delayping === "") {
                alert("Jeda waktu detik sebelum melanjutkan cek PING harus diisi!");
                return false;
            }
            return true;
        }
        function validateFormEdit() {
            var jenis = document.querySelector('input[name="edit_jenis"]:checked');
            var nama = document.getElementById("edit_nama").value.trim();
            var apn = document.getElementById("edit_apn").value.trim();
            var interface = document.getElementById("edit_interface").value.trim();
            var portat = document.getElementById("edit_portat").value.trim();
            var iporbit = document.getElementById("edit_iporbit").value.trim();
            var usernameorbit = document.getElementById("edit_usernameorbit").value.trim();
            var passwordorbit = document.getElementById("edit_passwordorbit").value.trim();
            var hostbug = document.getElementById("edit_hostbug").value.trim();
            var devicemodem = document.getElementById("edit_devicemodem").value.trim();
            var delayping = document.getElementById("edit_delayping").value.trim();

            if (!jenis) {
                alert("Pilih jenis modem!");
                return false;
            }
            if (nama === "") {
                alert("Nama modem harus diisi!");
                return false;
            }
            if (jenis.value === "rakitan" && apn === "") {
                alert("APN harus diisi untuk modem rakitan!");
                return false;
            }
            if (jenis.value === "rakitan" && portat === "") {
                alert("Port modem AT Command harus diisi untuk modem rakitan!");
                return false;
            }
            if (jenis.value === "orbit") {
                if (iporbit === "" || usernameorbit === "" || passwordorbit === "") {
                    alert("Semua bidang IP Modem, Username, dan Password harus diisi untuk modem orbit!");
                    return false;
                }
            }
            if (hostbug === "") {
                alert("Host / Bug untuk ping harus diisi!");
                return false;
            }
            if (devicemodem === "") {
                alert("Device modem untuk cek PING harus diisi!");
                return false;
            }
            if (delayping === "") {
                alert("Jeda waktu detik sebelum melanjutkan cek PING harus diisi!");
                return false;
            }
            return true;
        }
    </script>
</body>

</html>