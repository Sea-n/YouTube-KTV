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
		if ($data['chat']['id'] !== $ChatID)
			continue;

		$data = $data['message'];
		if (preg_match_all('#(?:v=|youtu.be/)([a-zA-Z0-9_-]{11})#ui', $data['text'], $matches)) {
			foreach ($matches[1] as $vid) {
				$title = trim(shell_exec("youtube-dl -e $vid"));
				file_put_contents('history', $vid . " " . $title . "\n", FILE_APPEND);
				file_put_contents('queue', $vid . " " . $title . "\n", FILE_APPEND);
				echo "Added $vid $title\n";
			}
		}
	}
}
