<?php class Firewall {
	
	// Rule data
	private $name, $description ;
	
	// Construct with rule data
	public function __construct($data) {
		$this->name = $data['name'];
		$this->description = $data['description'];
	}
	
	// Execute a firewall command
	private function exec($cmd) {
		exec('netsh advfirewall firewall '.$cmd, $output);
		return join("\r\n", $output);
	}
	
	// Update RemoteIP parameter
	public function updateRule(array $list) {
		return $this->exec('set rule name="'.$this->name.'" new RemoteIP="'.join(',', $list).'"');
	}
	
	// Enable/disable rule
	public function toggleRule(bool $enable=false) {
		return $this->exec('set rule name="'.$this->name.'" new enable='.($enable ? 'yes' : 'no'));
	}
	
	// Display rule details
	public function showRule() {
		return $this->exec('show rule name="'.$this->name.'"');
	}
	
	// Delete rule
	public function deleteRule() {
		return $this->exec('delete rule name="'.$this->name.'"');
	}
	
	// Create disabled rule (can be done multiple times!)
	public function addRule() {
		return $this->exec('add rule '.join(' ', [
			'name="'.$this->name.'"',
			'dir=in',
			'action=block',
			'description="'.$this->description.'"',
			'enable=no',
			'profile=any',
		]));
	}
}