import requests
import json
import os

# Fetch data from the URL
url = "https://luckpool.net/verus/miner/RVxwfn5TggLnYPgEAGQf8W7kes28QNQGJg"
response = requests.get(url)
data = response.json()

# Process and format the data
formatted_data = ""
for idx, worker in enumerate(data['workers'], start=1):
    formatted_data += f"{idx} - {worker['name']} - {worker['hashrate']} - {worker['status']}\n"

# Send data to Telegram
telegram_token = os.environ['TELEGRAM_BOT_TOKEN']
telegram_chat_id = os.environ['TELEGRAM_CHAT_ID']
telegram_api_url = f"https://api.telegram.org/bot{telegram_token}/sendMessage"
message = f"Address : {data['address']}\nHashrate : {data['total_hashrate']}\n\nNo | Worker | Hashrate | Status\n{formatted_data}"
payload = {
    'chat_id': telegram_chat_id,
    'text': message
}
requests.post(telegram_api_url, json=payload)
