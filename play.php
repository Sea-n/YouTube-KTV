<?php
require("telegram.php");

while (true) {
	$list = trim(file_get_contents('queue'));
	file_put_contents('queue', '');

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

	echo "Playing " . count($queue) . " Songs\n\n";

	$text = "準備播放下一輪歌曲\n";
	foreach ($queue as $i => $line) {
		[$vid, $title] = explode(' ', $line, 2);
		$text .= "\n$i. $title";
	}
	TG('sendMessage', [
		'chat_id' => ChatID,
		'text' => $text,
		'disable_web_page_preview' => true
	]);

	$cmd = "mpv -fs --screen=1";
	foreach ($queue as $line) {
		[$vid, $title] = explode(' ', $line, 2);
		$cmd .= " 'https://youtu.be/$vid'";
	}
	echo "Command: $cmd\n";
	shell_exec($cmd);
}
