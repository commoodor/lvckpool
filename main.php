<?php
// Fetch data from the URL
$address_token         = getenv('ADDRESS_PRIMARY');
$telegram_bot_token    = getenv('TELEGRAM_BOT_TOKEN');
$telegram_chat_id      = getenv('TELEGRAM_CHAT_ID');
$url = "https://luckpool.net/verus/miner/" . $address_token . "";
$response = file_get_contents($url);
$data = json_decode($response, true);

// Fetch balance from explore.verus.io
$balance = file_get_contents("https://explorer.verus.io/ext/getbalance/" . $address_token . "");

// Convert VRSC to IDR, u can change to your currency
function estimatedpaid($amount) {
    $url = "https://api.coingecko.com/api/v3/simple/price?ids=verus-coin&vs_currencies=idr";
    $data = json_decode(file_get_contents($url), true);
    $formatted_amount = number_format($amount * $data['verus-coin']['idr'], 2, ',', '.');
    // Add dot for every three digits from the right
    $formatted_amount = preg_replace('/(\d)(?=(\d{3})+(?!\d))/', '$1.', $formatted_amount);
    return $formatted_amount . " IDR";
}

// Extract and sort worker data
$workers = $data['workers'];
sort($workers);

// Display worker
$formatted_data = "";
foreach ($workers as $index => $worker) {
    $workerData = explode(':', $worker);
    
    // Fetch additional data for each worker
    $workerUrl = 'https://luckpool.net/verus/worker/' . $data['address'] . '.' . $workerData[0];
    $workerJson = file_get_contents($workerUrl);
    $workerInfo = json_decode($workerJson, true);
    
    // Process and format the data
$formatted_data .=
($index + 1) . " | " .
(($workerData[3] == 'on') ? '🟢' : '🔴') . " " . // Status
$workerData[0] . " - " . // ID
$workerInfo['hashrateString'] . " - " . // Hashrate
$workerInfo['software'] . "\n"; // Miner Application
}

// Send data to Telegram
$telegram_api_url = "https://api.telegram.org/bot$telegram_bot_token/sendMessage";
$message = "
🌐Report Date : " . date('d-m-Y H:i:s', $data['timestamp'] + 25200) . " 🌐\n
🔰Address : " . $data['address'] . "\n
⚡️Hashrate : " . $data['hashrateString'] . "
📊Estimated Luck : " . $data['estimatedLuck'] . "
⚠Efficiency : " . $data['efficiency'] . "%\n
♻Immature : " . $data['immature'] . "
💰Balance Pool : " . $data['balance'] . "
💎Total Balance : " . $balance . " 
💵Price : " . estimatedpaid('1') . "
💵Estimated Paid : " . estimatedpaid($balance) . "\n

# | Status | ID | Hashrate | Miner \n" . 
$formatted_data;

$options = [
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => json_encode(['chat_id' => $telegram_chat_id, 'text' => $message])
    ]
];

$result = file_get_contents($telegram_api_url, false, stream_context_create($options));
echo ($result === FALSE) ? "Failed to send message." : "Message sended successfully.";
?>
