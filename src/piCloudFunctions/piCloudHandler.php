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
    
   
   // function that creates a new sensor based on the arguments
   function createNewSensor($sensorId, $devIdent, $devName, $devType, $authToken){
      
      // prepare SQL statement
      $stmt = $this->mysqlConnection->prepare('insert into sensor (name, type, identifier, attached)
                                                   select :name, :type, :ident, device.did 
                                                   from device 
                                                   join user on (device.owner = user.uid)
                                                   where device.identifier = :devident and user.authToken = :token');
      // bind variables and execute										
      $result = $stmt->execute(array(':token' => $authToken, ':name' => $devName, ':type' => $devType, ':ident' => $sensorId,':devident' => $devIdent ));
      
      // if execution was successfull we return true
      if($result){
         return true;
      }	
     
      // otherwise return false
      return false;
   }
   
   
   // function that creates a new device based on the arguments
   function createNewDevice($devName, $devIdent, $authToken){
      
      // prepare SQL statement
      $stmt = $this->mysqlConnection->prepare('insert into device (name, identifier, owner) 
                                                select :name, :identifier, uid from user where authToken = :token');
                                                      
      // bind variables and execute
      $result = $stmt->execute(array(':token' => $authToken, ':name' => $devName, ':identifier' => $devIdent ));
      
      // if execution was successfull we return true								
      if($result){
         return true;
      }
      
      // otherwise return false
      return false;
   }
   
   

   
    
   // get data for sensorid and year
   function getDataBySensorYear($sensorId, $year){
	    
      $start = \Carbon\Carbon::create($year)->startOfYear();
      $end = \Carbon\Carbon::create($year)->endOfYear();

      // call function to generate json and return array back to user
      return $this->getJSONDataPointsAsync($sensorId, $start, $end);
   }
   
   // get data for sensorid and month
   function getDataBySensorMonth($sensorId, $year, $month){
	    
      // define start and end of the time window
      $start = \Carbon\Carbon::create($year,$month)->startOfMonth();
      $end = \Carbon\Carbon::create($year,$month)->endOfMonth();

      // call function to generate json and return array back to user
      return $this->getJSONDataPointsAsync($sensorId, $start, $end);
   }

   // get data for sensorid and specific day
   function getDataBySensorDay($sensorId, $year, $month, $day){
	   
	   // define start and end of the time window 
      $start = \Carbon\Carbon::create($year,$month,$day)->startOfDay();
      $end = \Carbon\Carbon::create($year,$month,$day)->endOfDay();
      
      // call function to generate json and return array back to user
      return $this->getJSONDataPointsAsync($sensorId, $start, $end);
   }

   
   
   // this function queries cassandra DB and returns a json
   function getJSONDataPointsAsync($sensorId, $starttime, $endtime){
		
      // prepare the SQL statement
      $statement = $this->cassandraConnection->prepare("SELECT probe_time, probe_value FROM sensordata WHERE sensor_id = ? and day = ? and probe_time >= ? and probe_time <= ?");
		
      // save original endtime  
      $original_endtime = clone $endtime;  
		
      // loop as long as starttime is lower than endtime  
      while($starttime->lt($original_endtime)){
         
         // if the endtime is on the same day as the new starttime we use the original time
         if( $original_endtime->isSameDay($starttime) ){
            $endtime = $original_endtime;
         }else{
            $endtime = clone $starttime;
            $endtime = $endtime->endOfDay();
         }
         
         
         // prepare values for cassandra execution option object
         $curday = $starttime->toDateString(); 
         $startvalue = new \Cassandra\Timestamp(strtotime($starttime->toDateTimeString()));
         $endvalue = new \Cassandra\Timestamp(strtotime($endtime->toDateTimeString()));
         
         // execute statement async and with exec option and hold result in array
         $futures[]= $this->cassandraConnection->executeAsync($statement, new \Cassandra\ExecutionOptions(array('arguments' => array($sensorId, $curday, $startvalue, $endvalue))));
         
         // set new start time
         $starttime = $starttime->addDay()->startOfDay();
      }
      
      // wait for all statements to complete
      foreach ($futures as $future) {
         
         // we will not wait for each result for more than 5 seconds
         $result = $future->get(5);
         
         // we loop over each result we get and store it in a data array
         foreach ($result as $row){
            // we need time in miliseconds and the value with 3 decimal precision
            $data[] = [$row['probe_time']->time() * 1000 , round($row['probe_value']->value(),3)];
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
   
   
    // get all data from all sensor for the plot that has the given name
   function getDataByGraphName($graphName){
	    
	   // prepare SQL statement
      $stmt = $this->mysqlConnection->prepare('select s.identifier, g.dataSinceDays from sensor s 
                                                   JOIN sensor2graph s2g on (s.sid = s2g.sensor)
                                                   JOIN graph g on (s2g.graph = g.gid)
                                                   where g.name = :name ');
      
      // bind variables and execute										
      $stmt->execute(array(':name' => $graphName ));										
		
		// create data array to hold results
		$data = array();
		       
      // loop over every returned row
      foreach ($stmt as $row) {		 
                  
         // the endtime for the query is now
         $endtime = \Carbon\Carbon::now();
         
         // the starttime for the query is the endtime minus the dataSinceDays from DB
         $starttime = clone $endtime;
         $starttime = $starttime->subDay($row[1]); 
         
         array_push($data, $this->getJSONDataPointsAsync($row[0], $starttime, $endtime));
         
      }
      return $data;    
   }
   
  
    
}

?>