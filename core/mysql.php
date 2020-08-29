<?php class MySQL extends PDO {
	
	// Unique fetch mode (returns associative array with index data as keys)
	const UNIQUE = parent::FETCH_ASSOC | parent::FETCH_UNIQUE;
	
	// Extended connector
	public function __construct($user, $pass, $name=NULL, $host='127.0.0.1', $charset='utf8mb4') {
		parent::__construct('mysql:dbname='.($name ?? $user).';host='.$host.';charset='.$charset, $user, $pass, [
			self::ATTR_EMULATE_PREPARES => false,
			self::ATTR_ERRMODE => self::ERRMODE_EXCEPTION,
			self::ATTR_DEFAULT_FETCH_MODE => self::FETCH_ASSOC,
		]);
	}
	
	// Automated queries
	public function query($query, $data=[]) {
		
		// Use object properties
		if(is_object($data)) $data = get_object_vars($data);
		
		// Array-ify primitive data
		if(!is_array($data)) $data = [$data];
		
		// Prepare statement
		$stmt = parent::prepare($query);
		
		// Return result
		$stmt->execute($data);
		return $stmt;
	}
}