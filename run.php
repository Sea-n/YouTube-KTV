<?php
require('telegram.php');

$offset = 0;
while (true) {
	$data = TG('getUpdates', [
		'offset' => $offset
	]);
	if (!$data['ok'])
		continue;
	$data = $data['result'];
	if (!count($data))
		continue;
	$offset = end($data)['update_id'] + 1;

	foreach ($data as $data) {
		if (!isset($data['message']))
			continue;
		$data = $data['message'];

		if ($data['chat']['id'] != ChatID) {
			TG('sendMessage', [
				'text' => "請加入 [@SeanChannel](https://t.me/SeanChannel) 群組點歌",
				'chat_id' => $data['chat']['id'],
				'parse_mode' => 'Markdown'
			]);
			continue;
		}

		if (!isset($data['from']['username']))
			continue;

		if (preg_match('/(vol|volume|音量) *(\d+)/i', $data['text'], $matches)) {
			volume($matches[2]);
		}

		if (in_array(strtolower($data['text']), [
			'skip',
			'next',
			'跳過',
			'卡歌',
			'刪歌',
			'下一首'
		])) {
			/* If just start the song */
			$payload = json_encode([
				'command' => [
					'get_property',
					'time-pos'
				]
			]);
			$data = json_decode(shell_exec("echo '$payload' | socat - ./socket"), true);
			$pos = $data['data']; // Remaining length of the file in seconds

			if ($pos < 10) {
				TG('sendMessage', [
					'text' => "開始播放前 10 秒不接受卡歌",
					'chat_id' => $data['chat']['id'],
					'parse_mode' => 'Markdown'
				]);
				continue;
			}

			/* If the song is ending */
			$payload = json_encode([
				'command' => [
					'get_property',
					'time-remaining'
				]
			]);
			$data = json_decode(shell_exec("echo '$payload' | socat - ./socket"), true);
			$remaining = $data['data']; // Remaining length of the file in seconds

			if ($remaining < 10) {
				TG('sendMessage', [
					'text' => "聽完最後 $remaining 秒吧",
					'chat_id' => $data['chat']['id'],
					'parse_mode' => 'Markdown'
				]);
				continue;
			}

			/* Skip the song */
			volume(50); // Fade Out

			$payload = json_encode([
				'command' => [
					'playlist-next',
					'force' // Terminate playback if there are no more files on the playlist
				]
			]);
			shell_exec("echo '$payload' | socat - ./socket");

			volume(100); // Reset volume for next song
		}

		if (preg_match_all('#(?:v=|youtu.be/)([a-zA-Z0-9_-]{11})#ui', $data['text'], $matches))
			foreach ($matches[1] as $vid)
				shell_exec("php add.php {$data['chat']['id']} {$data['message_id']} $vid &> /dev/null &");
	}
}

function volume(int $to) {
	$payload = json_encode([
		'command' => [
			'get_property',
			'volume'
		]
	]);
	$from = json_decode(shell_exec("echo '$payload' | socat - ./socket"), true)['data'];

	echo "Adjust volume from $from to $to\n";

	$step = ($to - $from) / 50;
	for ($k=0; $k<50; $k++) {
		$payload = json_encode([
			'command' => [
				'set_property',
				'volume',
				$from + $step*$k
			]
		]);
		shell_exec("echo '$payload' | socat - ./socket");
	}
}
