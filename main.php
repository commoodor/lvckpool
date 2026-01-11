<?php
// Fetch ENV
$address_token         = getenv('ADDRESS_PRIMARY');
$telegram_bot_token    = getenv('TELEGRAM_BOT_TOKEN');
$telegram_chat_id      = getenv('TELEGRAM_CHAT_ID');
$coingecko_token       = getenv('COIN_GECKO_API');

// ---------- LUCKPOOL GET DATA ----------
$url         = "https://luckpool.net/verus/miner/" . $address_token;
$response    = @file_get_contents($url);

if(!$response){
    die("❌ Failed fetching LuckPool API");
}

$data = json_decode($response, true);

// ---------- BALANCE FROM EXPLORER ----------
$balance_raw = @file_get_contents("https://explorer.verus.io/ext/getbalance/" . $address_token);
if(!$balance_raw){
    $balance_raw = 0;
}
$balance_raw = floatval($balance_raw);
$balance = $balance_raw / 100000000;


// ---------- PRICE CONVERTER ----------
function estimatedpaid($amount) {
    global $coingecko_token;
    $url = "https://api.coingecko.com/api/v3/simple/price?ids=verus-coin&vs_currencies=idr&x_cg_demo_api_key=" . $coingecko_token;
    $response = @file_get_contents($url);

    if (!$response) {
        return "API ERROR";
    }

    $data = json_decode($response, true);
    
    $idr = $amount * $data['verus-coin']['idr'];
    $formatted_amount = number_format($idr, 2, ',', '.');
    return preg_replace('/(\d)(?=(\d{3})+(?!\d))/', '$1.', $formatted_amount) . " IDR";
}


// ---------- WORKER HANDLING ----------
$workers = $data['workers'];
sort($workers);

$formatted_data = "";
foreach ($workers as $index => $worker) {
    $workerData = explode(':', $worker);

    $workerUrl  = 'https://luckpool.net/verus/worker/' . $data['address'] . '.' . $workerData[0];
    $workerJson = @file_get_contents($workerUrl);
    $workerInfo = json_decode($workerJson, true);

    $formatted_data .=
        ($index + 1) . " | " .
        (($workerData[3] == 'on') ? '🟢' : '🔴') . " " .
        $workerData[0] . " - " .
        $workerInfo['hashrateString'] . " - " .
        $workerInfo['software'] . "\n";
}

$message = "
🌐 Report Date : " . date('d-m-Y H:i:s', $data['timestamp'] + 25200) . " 🌐\n
🔰 Address : " . $data['address'] . "\n
⚡ Hashrate : " . $data['hashrateString'] . "
📊 Estimated Luck : " . $data['estimatedLuck'] . "
⚠ Efficiency : " . $data['efficiency'] . "%\n
♻ Immature : " . $data['immature'] . " VRSC
💰 Pool Balance : " . $data['balance'] . " VRSC
💎 Wallet Balance : " . number_format($balance, 8) . " VRSC
💵 Price : " . estimatedpaid(1) . "
💵 Estimated Paid : " . estimatedpaid($balance) . "

# | Status | ID | Hashrate | Miner
" . $formatted_data;

$telegram_api_url = "https://api.telegram.org/bot$telegram_bot_token/sendMessage";
$options = [
    'http' => [
        'method'  => 'POST',
        'header'  => 'Content-Type: application/json',
        'content' => json_encode([
            'chat_id' => $telegram_chat_id,
            'text'    => $message
        ])
    ]
];

$result = file_get_contents($telegram_api_url, false, stream_context_create($options));
echo ($result === FALSE) ? "❌ Failed to send message." : "✅ Message sent successfully.";
