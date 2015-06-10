<?php 

namespace piCloudFunctions;

class piCloudHandler {
    
    // class variables holding the mysql and the cassandra connection
    protected $mysqlConnection;
    protected $cassandraConnection;
    
    // constructor
    function __construct() { 
	    
    }
    
    // setter function for mysql connection
    function setMysqlConnection($mysqlConnection){
	    $this->mysqlConnection = $mysqlConnection;
    }
    
    // setter function for cassandra connection
    function setCassandraConnection($cassandraConnection){
	    $this->cassandraConnection = $cassandraConnection;
    }
    
    
    function setSensorData($probeTime,$probeValue,$sensorId,$authToken){
	    $this->probeTime = $probeTime;
    	$this->probeValue = $probeValue;
    	$this->sensorId = $sensorId;
    	$this->authToken = $authToken;    
    }
    
    
    function saveDataPoint(){
	    
	    $statement =  $this->cassandraConnection->prepare('INSERT INTO sensordata (sensor_id,day,probe_time,probe_value) VALUES (?,?,?,?)');
	    
	    $this->cassandraConnection->execute($statement, new \Cassandra\ExecutionOptions(array('26.A1E97B000000','2015-06-09',new \Cassandra\Timestamp(1433950852),new \Cassandra\Float( 22.234) )));

	    
    }
    
}

?>