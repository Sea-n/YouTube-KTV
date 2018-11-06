<?php
require("telegram.php");

$param = join(' ', [
	'--screen=1',
	'--fullscreen',
	'--slang=zh-TW,en,ja',
	'--volume=100',
	'--no-input-default-bindings',
	'--no-input-terminal',
	'--input-ipc-server=socket'
]);

while (true) {
	rename('queue', 'now-playing');
	touch('queue'); // Clear queue
	file_put_contents('pin-id', ''); // Stop pin updating
	$list = trim(file_get_contents('now-playing'));

	if (!strlen($list)) {
		$list = trim(file_get_contents('history'));
		$list = explode("\n", $list);
		$item = $list[array_rand($list)];
		file_put_contents('now-playing', $item);
		[$vid, $title] = explode(' ', $item, 2);

		shell_exec("php pin.php warm &> /dev/null &"); // Send Message

		shell_exec("mpv $param 'https://youtu.be/$vid'"); // Play

		continue;
	}

	shell_exec("php pin.php send &> /dev/null &");

	$queue = explode("\n", $list);
	$count = count($queue);

	$cmd = "mpv $param";
	foreach ($queue as $line) {
		[$vid, $title] = explode(' ', $line, 2);
		$cmd .= " 'https://youtu.be/$vid'";
	}
	echo "Command: $cmd\n";
	shell_exec($cmd);
}
