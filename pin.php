<?php
require('telegram.php');

$list = trim(file_get_contents('now-playing'));
$queue = explode("\n", $list);
$count = count($queue);

/* Warm up */
if ($argv[1] == 'warm') {
	[$vid, $title] = explode(' ', $list, 2);

	TG('sendMessage', [
		'chat_id' => ChatID,
		'text' => "歌單空了，從點歌記錄抓一首來聽聽吧 😆\n\n<a href='https://www.youtube.com/watch?v=$vid'>$title</a>",
		'parse_mode' => 'HTML',
	]);

	$result = TG('getChat', [
		'chat_id' => ChatID
	]);
	if ($result['result']['pinned_message'])
		TG('unpinChatMessage', [
			'chat_id' => ChatID,
		]);
}

/* Metadata of current playing list */
$payload = json_encode([
	'command' => [
		'get_property',
		'playlist-pos'
	]
]);
$data = json_decode(shell_exec("echo '$payload' | socat - ./socket 2> /dev/null"), true) ?? 0;

$pos = $data['data']; // Current position on playlist

$payload = json_encode([
	'command' => [
		'get_property',
		'percent-pos'
	]
]);
$data = json_decode(shell_exec("echo '$payload' | socat - ./socket"), true);
$percent_pos = (int) $data['data']; // Position in current file (percentage)

if ($pos) // Not first song
	$text = "正在播放 $count 首歌曲";
else
	$text = "準備播放下一輪歌曲 (共 $count 首)";

/* Generate list message */
foreach ($queue as $i => $line) {
	if (!($i % 5)) // Every 5 songs
		$text .= "\n"; // New paragraph

	[$vid, $title] = explode(' ', $line, 2);
	if ($i == $pos) { // Now Playing
		$text .= "\n$i. <a href='https://youtube.com/watch?v=$vid#" . date('s') . "'>" . enHTML("$title") . '</a>';
		if ($percent_pos <= 95) // No playing progress for last 5% of song
			$text .= " ($percent_pos%)";
	} else
		$text .= enHTML("\n$i. $title");
}

if ($argv[1] == 'send') {
	$result = TG('sendMessage', [
		'chat_id' => ChatID,
		'text' => mb_substr($text, 0, 4000),
		'parse_mode' => 'HTML',
	]);
	$msg_id = $result['result']['message_id'];
	TG('pinChatMessage', [
		'chat_id' => ChatID,
		'message_id' => $msg_id,
		'disable_notification' => true
	]);
	file_put_contents('pin-id', $msg_id);
} else if ($argv[1] == 'edit') {
	$msg_id = file_get_contents('pin-id');
	if ($msg_id)
		$result = TG('editMessageText', [
			'chat_id' => ChatID,
			'message_id' => $msg_id,
			'text' => mb_substr($text, 0, 4000),
			'parse_mode' => 'HTML',
		]);
}
