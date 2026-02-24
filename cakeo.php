<?php
header('Content-Type: text/html; charset=utf-8');

// API utama daftar pertandingan
$featuredUrl = 'https://api.cakeo.xyz/match/live';
$featuredResponse = file_get_contents($featuredUrl);
$featuredData = json_decode($featuredResponse, true);

$noSignalUrl = "https://raw.githubusercontent.com/akkradet/IPTV-THAI/refs/heads/master/nosignal.m3u8";

if ($featuredData['status'] == 1) {
    echo "#EXTM3U\n\n";

    $found = false;

    foreach ($featuredData['data'] as $match) {
        $status = (int)$match['status'];

        // hanya tampilkan pertandingan LIVE
        if ($status === 1) {
            $id = $match['id'];
            $title = $match['name'];
            $timestamp = $match['timestamp'] ?? 0;

            // Format waktu tayang (WIB)
            if ($timestamp > 0) {
                $wibTime = ($timestamp / 1000) + (7 * 3600);
                $jam = date('H:i', $wibTime);
                $tanggal = date('d/m/Y', $wibTime);
                $timeStr = "$jam WIB ($tanggal)";
            } else {
                $timeStr = "LIVE";
            }

            $logo = $match['home']['logo'] ?: "https://clovers.serv00.net/logo/3.gif";

            // Ambil URL streaming dari endpoint baru
            $metaUrl = "https://api.cakeo.xyz/match/meta-v2/$id";
            $metaResponse = file_get_contents($metaUrl);
            $metaData = json_decode($metaResponse, true);

            if ($metaData['status'] == 1 && !empty($metaData['data']['fansites'])) {
                foreach ($metaData['data']['fansites'] as $fansite) {
                    if (!empty($fansite['play_urls'])) {
                        foreach ($fansite['play_urls'] as $stream) {
                            $quality = strtoupper($stream['name'] ?? 'HD');
                            $url = $stream['url'];

                            $displayTitle = "$title [$quality] • LIVE $timeStr";

                            echo "#EXTINF:-1 group-title=\"LIVE EVENT CAKEO\" tvg-logo=\"$logo\", $displayTitle\n";
                            echo "#EXTVLCOPT:http-user-agent=Mozilla/5.0 (Linux; Android 15) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.6834.122 Mobile Safari/537.36\n";
                            echo "#EXTVLCOPT:http-referrer=https://q.zoomplayer.xyz/\n";
                            echo "#EXTVLCOPT:http-origin=https://q.zoomplayer.xyz/\n";
                            echo "$url\n\n";

                            $found = true;
                        }
                    }
                }
            }
        }
    }

    // Jika tidak ada pertandingan live
    if (!$found) {
        echo "#EXTINF:-1 tvg-logo=\"https://clovers.serv00.net/logo/3.gif\" group-title=\"Event by Cakeo\", Tidak ada pertandingan LIVE\n";
        echo "#EXTVLCOPT:http-referrer=https://q.zoomplayer.xyz/\n";
        echo "#EXTVLCOPT:http-origin=https://q.zoomplayer.xyz/\n";
        echo $noSignalUrl . "\n";
    }
} else {
    echo "Gagal mengambil data dari API.";
}
?>