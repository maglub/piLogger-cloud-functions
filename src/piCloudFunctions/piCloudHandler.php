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
                                                where u.authtoken = :token and s.identifier = :sensor ');
		
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
   function getDataBySensorYear($sensorId, $year){
	    
      // generate an array with all days for the given year
      $allDays = $this->generateDayArray($year);

      // call function to generate json and return array back to user
     	return $this->getJSONDataPointsAsync($sensorId, $allDays);
   }
   
   // get data for sensorid and month
   function getDataBySensorMonth($sensorId, $year, $month){
	    
      // generate an array with all days for the given year
      $allDays = $this->generateDayArray($year, $month);

      // call function to generate json and return array back to user
     	return $this->getJSONDataPointsAsync($sensorId, $allDays);
   }

   // get data for sensorid and specific day
   function getDataBySensorDay($sensorId, $year, $month, $day){
	    
      // generate an array with all days for the given year
      $allDays = $this->generateDayArray($year, $month, $day);

      // call function to generate json and return array back to user
     	return $this->getJSONDataPointsAsync($sensorId, $allDays);
   }

   
   // this function queries cassandra DB and returns a json
   function getJSONDataPointsAsync($sensorId, $allDaysArray){
		
      // prepare the SQL statement
      $statement = $this->cassandraConnection->prepare("SELECT probe_time, probe_value FROM sensordata WHERE sensor_id = ? and day = ?");
		
      // empty array to hold query results
      $futures = array();

      // execute all statements in background
      foreach ($allDaysArray as $current_day) {
         // execute statement async and with exec option and hold result in array
         $futures[]= $this->cassandraConnection->executeAsync($statement, new \Cassandra\ExecutionOptions(array('arguments' => array($sensorId, $current_day))));
      }
      
      // wait for all statements to complete
      foreach ($futures as $future) {
         
         // we will not wait for each result for more than 5 seconds
         $result = $future->get(5);
         
         // we loop over each result we get and store it in a data array
         foreach ($result as $row){
            // we need time in miliseconds and the value with 3 decimal precision
            $data[$row['probe_time']->time()*1000] = round($row['probe_value']->value(),3);
         }
      }
	
      return array('sensor' => $this->getSensorNameById($sensorId),$this->getSensorTypeById($sensorId) => $data );
   }
    
      
   // get a sensor name by a given sensor id 
   function getSensorNameById($sensorId){
       
      // prepare SQL statement
      $stmt = $this->mysqlConnection->prepare('select name from sensor where identifier = :sensor ');
		
      // bind variables and execute										
      $stmt->execute(array(':sensor' => $sensorId ));										
		
      // get the result
      $row = $stmt->fetch();
      
      return $row[0]; 
   }
    
    
   // get the sensor type by a given sensor id 
   function getSensorTypeById($sensorId){
       
      // prepare SQL statement
      $stmt = $this->mysqlConnection->prepare('select type from sensor where identifier = :sensor ');
		
      // bind variables and execute										
      $stmt->execute(array(':sensor' => $sensorId ));										
		
      // get the result
      $row = $stmt->fetch();

      return $row[0];   
   }
   
    
   // returns an array with all days for the given period from parameters
   function generateDayArray($year, $month=null, $day=null){
		 
      // create new empty array to hold all days
      $days = array();
 
      // we have just a year param
      if(is_null($day) and is_null($month)){

         $startday = \Carbon\Carbon::create($year)->startOfYear();
         $endday = \Carbon\Carbon::create($year)->lastOfYear();	
		
      // we have a year and a month set	 
      }elseif(is_null($day) and !is_null($month)){
		
         $startday = \Carbon\Carbon::create($year,$month)->firstOfMonth();
         $endday = \Carbon\Carbon::create($year,$month)->lastOfMonth();
		
      // we have all 3 params 
      }else{
         
         array_push($days, \Carbon\Carbon::create($year,$month,$day)->toDateString());
         return $days;	
      }	
		
      // iterate as long as the cur day is less or equals the end day
      while($startday->lt($endday)){
         array_push($days, $startday->toDateString());
         $startday = $startday->addDay();
      }	 
		 
      return $days;	 
	}

    
   // get all data from all sensor for the plot that has the given name
   function getDataByGraphName($graphName){
	    
	    
	    
	    
   }
    
    
    
   function getSensorsForGraphName($graphName){
		
      // prepare SQL statement
      $stmt = $this->mysqlConnection->prepare('select identifier from sensor s 
                                                   JOIN sensor2graph s2g on (s.sid = s2g.sensor)
                                                   JOIN graph g on (s2g.graph = g.gid)
                                                   where g.name = :name ');
      
      // bind variables and execute										
      $stmt->execute(array(':name' => $graphName ));										
		
      // return the result
      return $stmt->fetch();
       
   }
    
    
    
}

?>