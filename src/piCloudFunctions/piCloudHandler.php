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
	    
	    // prepare SQL statement for Cassandra
	    $statement =  $this->cassandraConnection->prepare('INSERT INTO sensordata (sensor_id,day,probe_time,probe_value) VALUES (?,?,?,?)');
	    
	    // prepare values for SQL insert
	    $value = new \Cassandra\Float($probeValue);
	    $timestamp = new \Cassandra\Timestamp($probeTime);
	    $day = date('Y-m-d',$probeTime);
	    
	    // Execute the prepared SQL statement with Execution Options
	    $this->cassandraConnection->execute($statement, new \Cassandra\ExecutionOptions(array($sensorId,$day,$timestamp,$value )));
	    
    }
    
    // check if a given sensor is authenticated by verifying the given token
    function isSensorAuthenticated($sensorId, $authToken){
	    echo "test";
    }
    
}

?>