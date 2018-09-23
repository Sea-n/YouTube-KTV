<?php
require("telegram.php");

while (true) {
	$list = trim(file_get_contents('queue'));
	rename('queue', 'now-playing');

	if (!strlen($list)) {
		$list = trim(file_get_contents('history'));
		$list = explode("\n", $list);
		$item = $list[array_rand($list)];
		[$vid, $title] = explode(' ', $item, 2);

		TG('sendMessage', [
			'chat_id' => ChatID,
			'text' => "歌單空了，從點歌記錄抓一首來聽聽吧 😆\n\n<a href='https://www.youtube.com/watch?v=$vid'>$title</a>",
			'parse_mode' => 'HTML'
		]);
		shell_exec("mpv -fs --screen=1 'https://youtu.be/$vid'");

		continue;
	}

	$queue = explode("\n", $list);
	$count = count($queue);

	echo "Playing $count Songs\n\n";

	$text = "準備播放下一輪歌曲 (共 $count 首)";
	foreach ($queue as $i => $line) {
		if (!($i % 5))
			$text .= "\n";
		[$vid, $title] = explode(' ', $line, 2);
		$text .= "\n$i. $title";
	}
	$result = TG('sendMessage', [
		'chat_id' => ChatID,
		'text' => mb_substr($text, 0, 4000),
		'disable_web_page_preview' => true
	]);
	TG('pinChatMessage', [
		'chat_id' => ChatID,
		'message_id' => $result['result']['message_id'],
		'disable_notification' => true
	]);

	$cmd = "mpv -fs --screen=1 --audio-delay=-0.1";
	foreach ($queue as $line) {
		[$vid, $title] = explode(' ', $line, 2);
		$cmd .= " 'https://youtu.be/$vid'";
	}
	echo "Command: $cmd\n";
	shell_exec($cmd);
}
