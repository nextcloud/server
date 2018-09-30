<?php
/* SIMPLE SCRIPT FOR LOAD BALANCER HEALTH CHECK
* - Send http 204 when all working
* - Send http 503 if not
* Just add following line in your HAProxy Backend for accept only working webserver node
*		http-check expect status 204
*/

require('./config/config.php');
$port = "";
if (!empty($CONFIG["dbport"])){
        $port = ":".$CONFIG["dbport"];
}
$dsn = $CONFIG["dbtype"].':host='.$CONFIG["dbhost"].$port.';dbname='.$CONFIG["dbname"];

try{
 // create a PDO connection with the configuration data
 $conn = new PDO($dsn, $CONFIG["dbuser"], $CONFIG["dbpassword"]);
 
 // display a message if connected to database successfully
 if($conn){
        header('HTTP/1.1 204');
        }
}catch (PDOException $e){
                // report error message
        echo '<html><head><title>503 Service Unavailable</title></head><body><h1>503 Service Unavailable</h1></body></html>'; header('HTTP/1.1 503');
}
?>
