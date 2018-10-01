<?php
/* SIMPLE SCRIPT FOR LOAD BALANCER HEALTH CHECK
* - Send http 204 when all working
* - Send http 503 if not
* Just add following line in your HAProxy Backend for accept only working webserver node
*               http-check expect status 204
*/

require('./config/config.php');
$port = "";
if (!empty($CONFIG["dbport"])){
        $port = ":".$CONFIG["dbport"];
}
try{
 $conn = new PDO($CONFIG["dbtype"].':host='.$CONFIG["dbhost"].$port.';dbname='.$CONFIG["dbname"], $CONFIG["dbuser"], $CONFIG["dbpassword"]);
 if($conn){
        header('HTTP/1.1 204');
        }
}catch (PDOException $e){
        header('HTTP/1.1 503');
}
?>
