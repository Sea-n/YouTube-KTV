<?php
require("telegram.php");

while (true) {
	$list = trim(file_get_contents('queue'));
	file_put_contents('queue', '');

	if (!strlen($list))
		continue;

	$queue = explode("\n", $list);
	if (!count($queue))
		continue;

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
