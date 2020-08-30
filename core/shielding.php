<?php class Shielding {
	
	// List of blocked IPs
	private $list;
	
	// Handles
	private $sources, $db, $firewall;
	
	// Badness threshold 
	public $threshold = 0;
	
	// Construct with handles
	public function __construct($sources, $db, $firewall) {
		$this->sources = $sources;
		$this->db = $db;
		$this->firewall = $firewall;
	}
	
	// Installation routine
	public function install() {
		
		// Add firewall rule
		print $this->firewall->addRule()."\r\n";
		
		// Create table
		print "Creating table \"requests\".\r\n";
		$this->db->query('
			CREATE TABLE IF NOT EXISTS requests (
				ip varchar(39) NOT NULL,
				datetime datetime NOT NULL,
				UNIQUE KEY request (ip, datetime)
			)
		');
		
		// Create view
		print "Creating view \"report\".\r\n";
		$this->db->query('			
			CREATE OR REPLACE VIEW report AS SELECT
				ip,
				COUNT(datetime) AS total,
				MIN(DATEDIFF(CURRENT_TIMESTAMP, datetime)) AS latest,
				SUM(1/(DATEDIFF(CURRENT_TIMESTAMP, datetime) + 1)) AS activity
			FROM requests GROUP BY ip
		');
	}
	
	// Delete firewall rule on uninstallation
	public function uninstall() {
		print $this->firewall->deleteRule()."\r\n";
	}
	
	// Output data with current datetime
	public function log(...$cols) {
		print implode("\t", $cols)."\r\n";
	}
	
	// Retrieve request lists from sources
	private function readSources() {
		foreach($this->sources as $source) {
			foreach($source->getList() as $request) {
				
				// Insert request
				$query = 'INSERT IGNORE INTO requests (ip, datetime) VALUES (:ip, :datetime)';
				$this->db->query($query, $request);
				
				// Log request
				$this->log(
					get_class($source),
					$request['datetime'],
					$request['ip'],
				);
			}
		}
	}
	
	// Update firewall rule
	private function updateFirewall() {
		
		// Fetch list of blocked IPs
		$selector = 'SELECT ip, total*activity AS badness FROM report HAVING badness > ?';
		$query = $this->db->query($selector, $this->threshold);
		$list = array_column($query->fetchAll(), 'ip');
		
		// Update firewall rule if list changed
		if($this->list != $list) {
			$this->list = $list;
			$this->log('Firewall', count($list).' entries');
			
			// Enable the rule only if IPs are given or any IP will be blocked
			$this->firewall->toggleRule(false); // disable
			$this->firewall->updateRule($list); // update
			$this->firewall->toggleRule(count($list) > 0); // (maybe) enable
		}
	}
	
	// Runner cycle with exception handling
	public function cycle() {
		try {
			$this->readSources();
			$this->updateFirewall();
		} catch(Exception $e) {
			$this->log('Exception', var_export($e->getMessage(), true)); 
		}
	}
}