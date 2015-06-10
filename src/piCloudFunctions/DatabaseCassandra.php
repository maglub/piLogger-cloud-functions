<?php 

namespace piCloudFunctions;

class DatabaseCassandra {
    
	// private static variable to hold the instance
	private static $instance;
	
	// Magic method clone is empty to prevent duplication of connection
	private function __clone() { }
	
	// empty constructor so it can't be directly initiated
	private function __construct() { }
      
    public static function call(){  
    	
    	// create the instance if it does not exist  
        if(!isset(self::$instance)){  
            
            // the MYSQL_* constants should be set to or  
            //  replaced with your db connection details  
            self::$instance = Cassandra::cluster()
            					->withContactPoints('172.31.5.160', '172.31.5.161')
								->withPort(9042)
								->build()
								->connect('piclouddb');  
            
            
         
        }  
        // return the instance  
        return self::$instance;  
    }  
	

}

?>