<?php
declare(strict_types=1);
date_default_timezone_set('Asia/Jakarta');

const API      = 'https://api.camel1.live/camel-service/ee/sports_live/home';
const LOGO     = 'https://cubmu.adp-premium.my.id/combine.php';
const UA       = 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36';
const REFERER  = 'https://www.camel1.live/';
const ORIGIN   = 'https://www.camel1.live';
const NOSIGNAL = 'http://globaltv.my.id/nosignal.m3u8';
const PAGE     = 1;
const SIZE     = 100;
const TTL      = 180;

function fetch_json(string $url): ?array {
    $file = sys_get_temp_dir().'/'.md5($url).'.json';
    if(file_exists($file) && time()-filemtime($file)<TTL){
        $data = @json_decode(@file_get_contents($file), true);
        if(is_array($data)) return $data;
    }
    $opts = ['http'=>['method'=>'GET','timeout'=>5,'header'=>implode("\r\n",[
        "Accept: application/json",
        "User-Agent: ".UA,
        "Referer: ".REFERER,
        "Origin: ".ORIGIN
    ])],'ssl'=>['verify_peer'=>true,'verify_peer_name'=>true]];
    $raw = @file_get_contents($url,false,stream_context_create($opts));
    if($raw) @file_put_contents($file,$raw);
    $json = @json_decode($raw,true);
    return is_array($json)?$json:null;
}

header('Content-Type: audio/x-mpegurl; charset=utf-8');
header('Content-Disposition: inline; filename="camel1_auto.m3u"');
echo "#EXTM3U\n";

$json = fetch_json(API.'?page='.PAGE.'&size='.SIZE);
$total=0;

if($json['data']['results']??false){
    foreach($json['data']['results'] as $m){
        $now=time();
        $status=(int)($m['status_id']??0);
        $ts=(int)($m['match_time']??0);
        $has=(int)($m['coverage']['has_stream']??0)===1;

        $home=$m['home_team']['name']??'Home';
        $away=$m['away_team']['name']??'Away';
        $homeLogo=$m['home_team']['logo']??'';
        $awayLogo=$m['away_team']['logo']??'';
        $matchId=$m['id']??null;
        if(!$matchId) continue;

        if($status===2||($ts>0&&$now>=$ts&&$now<=$ts+10800)){
            if(!$has) continue;
            $group='LIVENOW';
            $url="http://adp-premium.my.id/stream_camel1.php?id=$matchId";
        }elseif($ts>$now){
            $group='UPCOMING';
            $url=NOSIGNAL;
        }else continue;

        $title="$home vs $away".($ts? " (".date('d/m H:i',$ts)." WIB)":'');
        $logo=LOGO.'?home='.urlencode($homeLogo).'&away='.urlencode($awayLogo).'&hname='.urlencode($home).'&aname='.urlencode($away);

        echo "#EXTVLCOPT:http-referrer=".REFERER."\n";
        echo "#EXTVLCOPT:http-origin=".ORIGIN."\n";
        echo "#EXTVLCOPT:http-user-agent=".UA."\n";
        echo "#EXTINF:-1 tvg-id=\"\" tvg-name=\"$title\" tvg-logo=\"$logo\" group-title=\"$group\",$title\n";
        echo "$url\n\n";

        $total++;
    }
}

if(!$total){
    echo "#EXTINF:-1, Tidak ada event tersedia\n";
    echo "http://127.0.0.1/empty\n";
}
