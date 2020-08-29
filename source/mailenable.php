<?php class MailEnable {
	
	// Lines to skip in log
	private $skipLines = [
		'****************** LOG FILE STARTED *******************',
		'****************** LOG FILE CLOSED ********************',
	];
	
	// Log fields (ordered)
	private $keys = [
		'datetime',
		'channel',
		'file',
		'x',
		'ip',
		'command',
		'message',
		'response',
		'y',
		'z',
		'username',
		'u',
	];
	
	// Date and file position
	private $today, $pos;
	
	// Logpath (input)
	private $path;
	
	// Construct with logpath
	public function __construct($path) {
		$this->path = $path;
	}
	
	// Read lines from stored position to EOF
	private function readLines() {
		$file = $this->path.'SMTP-Activity-'.$this->today.'.log';
		
		// Wait in case the file is not yet created
		if(!file_exists($file)) return [];
		
		// Open handle and move to position
		$handle = fopen($file, 'r');
		fseek($handle, $this->pos);
		
		// Read lines till EOF
		while($line = fgets($handle)) yield $line;
		
		// Store position and close handle
		$this->pos = ftell($handle);
		fclose($handle);
	}
	
	// Get list of failed requests
	public function getList() {
		
		// Reset log position for a new day
		$today = date('ymd');
		if($this->today != $today) {
			$this->today = $today;
			$this->pos = 0;
		}
		
		// Read new lines
		foreach($this->readLines() as $line) {
			
			// Skip non-data lines
			foreach($this->skipLines as $skip) {
				if(strpos($line, $skip) > 0) continue 2;
			}
			
			// Extract data
			$data = array_combine($this->keys, explode("\t", $line));
			$data['code'] = substr($data['response'], 0, 3);
			
			// Analyze request
			if(
				$data['command'] = 'AUTH' and
				$data['code'] == '535'
			
			// Yield failed request
			) yield [
				'ip' => $data['ip'],
				'datetime' => (new DateTime($data['datetime']))->format('Y-m-d H:i:s'),
			];
		}
	}
}