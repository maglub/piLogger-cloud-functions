<?php 

namespace piCloudFunctions;

final class DatabaseCassandra {
    
	// private static variable to hold the singelton instance
	private static $instance;
	
	// private variable to hold the db connectoin
	private $connection;
    
    // public fucntion to get the instance
	public static function getInstance() {
		
		// If no instance then make one
		if(!self::$instance) { 
			self::$instance = new self();
		}
		return self::$instance;
	}
	    
    // private constructor to initialize Cassandra Connection
    private function __cunstruct() {  
    	
    	// generate the Cassandra DB object and connect to the cluster
    	$this->connection = Cassandra::cluster()
            					->withContactPoints('172.31.5.160', '172.31.5.161')
								->withPort(9042)
								->build()
								->connect('piclouddb');    	 
    }  
	
	// Magic method clone is empty to prevent duplication of connection
	private function __clone() { }
	
	// Get the cassandra database connection
	public function getConnection() {
		return $this->connection;
	}
}

?>