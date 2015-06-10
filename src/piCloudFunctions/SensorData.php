<?php 

namespace piCloudFunctions;

class SensorData {
    
    protected $probeTime;
    protected $probeValue;
    protected $sensorId;
    protected $authToken;
    protected $dbhandler;
    
    function __construct($dbConnection) {
    	$this->dbhandler = $dbConnection;
    }
    
    function setSensorData($probeTime,$probeValue,$sensorId,$authToken){
	    $this->probeTime = $probeTime;
    	$this->probeValue = $probeValue;
    	$this->sensorId = $sensorId;
    	$this->authToken = $authToken;    
    }
    
    
    function saveDataPoint(){
	    
	    $statement =  $this->dbhandler->prepare('INSERT INTO sensordata (sensor_id,day,probe_time,probe_value) VALUES (?,?,?,?)');
	    
	    $this->dbhandler->execute($statement, new \Cassandra\ExecutionOptions(array('26.A1E97B000000','2015-06-09',new \Cassandra\Timestamp(1433950852),new \Cassandra\Float( 22.234) )));

	    
    }
    
}

?>