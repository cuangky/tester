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

// Cek apakah form disubmit
if (isset($_POST['save'])) {
    // Update variabel dengan data dari form
    foreach ($_POST as $key => $value) {
        if (array_key_exists($key, $variables)) {
            $variables[$key] = $value;
        }
    }

    // Update file bash dengan variabel baru
    foreach ($variables as $key => $value) {
        if ($key !== 'connect') {
            $bash_content = preg_replace('/' . $key . '=".*"/', $key . '="' . $value . '"', $bash_content);
        }
    }
    file_put_contents($bash_file, $bash_content);

    $selectedModem = $_POST['modemType'];

    // Memperbarui variabel dalam file bash berdasarkan jenis modem yang dipilih
    switch ($selectedModem) {
        case 'rakitan':
            $variables['modem_rakitan'] = 'Enabled';
            $variables['modem_hp'] = 'Disabled';
            $variables['modem_orbit'] = 'Disabled';
            break;
        case 'hp':
            $variables['modem_rakitan'] = 'Disabled';
            $variables['modem_hp'] = 'Enabled';
            $variables['modem_orbit'] = 'Disabled';
            break;
        case 'orbit':
            $variables['modem_rakitan'] = 'Disabled';
            $variables['modem_hp'] = 'Disabled';
            $variables['modem_orbit'] = 'Enabled';
            break;
        default:
            // Default case
            break;
    }
    // Update variabel dalam file bash
    $updated_content = $bash_content;
    foreach ($variables as $key => $value) {
        $updated_content = preg_replace('/' . $key . '=".*"/', $key . '="' . $value . '"', $updated_content);
    }

    file_put_contents($bash_file, $updated_content);
    $log_message = shell_exec("date '+%Y-%m-%d %H:%M:%S'") . " - Script Telah Diperbaharui\n";
    file_put_contents('/var/log/rakitanmanager.log', $log_message, FILE_APPEND);
} elseif (isset($_POST['enable'])) {
    exec('pid=$(pgrep -f rakitanmanager.sh) && kill $pid');
    $log_message = shell_exec("date '+%Y-%m-%d %H:%M:%S'") . " - Script Telah Di Aktifkan\n";
    file_put_contents('/var/log/rakitanmanager.log', $log_message, FILE_APPEND);
    $variables['modem_status'] = 'Enabled';
    shell_exec('/usr/bin/rakitanmanager.sh -s >/dev/null 2>&1 &');
    // Update variabel dalam file bash
    $updated_content = $bash_content;
    foreach ($variables as $key => $value) {
        $updated_content = preg_replace('/' . $key . '=".*"/', $key . '="' . $value . '"', $updated_content);
    }
    file_put_contents($bash_file, $updated_content);
} elseif (isset($_POST['disable'])) {
    // Hentikan proses dengan nama rakitanmanager.sh menggunakan pkill
    exec('pid=$(pgrep -f rakitanmanager.sh) && kill $pid');
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

<!doctype html>
<html lang="en">

<head>
    <?php
    $title = "Home";
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
        <form method="POST" class="mt-5">
            <div class="container-fluid">
                <div class="row py-2">
                    <div class="col-lg-8 col-md-9 mx-auto mt-3">
                        <div class="card">
                            <div class="card-header">
                                <div class="text-center">
                                    <h4><i class="fa fa-home"></i> Modem Rakitan Manager</h4>
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
                                    <div class="row">
                                        <div class="col-lg-6 col-md-6">
                                            <i class="fa fa-inbox"></i>
                                            <?php if ($variables['modem_status'] == 'Enabled'): ?>
                                                <span class="text-primary">Status: </span><span class="text-success">
                                                    <?= $variables['modem_status'] ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-primary">Status: </span><span class="text-danger">
                                                    <?= $variables['modem_status'] ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-lg-6 col-md-6">
                                            <i class="fa fa-server"></i>
                                            <span class="text-primary">IP: {{ wan_ip }}</span>
                                        </div>
                                        <div class="col-lg-6 col-md-6 d-sm-block d-md-block d-lg-none">
                                            <i class="fa fa-globe"></i>
                                            <span class="text-primary">ISP: {{ wan_isp }}</span>
                                        </div>
                                        <div class="col-lg-6 col-md-6 pb-lg-1">
                                            <i class="fa fa-flag-o"></i>
                                            <span class="text-primary">Location : {{ wan_country }}</span>
                                        </div>
                                        <div class="col-lg-6 col-md-6 d-none d-lg-block d-xl-block">
                                            <i class="fa fa-globe"></i>
                                            <span class="text-primary">ISP: {{ wan_isp }}</span>
                                        </div>
                                    </div>
                                    <br>
                                    <div class="card-header">
                                    </div><br>
                                    <div class="card-body py-0 px-0">
                                        <h2 class="text-center">Config Modem</h2>
                                        <form method="POST" class="mt-5">
                                            <div class="row justify-content-center">
                                                <div class="col-auto">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" id="modemRakitan"
                                                            name="modemType" value="rakitan" <?php if ($variables['modem_rakitan'] == 'Enabled')
                                                                echo 'checked'; ?>
                                                            <?php if ($variables['modem_status'] == 'Enabled')
                                                                echo 'disabled'; ?>>
                                                        <label class="form-check-label" for="modemRakitan">Modem
                                                            Rakitan</label>
                                                    </div>
                                                </div>
                                                <div class="col-auto">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" id="modemHP"
                                                            name="modemType" value="hp" <?php if ($variables['modem_hp'] == 'Enabled')
                                                                echo 'checked'; ?> <?php if ($variables['modem_status'] == 'Enabled')
                                                                       echo 'disabled'; ?>>
                                                        <label class="form-check-label" for="modemHP">Modem HP</label>
                                                    </div>
                                                </div>
                                                <div class="col-auto">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" id="modemOrbit"
                                                            name="modemType" value="orbit" <?php if ($variables['modem_orbit'] == 'Enabled')
                                                                echo 'checked'; ?>
                                                            <?php if ($variables['modem_status'] == 'Enabled')
                                                                echo 'disabled'; ?>>
                                                        <label class="form-check-label" for="modemOrbit">Modem
                                                            Orbit</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="modemRakitanFields" style="display:none;">
                                                <div class="form-group">
                                                    <label for="apn">APN Modem Rakitan:</label>
                                                    <input type="text" class="form-control" placeholder="internet"
                                                        id="apn" name="apn" value="<?= $variables['apn'] ?>" required
                                                        <?php if ($variables['modem_status'] == 'Enabled')
                                                            echo 'disabled'; ?>>
                                                </div>
                                                <div class="form-group">
                                                    <label for="host">Host / Bug Untuk Ping | Multi Host:</label>
                                                    <input type="text" class="form-control"
                                                        placeholder="1.1.1.1 8.8.8.8 google.com" id="host" name="host"
                                                        value="<?= $variables['host'] ?>" required <?php if ($variables['modem_status'] == 'Enabled')
                                                              echo 'disabled'; ?>>
                                                </div>
                                                <div class="form-group">
                                                    <label for="interface_modem">Nama Interface Modem:</label>
                                                    <select name="interface_modem" id="interface_modem"
                                                        class="form-control" <?php if ($variables['modem_status'] == 'Enabled')
                                                            echo 'disabled'; ?>>
                                                        <?php
                                                        foreach ($interface_modem as $interface) {
                                                            echo "<option value=\"$interface\"";
                                                            if ($interface == $variables['interface_modem']) {
                                                                echo " selected";
                                                            }
                                                            echo ">$interface</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label for="device_modem">Device Modem Untuk Cek
                                                        PING:</label>
                                                    <input type="text" class="form-control"
                                                        placeholder="wwan0 Atau Di Kosongkan Default" id="device_modem"
                                                        name="device_modem" value="<?= $variables['device_modem'] ?>"
                                                        <?php if ($variables['modem_status'] == 'Enabled')
                                                            echo 'disabled'; ?>>
                                                </div>
                                                <div class="form-group">
                                                    <label for="modem_port">Port Modem AT Command Modem:</label>
                                                    <input type="text" class="form-control" placeholder="/dev/ttyUSB0"
                                                        id="modem_port" name="modem_port"
                                                        value="<?= $variables['modem_port'] ?>" required <?php if ($variables['modem_status'] == 'Enabled')
                                                              echo 'disabled'; ?>>
                                                </div>
                                                <div class="form-group">
                                                    <label for="delay">Jeda Waktu Detik | Untuk Percobaan Reconnect
                                                        Modem:</label>
                                                    <input type="number" class="form-control" placeholder="10"
                                                        id="delay" name="delay" value="<?= $variables['delay'] ?>"
                                                        required <?php if ($variables['modem_status'] == 'Enabled')
                                                            echo 'disabled'; ?>>
                                                </div>
                                            </div>
                                            <div id="modemHPFields" style="display:none;">
                                                <div class="form-group">
                                                    <label for="host">Host / Bug Untuk Ping | Multi Host:</label>
                                                    <input type="text" class="form-control"
                                                        placeholder="1.1.1.1 8.8.8.8 google.com" id="host" name="host"
                                                        value="<?= $variables['host'] ?>" required <?php if ($variables['modem_status'] == 'Enabled')
                                                              echo 'disabled'; ?>>
                                                </div>
                                                <div class="form-group">
                                                    <label for="device_modem">Device Modem Untuk Cek
                                                        PING:</label>
                                                    <input type="text" class="form-control"
                                                        placeholder="tethring1 Atau Di Kosongkan Default" id="device_modem"
                                                        name="device_modem" value="<?= $variables['device_modem'] ?>"
                                                        <?php if ($variables['modem_status'] == 'Enabled')
                                                            echo 'disabled'; ?>>
                                                </div>
                                                <div class="form-group">
                                                    <label for="delay">Jeda Waktu Detik | Untuk Percobaan Reconnect
                                                        Modem:</label>
                                                    <input type="number" class="form-control" placeholder="10"
                                                        id="delay" name="delay" value="<?= $variables['delay'] ?>"
                                                        required <?php if ($variables['modem_status'] == 'Enabled')
                                                            echo 'disabled'; ?>>
                                                </div>
                                            </div>
                                            <div id="modemOrbitFields" style="display:none;">
                                                <div class="form-group">
                                                    <label for="ip_orbit">IP Modem Huawei / Orbit :</label>
                                                    <input type="text" class="form-control" placeholder="192.168.8.1"
                                                        id="ip_orbit" name="ip_orbit"
                                                        value="<?= $variables['ip_orbit'] ?>" required <?php if ($variables['modem_status'] == 'Enabled')
                                                              echo 'disabled'; ?>>
                                                </div>
                                                <div class="form-group">
                                                    <label for="username_orbit">Username :</label>
                                                    <input type="text" class="form-control" placeholder="admin"
                                                        id="username_orbit" name="username_orbit"
                                                        value="<?= $variables['username_orbit'] ?>" required <?php if ($variables['modem_status'] == 'Enabled')
                                                              echo 'disabled'; ?>>
                                                </div>
                                                <div class="form-group">
                                                    <label for="password_orbit">Password :</label>
                                                    <input type="text" class="form-control" placeholder="admin"
                                                        id="password_orbit" name="password_orbit"
                                                        value="<?= $variables['password_orbit'] ?>" required <?php if ($variables['modem_status'] == 'Enabled')
                                                              echo 'disabled'; ?>>
                                                </div>
                                                <div class="form-group">
                                                    <label for="host">Host / Bug Untuk Ping | Multi Host:</label>
                                                    <input type="text" class="form-control"
                                                        placeholder="1.1.1.1 8.8.8.8 google.com" id="host" name="host"
                                                        value="<?= $variables['host'] ?>" required <?php if ($variables['modem_status'] == 'Enabled')
                                                              echo 'disabled'; ?>>
                                                </div>
                                                <div class="form-group">
                                                    <label for="device_modem">Device Modem Untuk Cek
                                                        PING:</label>
                                                    <input type="text" class="form-control"
                                                        placeholder="eth1 Atau Di Kosongkan Default" id="device_modem"
                                                        name="device_modem" value="<?= $variables['device_modem'] ?>"
                                                        <?php if ($variables['modem_status'] == 'Enabled')
                                                            echo 'disabled'; ?>>
                                                </div>
                                                <div class="form-group">
                                                    <label for="delay">Jeda Waktu Detik | Untuk Percobaan Reconnect
                                                        Modem:</label>
                                                    <input type="number" class="form-control" placeholder="10"
                                                        id="delay" name="delay" value="<?= $variables['delay'] ?>"
                                                        required <?php if ($variables['modem_status'] == 'Enabled')
                                                            echo 'disabled'; ?>>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col pt-2">
                                                    <pre id="logContent" class="form-control text-left"
                                                        style="height: 200px; width: auto; font-size:80%; background-image-position: center; background-color: #141d26 "></pre>
                                                </div>
                                            </div>
                                            <br>
                                            <div class="card-header"></div><br>
                                            <td class="d-grid">
                                                <div class="btn-group col" role="group" aria-label="ctrl">
                                                    <button type="submit" class="btn btn-primary" name="save" <?php if ($variables['modem_status'] == 'Enabled')
                                                        echo 'disabled'; ?>>Simpan</button>
                                                    <?php if ($variables['modem_status'] == 'Enabled'): ?>
                                                        <button type="submit" class="btn btn-danger"
                                                            name="disable">Disable</button>
                                                    <?php else: ?>
                                                        <?php exec('pid=$(pgrep -f rakitanmanager.sh) && kill $pid'); ?>
                                                        <button type="submit" class="btn btn-success"
                                                            name="enable">Enable</button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </form>
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
    <script src="js/index.js"></script>
    <script>
        $(document).ready(function () {
            // Menentukan tampilan awal berdasarkan nilai variabel
            if ('<?= $variables['modem_rakitan'] ?>' === 'Enabled') {
                $('#modemRakitanFields').show();
            } else if ('<?= $variables['modem_hp'] ?>' === 'Enabled') {
                $('#modemHPFields').show();
            } else if ('<?= $variables['modem_orbit'] ?>' === 'Enabled') {
                $('#modemOrbitFields').show();
            }

            // Tampilkan field sesuai dengan jenis modem yang dipilih
            $('input[type=radio][name=modemType]').change(function () {
                if (this.value === 'rakitan') {
                    $('#modemRakitanFields').show();
                    $('#modemHPFields').hide();
                    $('#modemOrbitFields').hide();
                } else if (this.value === 'hp') {
                    $('#modemRakitanFields').hide();
                    $('#modemHPFields').show();
                    $('#modemOrbitFields').hide();
                } else if (this.value === 'orbit') {
                    $('#modemRakitanFields').hide();
                    $('#modemHPFields').hide();
                    $('#modemOrbitFields').show();
                }
            });
        });
    </script>
</body>

</html>