<?php
class OC_CONFIG{
   /**
   * show the configform
   *
   */
  public static function showconfigform(){
    global $CONFIG_ADMINLOGIN;
    global $CONFIG_ADMINPASSWORD;
    global $CONFIG_DATADIRECTORY;
    global $CONFIG_HTTPFORCESSL;
    global $CONFIG_DATEFORMAT;
    global $CONFIG_DBNAME;
    require('templates/configform.php');
  }

  /**
   * lisen for configuration changes and write it to the file
   *
   */
  public static function writeconfiglisener(){
    global $DOCUMENTROOT;
    global $SERVERROOT;
    global $WEBROOT;
    global $CONFIG_DBNAME;
    if(isset($_POST['set_config'])){

      //checkdata
      $error='';
      $FIRSTRUN=isset($CONFIG_ADMINLOGIN);
      if(!$FIRSTRUN){
         if($_POST['currentpassword']!=$CONFIG_ADMINPASSWORD){
            $error.='wrong password';
         }
      }
      
      if(!isset($_POST['adminlogin'])        or empty($_POST['adminlogin']))        $error.='admin login not set<br />';
      if(!isset($_POST['adminpassword'])     or empty($_POST['adminpassword']) and $FIRSTRUN)     $error.='admin password not set<br />';
      if(!isset($_POST['adminpassword2'])    or empty($_POST['adminpassword2']) and $FIRSTRUN)    $error.='retype admin password not set<br />';
      if(!isset($_POST['datadirectory'])     or empty($_POST['datadirectory']))     $error.='data directory not set<br />';
      if(!isset($_POST['dateformat'])        or empty($_POST['dateformat']))        $error.='dateformat not set<br />';
      if(!isset($_POST['dbname'])            or empty($_POST['dbname']))            $error.='databasename not set<br />';
      if($_POST['adminpassword']<>$_POST['adminpassword2'] )                        $error.='admin passwords are not the same<br />';
      
       if(!isset($_POST['adminpassword']) or empty($_POST['adminpassword']) and !$FIRSTRUN){
          $_POST['adminpassword']=$CONFIG_ADMINPASSWORD;
       }
      $dbtype=$_POST['dbtype'];
      if($dbtype=='mysql'){
          if(!isset($_POST['dbhost'])            or empty($_POST['dbhost']))            $error.='database host not set<br />';
          if(!isset($_POST['dbuser'])            or empty($_POST['dbuser']))            $error.='database user not set<br />';
          if($_POST['dbpassword']<>$_POST['dbpassword2'] )                        $error.='database passwords are not the same<br />';
          
      }
      if(empty($error)) {
        //create/fill database
        $CONFIG_DBNAME=$_POST['dbname'];
        if(isset($_POST['filldb'])){
//            self::filldatabase();
        }
      
        //storedata
        $config='<?php '."\n";
        $config.='$CONFIG_ADMINLOGIN=\''.$_POST['adminlogin']."';\n";
        $config.='$CONFIG_ADMINPASSWORD=\''.$_POST['adminpassword']."';\n";
        $config.='$CONFIG_DATADIRECTORY=\''.$_POST['datadirectory']."';\n";
        if(isset($_POST['forcessl'])) $config.='$CONFIG_HTTPFORCESSL=true'.";\n"; else $config.='$CONFIG_HTTPFORCESSL=false'.";\n";
        $config.='$CONFIG_DATEFORMAT=\''.$_POST['dateformat']."';\n";
        $config.='$CONFIG_DBTYPE=\''.$dbtype."';\n";
        $config.='$CONFIG_DBNAME=\''.$_POST['dbname']."';\n";
        if($dbtype=='mysql'){
            $config.='$CONFIG_DBHOST=\''.$_POST['dbhost']."';\n";
            $config.='$CONFIG_DBUSER=\''.$_POST['dbuser']."';\n";
            $config.='$CONFIG_DBPASSWORD=\''.$_POST['dbpassword']."';\n";
        }
        $config.='?> ';

        $filename=$SERVERROOT.'/config/config.php';
        file_put_contents($filename,$config);

        header("Location: ".$WEBROOT."/");

      }
      return($error);

    }

  }
  
   /**
   * Fills the database with the initial tables
   * Note: while the AUTO_INCREMENT function is not supported by SQLite
   *    the same effect can be achieved by accessing the SQLite pseudo-column
   *    "rowid"
   */
   private static function filldatabase(){
      $query="CREATE TABLE 'locks' (
  'token' VARCHAR(255) NOT NULL DEFAULT '',
  'path' varchar(200) NOT NULL DEFAULT '',
  'expires' int(11) NOT NULL DEFAULT '0',
  'owner' varchar(200) DEFAULT NULL,
  'recursive' int(11) DEFAULT '0',
  'writelock' int(11) DEFAULT '0',
  'exclusivelock' int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY ('token'),
  UNIQUE ('token')
 );

CREATE TABLE 'log' (
  'timestamp' int(11) NOT NULL,
  'user' varchar(250) NOT NULL,
  'type' int(11) NOT NULL,
  'message' varchar(250) NOT NULL
);


CREATE TABLE  'properties' (
  'path' varchar(255) NOT NULL DEFAULT '',
  'name' varchar(120) NOT NULL DEFAULT '',
  'ns' varchar(120) NOT NULL DEFAULT 'DAV:',
  'value' text,
  PRIMARY KEY ('path','name','ns')
);";
      OC_DB::multiquery($query);
   }
}
?>


