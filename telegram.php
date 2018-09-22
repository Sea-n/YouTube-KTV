<?php
function TG(string $method, array $query = []): array {
	$botToken = trim(file_get_contents('TOKEN'));

	$json = json_encode($query);

	$url = "https://api.telegram.org/bot{$botToken}/{$method}";

	$curl = curl_init();
	curl_setopt_array($curl, [
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => $json,
		CURLOPT_HTTPHEADER => [
			'Content-Type: application/json; charset=utf-8'
		]
	]);
	$data = curl_exec($curl);
	curl_close($curl);

	$data = json_decode($data, true);

	if (!$data['ok'] && $data['error_code'] != 429 && $data['error_code'] != 403) {
		$result = TG('sendMessage', [
			'chat_id' => ChatID,
			'text' => substr(json_encode($data, JSON_PRETTY_PRINT), 0, 4000)
		]);
		$result = TG('sendMessage', [
			'chat_id' => ChatID,
			'reply_to_message_id' => $result['result']['message_id'],
			'text' => substr($method . json_encode($query, JSON_PRETTY_PRINT), 0, 4000)
		]);
	}

	return $data;
}

define('ChatID', trim(file_get_contents('chat-id')));
