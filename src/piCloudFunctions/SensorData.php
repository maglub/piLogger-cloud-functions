<?php 

namespace piCloudFunctions;

class SensorData {
    
    protected probeTime;
    protected probeValue;
    protected sensorId;
    protected authToken;
    protected dbhandler;
    
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
	    
	    $this->dbhandler->execute(
		    new Cassandra\SimpleStatement("INSERT INTO sensordata (sensor_id,day,probe_time,probe_value) VALUES (?,?,?,?)"),
			new Cassandra\ExecutionOptions(array('arguments' => array('26.A1E97B000000','2015-06-09','2015-06-09 14:01:01', 22.234)))	    
		);
    }
    
}

?>