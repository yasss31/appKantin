<?php
// Cegah cache: info server harus selalu berasal dari request yang baru
if (!headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

// Info server untuk ditampilkan di header
if (!function_exists('ambil_ip_server')) {
    function ambil_ip_server(string $hostname): array
    {
        $ips = [];
        $last = null;
        // Baca alamat IPv4 lokal container (lebih andal daripada gethostbyname di Docker/Swarm)
        foreach (@file('/proc/net/fib_trie') ?: [] as $line) {
            if (preg_match('/\|--\s+(\d+\.\d+\.\d+\.\d+)/', $line, $m)) {
                $last = $m[1];
            } elseif ($last !== null && strpos($line, '/32 host LOCAL') !== false) {
                if (strpos($last, '127.') !== 0) {
                    $ips[$last] = $last;
                }
            }
        }
        // Cadangan: gethostbyname bila /proc tidak tersedia
        if (!$ips) {
            $ip = gethostbyname($hostname);
            if ($ip !== $hostname) {
                $ips[$ip] = $ip;
            }
        }
        return array_values($ips);
    }
}

$info_hostname  = gethostname() ?: '-';
$info_ip_server = implode(', ', ambil_ip_server($info_hostname)) ?: '-';
$info_ip_client = ($_SERVER['REMOTE_ADDR'] ?? '-') . ':' . ($_SERVER['REMOTE_PORT'] ?? '-');
$info_waktu     = date('H:i:s');
// Warna badge hostname berbeda per replika (turunan dari hostname)
$info_warna     = 'hsl(' . (hexdec(substr(md5($info_hostname), 0, 6)) % 360) . ', 55%, 32%)';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplikasi Kantin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .info-server {
            background-color: #922b21;
            color: #f5d5d0;
            font-size: .78rem;
            line-height: 1.3;
        }
        .info-server .container {
            display: flex;
            flex-wrap: wrap;
            gap: .25rem 1.5rem;
            padding-top: .35rem;
            padding-bottom: .35rem;
        }
        .info-server .item { white-space: nowrap; }
        .info-server .label { opacity: .75; margin-right: .35rem; }
        .info-server .nilai {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-weight: 600;
            color: #fff;
            background: rgba(0, 0, 0, .22);
            border-radius: 4px;
            padding: .05rem .4rem;
        }
        @media (max-width: 575.98px) {
            .info-server { font-size: .72rem; }
            .info-server .container { gap: .2rem 1rem; }
        }
    </style>
</head>
<body>
<div class="info-server">
    <div class="container">
        <span class="item"><span class="label">Hostname</span><span class="nilai" style="background:<?= htmlspecialchars($info_warna) ?>"><?= htmlspecialchars($info_hostname) ?></span></span>
        <span class="item"><span class="label">IP server</span><span class="nilai"><?= htmlspecialchars($info_ip_server) ?></span></span>
        <span class="item"><span class="label">IP client</span><span class="nilai"><?= htmlspecialchars($info_ip_client) ?></span></span>
        <span class="item"><span class="label">Waktu</span><span class="nilai"><?= htmlspecialchars($info_waktu) ?></span></span>
    </div>
</div>
<nav class="navbar navbar-dark navbar-expand-lg mb-4" style="background-color:#c0392b;">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">🍽️ Aplikasi Kantin</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Menu</a></li>
                <li class="nav-item"><a class="nav-link" href="meja.php">Meja</a></li>
                <li class="nav-item"><a class="nav-link" href="pesan.php">Pesan (Pramusaji)</a></li>
                <li class="nav-item"><a class="nav-link" href="pesanan.php">Daftar Pesanan</a></li>
                <li class="nav-item"><a class="nav-link fw-bold" href="dapur.php">👨‍🍳 Dapur</a></li>
            </ul>
        </div>
    </div>
</nav>
<div class="container pb-5">