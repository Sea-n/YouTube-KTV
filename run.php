<?php
require('telegram.php');

$offset = 0;
$msg_id = trim(file_get_contents('msg-id'));
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

		if ($data['chat']['id'] != ChatID)
			continue;

		if (!isset($data['from']['username']))
			continue;

		if (preg_match_all('#(?:v=|youtu.be/)([a-zA-Z0-9_-]{11})#ui', $data['text'], $matches)) {
			foreach ($matches[1] as $vid) {
				$title = trim(shell_exec("youtube-dl -e $vid"));
				$title = preg_replace('# \(.*?\)| \[.*?\]#', '', $title);
				file_put_contents('history', $vid . " " . $title . "\n", FILE_APPEND);
				file_put_contents('queue', $vid . " " . $title . "\n", FILE_APPEND);
				echo "{$data['from']['username']} added $vid $title\n";

				$count = explode(' ', trim(shell_exec('wc -l queue')), 2)[0];

				if ($data['chat']['id'] == ChatID) {
					$result = TG('sendMessage', [
						'text' => "已加入 $title\n\n目前共 $count 首歌",
						'chat_id' => ChatID,
						'reply_to_message_id' => $data['message_id'],
						'disable_web_page_preview' => true
					]);
					TG('deleteMessage', [
						'chat_id' => ChatID,
						'message_id' => $msg_id
					]);
					$msg_id = $result['result']['message_id'];
				}
			}
		}
	}
	file_put_contents('msg-id', $msg_id);
}
