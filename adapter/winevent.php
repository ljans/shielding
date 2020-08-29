<?php class WinEvent {
	
	// Event data
	const format = 'yyyy-MM-dd HH:mm:ss'; // For MySQL DateTime
	const category = 'Security'; // Protocol file
	const eventID = '4625'; // Account failed to log on
	
	// Keys for powershell result array (ordered)
	private $keys = [
		'ip',
		'datetime',
	];
	
	// Datetime of the last check
	private $datetime;
	
	// Start at the time of constructing
	public function __construct() {
		$this->datetime = new DateTime();
	}
	
	// Assemble the powershell command
	private function getCommand() {
		return 'Get-WinEvent'.
			' -ErrorAction SilentlyContinue'.
			' -MaxEvents 10'.
			' -FilterHashtable @{'.
				'LogName=\''.self::category.'\';'.
				'StartTime=[datetime]\''.$this->datetime->format('m/d/Y H:i:s').'\';'.
				'ID='.self::eventID.''.
			'} | ForEach-Object {'.
				'if($_.Properties.value[19] -ne \'-\') {'.
					'('.
						'$_.Properties.value[19],'.
						'$_.TimeCreated.tostring(\''.self::format.'\')'.
					') | ConvertTo-Json -Compress'.
				'}'.
			'}'
		;
	}
	
	// Get list of failed requests
	public function getList() {
		
		// Retrieve JSON lines via powershell
		exec('powershell -command "'.$this->getCommand().'"', $list);
		
		// Save current datetime
		$this->datetime = new DateTime();
		
		// Yield parsed lines
		foreach($list as $data) yield array_combine($this->keys, json_decode($data));
	}
}