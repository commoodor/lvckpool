<?php
// Fetch data from the URL
$url = "https://luckpool.net/verus/miner/RVxwfn5TggLnYPgEAGQf8W7kes28QNQGJg";
$response = file_get_contents($url);
$data = json_decode($response, true);

// Process and format the data
$formatted_data = "";
foreach ($data['workers'] as $idx => $worker) {
    $formatted_data .= ($idx + 1) . " - " . $worker['name'] . " - " . $worker['hashrate'] . " - " . $worker['status'] . "\n";
}

// Send data to Telegram
$telegram_bot_token = getenv('TELEGRAM_BOT_TOKEN');
$telegram_chat_id = getenv('TELEGRAM_CHAT_ID');
$telegram_api_url = "https://api.telegram.org/bot$telegram_bot_token/sendMessage";
$message = "Address : " . $data['address'] . "\nHashrate : " . $data['total_hashrate'] . "\n\nNo | Worker | Hashrate | Status\n" . $formatted_data;
$payload = [
    'chat_id' => $telegram_chat_id,
    'text' => $message
];

$options = [
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => json_encode($payload)
    ]
];

$context = stream_context_create($options);
$result = file_get_contents($telegram_api_url, false, $context);
if ($result === FALSE) {
    echo "Failed to send message to Telegram.";
} else {
    echo "Message sent to Telegram successfully.";
}
?>
