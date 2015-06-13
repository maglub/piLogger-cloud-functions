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
    
    
    
    // get data for sensorid and year
    function getDataBySensorYear($sensorId,$year){
	    
	   
		var_dump(generateDayArray($year);

	/*	$statement = $cluster->prepare("SELECT probe_time, probe_value FROM sensordata WHERE sensor_id = ? and day = ?");
		$futures   = array();

		// execute all statements in background
		foreach ($data as $arguments) {
			$futures[]= $cluster->executeAsync($statement, new \Cassandra\ExecutionOptions(array(
                'arguments' => $arguments
            )));
		}

		// wait for all statements to complete
		foreach ($futures as $future) {
			// we will not wait for each result for more than 5 seconds
			$result = $future->get(10);
			foreach ($result as $row){
				echo "time: ".date('Y-m-d H:i:s',$row['probe_time']->time())." and value: ".$row['probe_value']->value()."\n";
			}
		}
	*/ 

	   	    
    }
    
    
    function generateDayArray($year, $month=null, $day=null){
		 
		 
		// create new empty array to hold all days
		$days = array();
 
		 
		// we have just a year param
		if(is_null($day) and is_null($month)){

			$startday = \Carbon\Carbon::create($year)->firstOfYear();
			$endday = $startday->lastOfYear();	
		
		// we have a year and a month set	 
		}elseif(is_null($day) and !is_null($month)){
		
			$startday = \Carbon\Carbon::create($year,$month)->firstOfMonth();
			$endday = $startday->lastOfMonth();
		
		// we have all 3 params 
		}else{
			
			$startday = \Carbon\Carbon::create($year,$month,$day);
			$endday = $startday;
			
		}	
		
		
		// iterate as long as the cur day is less or equals the end day
		while($startday->lte($endday)){
			array_push($days, $startday->toDateString());
			$startday = $startday->tomorrow();
		}	 
		 
		return $days;	 
	}

    
    
    
}

?>