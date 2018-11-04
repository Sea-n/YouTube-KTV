<?php
require('telegram.php');

$chat = $argv[1];
$msg_id = $argv[2];
$vid = $argv[3];

$title = trim(shell_exec("youtube-dl -e -- $vid"));
$title = preg_replace('# \(.*?\)| \[.*?\]#', '', $title);
file_put_contents('history', $vid . " " . $title . "\n", FILE_APPEND);
file_put_contents('queue', $vid . " " . $title . "\n", FILE_APPEND);

$count = explode(' ', trim(shell_exec('wc -l queue')), 2)[0];

if ($chat == ChatID) {
	echo "Added $vid $title\n";

	$result = TG('sendMessage', [
		'text' => "已加入 $title\n\n目前共 $count 首歌",
		'chat_id' => ChatID,
		'reply_to_message_id' => $msg_id,
		'disable_web_page_preview' => true
	]);
	$new_msg_id = $result['result']['message_id'];

	$old_msg_id = trim(file_get_contents('msg-id'));
	file_put_contents('msg-id', $new_msg_id);
	
	TG('deleteMessage', [
		'chat_id' => ChatID,
		'message_id' => $old_msg_id
	]);
}
