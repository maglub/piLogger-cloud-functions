<?php 

namespace piCloudFunctions;

class piCloudHandler {
    
    // class variables holding the mysql and the cassandra connection
    protected $mysqlConnection;
    protected $cassandraConnection;
    
    // empty constructor
    function __construct() { }
    
    // setter function for mysql connection
    function setMysqlConnection($mysqlConnection){
	    $this->mysqlConnection = $mysqlConnection;
    }
    
    // setter function for cassandra connection
    function setCassandraConnection($cassandraConnection){
	    $this->cassandraConnection = $cassandraConnection;
    }
    
     
    // save a new data point to the cassandra database
    function saveNewDataPoint($sensorId,$probeTime, $probeValue){
	   	    
	    // prepare values for SQL insert
	    $value = new \Cassandra\Float($probeValue);
	    $timestamp = new \Cassandra\Timestamp($probeTime);
	    $day = date('Y-m-d',$probeTime);
	   	
	   	// execute the statement with above values    
	    $this->cassandraConnection->execute(
			new \Cassandra\SimpleStatement("INSERT INTO sensordata (sensor_id,day,probe_time,probe_value) VALUES (?,?,?,?)"),
			new \Cassandra\ExecutionOptions(array('arguments' => array($sensorId,$day,$timestamp,$value )))
		);
	    
    }
    
    // check if a given sensor is authenticated by verifying the given token
    function isSensorAuthenticated($sensorId, $authToken){
	    
	    // prepare SQL statement
		$stmt = $this->mysqlConnection->prepare('select 1 from sensor s
													join device d on (d.did = s.attached)
													join user u on (d.owner = u.uid)
													where u.authtoken = :token
													and s.identifier = :sensor ');
		
		// bind variables and execute										
		$stmt->execute(array(':token' => $authToken, ':sensor' => $sensorId ));										
		
		
		
		// if we got a mysql row back we can return true
		if ($stmt->rowCount() == 1){
			return true;
		}
		
		
		// if we come up to this point we have to return false
		return false;
		
    }
    
}

?>