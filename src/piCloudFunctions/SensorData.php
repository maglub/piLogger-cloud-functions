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
	    
    }
    
}

?>