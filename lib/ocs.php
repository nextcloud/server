<?php

/**
* ownCloud
*
* @author Frank Karlitschek
* @copyright 2010 Frank Karlitschek karlitschek@kde.org
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*
*/



/**
 * Class to handle open collaboration services API requests
 *
 */
class OC_OCS {

  /**
   * reads input date from get/post/cookies and converts the date to a special data-type
   *
   * @param variable $key
   * @param variable-type $type
   * @param priority $getpriority
   * @param default  $default
   * @return data
   */
  public static function readData($key,$type='raw',$getpriority=false,$default='') {
    if($getpriority) {
      if(isset($_GET[$key])) {
        $data=$_GET[$key];
      } elseif(isset($_POST[$key])) {
        $data=$_POST[$key];
      } else {
        if($default=='') {
          if(($type=='int') or ($type=='float')) $data=0; else $data='';
        } else {
          $data=$default;
        }
      }
    } else {
      if(isset($_POST[$key])) {
        $data=$_POST[$key];
      } elseif(isset($_GET[$key])) {
        $data=$_GET[$key];
      } elseif(isset($_COOKIE[$key])) {
        $data=$_COOKIE[$key];
      } else {
        if($default=='') {
          if(($type=='int') or ($type=='float')) $data=0; else $data='';
        } else {
          $data=$default;
        }
      }
    }

    if($type=='raw') return($data);
    elseif($type=='text') return(addslashes(strip_tags($data)));
    elseif($type=='int')  { $data = (int) $data; return($data); }
    elseif($type=='float')  { $data = (float) $data; return($data); }
    elseif($type=='array')  { $data = $data; return($data); }
  }


  /**
    main function to handle the REST request
  **/
  public static function handle() {

    // overwrite the 404 error page returncode
    header("HTTP/1.0 200 OK");


    if($_SERVER['REQUEST_METHOD'] == 'GET') {
       $method='get';
    }elseif($_SERVER['REQUEST_METHOD'] == 'PUT') {
       $method='put';
       parse_str(file_get_contents("php://input"),$put_vars);
    }elseif($_SERVER['REQUEST_METHOD'] == 'POST') {
       $method='post';
    }else{
      echo('internal server error: method not supported');
      exit();
    }

    // preprocess url
    $url=$_SERVER['REQUEST_URI'];
    if(substr($url,(strlen($url)-1))<>'/') $url.='/';
    $ex=explode('/',$url);
    $paracount=count($ex);

    // eventhandler
    // CONFIG
    // apiconfig - GET - CONFIG
    if(($method=='get') and (strtolower($ex[$paracount-3])=='v1.php') and (strtolower($ex[$paracount-2])=='config')){
      $format=OC_OCS::readdata('format','text');
      OC_OCS::apiconfig($format);

    // PERSON
    // personcheck - POST - PERSON/CHECK
    }elseif(($method=='post') and (strtolower($ex[$paracount-4])=='v1.php') and (strtolower($ex[$paracount-3])=='person') and  (strtolower($ex[$paracount-2])=='check')){
      $format=OC_OCS::readdata('format','text');
      $login=OC_OCS::readdata('login','text');
      $passwd=OC_OCS::readdata('password','text');
      OC_OCS::personcheck($format,$login,$passwd);

    // ACTIVITY
    // activityget - GET ACTIVITY   page,pagesize als urlparameter
    }elseif(($method=='get') and (strtolower($ex[$paracount-3])=='v1.php')and (strtolower($ex[$paracount-2])=='activity')){
      $format=OC_OCS::readdata('format','text');
      $page=OC_OCS::readdata('page','int');
      $pagesize=OC_OCS::readdata('pagesize','int');
      if($pagesize<1 or $pagesize>100) $pagesize=10;
      OC_OCS::activityget($format,$page,$pagesize);

    // activityput - POST ACTIVITY
    }elseif(($method=='post') and (strtolower($ex[$paracount-3])=='v1.php')and (strtolower($ex[$paracount-2])=='activity')){
      $format=OC_OCS::readdata('format','text');
      $message=OC_OCS::readdata('message','text');
      OC_OCS::activityput($format,$message);

    // PRIVATEDATA
    // get - GET DATA
    }elseif(($method=='get') and (strtolower($ex[$paracount-4])=='v1.php')and (strtolower($ex[$paracount-2])=='getattribute')){
      $format=OC_OCS::readdata('format','text');
      OC_OCS::privateDataGet($format);

    }elseif(($method=='get') and (strtolower($ex[$paracount-5])=='v1.php')and (strtolower($ex[$paracount-3])=='getattribute')){
      $format=OC_OCS::readdata('format','text');
      $app=$ex[$paracount-2];
      OC_OCS::privateDataGet($format, $app);
	}elseif(($method=='get') and (strtolower($ex[$paracount-6])=='v1.php')and (strtolower($ex[$paracount-4])=='getattribute')){
      $format=OC_OCS::readdata('format','text');
      $key=$ex[$paracount-2];
      $app=$ex[$paracount-3];
      OC_OCS::privateDataGet($format, $app,$key);

    // set - POST DATA
    }elseif(($method=='post') and (strtolower($ex[$paracount-6])=='v1.php')and (strtolower($ex[$paracount-4])=='setattribute')){
      $format=OC_OCS::readdata('format','text');
      $key=$ex[$paracount-2];
      $app=$ex[$paracount-3];
      $value=OC_OCS::readdata('value','text');
      OC_OCS::privatedataset($format, $app, $key, $value);
    // delete - POST DATA
    }elseif(($method=='post') and (strtolower($ex[$paracount-6])=='v1.php')and (strtolower($ex[$paracount-4])=='deleteattribute')){
      $format=OC_OCS::readdata('format','text');
      $key=$ex[$paracount-2];
      $app=$ex[$paracount-3];
      OC_OCS::privatedatadelete($format, $app, $key);

    }else{
      $format=OC_OCS::readdata('format','text');
      $txt='Invalid query, please check the syntax. API specifications are here: http://www.freedesktop.org/wiki/Specifications/open-collaboration-services. DEBUG OUTPUT:'."\n";
      $txt.=OC_OCS::getdebugoutput();
      echo(OC_OCS::generatexml($format,'failed',999,$txt));
    }
    exit();
  }

  /**
   * generated some debug information to make it easier to find faild API calls
   * @return debug data string
   */
  private static function getDebugOutput() {
    $txt='';
    $txt.="debug output:\n";
    if(isset($_SERVER['REQUEST_METHOD'])) $txt.='http request method: '.$_SERVER['REQUEST_METHOD']."\n";
    if(isset($_SERVER['REQUEST_URI'])) $txt.='http request uri: '.$_SERVER['REQUEST_URI']."\n";
    if(isset($_GET)) foreach($_GET as $key=>$value) $txt.='get parameter: '.$key.'->'.$value."\n";
    if(isset($_POST)) foreach($_POST as $key=>$value) $txt.='post parameter: '.$key.'->'.$value."\n";
    return($txt);
  }

  /**
   * checks if the user is authenticated
   * checks the IP whitlist, apikeys and login/password combination
   * if $forceuser is true and the authentication failed it returns an 401 http response.
   * if $forceuser is false and authentification fails it returns an empty username string
   * @param bool $forceuser
   * @return username string
   */
  private static function checkPassword($forceuser=true) {
    //valid user account ?
    if(isset($_SERVER['PHP_AUTH_USER'])) $authuser=$_SERVER['PHP_AUTH_USER']; else $authuser='';
    if(isset($_SERVER['PHP_AUTH_PW']))   $authpw=$_SERVER['PHP_AUTH_PW']; else $authpw='';

    if(empty($authuser)) {
      if($forceuser){
        header('WWW-Authenticate: Basic realm="your valid user account or api key"');
        header('HTTP/1.0 401 Unauthorized');
        exit;
      }else{
        $identifieduser='';
      }
    }else{
      if(!OC_User::login($authuser,$authpw)){
        if($forceuser){
          header('WWW-Authenticate: Basic realm="your valid user account or api key"');
          header('HTTP/1.0 401 Unauthorized');
          exit;
        }else{
          $identifieduser='';
        }
      }else{
        $identifieduser=$authuser;
      }
    }

    return($identifieduser);
  }


  /**
   * generates the xml or json response for the API call from an multidimenional data array.
   * @param string $format
   * @param string $status
   * @param string $statuscode
   * @param string $message
   * @param array $data
   * @param string $tag
   * @param string $tagattribute
   * @param int $dimension
   * @param int $itemscount
   * @param int $itemsperpage
   * @return string xml/json
   */
  private static function generateXml($format,$status,$statuscode,$message,$data=array(),$tag='',$tagattribute='',$dimension=-1,$itemscount='',$itemsperpage='') {
    if($format=='json') {

      $json=array();
      $json['status']=$status;
      $json['statuscode']=$statuscode;
      $json['message']=$message;
      $json['totalitems']=$itemscount;
      $json['itemsperpage']=$itemsperpage;
      $json['data']=$data;
      return(json_encode($json));


    }else{
      $txt='';
      $writer = xmlwriter_open_memory();
      xmlwriter_set_indent( $writer, 2 );
      xmlwriter_start_document($writer );
      xmlwriter_start_element($writer,'ocs');
      xmlwriter_start_element($writer,'meta');
      xmlwriter_write_element($writer,'status',$status);
      xmlwriter_write_element($writer,'statuscode',$statuscode);
      xmlwriter_write_element($writer,'message',$message);
      if($itemscount<>'') xmlwriter_write_element($writer,'totalitems',$itemscount);
      if(!empty($itemsperpage)) xmlwriter_write_element($writer,'itemsperpage',$itemsperpage);
      xmlwriter_end_element($writer);
      if($dimension=='0') {
        // 0 dimensions
        xmlwriter_write_element($writer,'data',$data);

      }elseif($dimension=='1') {
        xmlwriter_start_element($writer,'data');
        foreach($data as $key=>$entry) {
          xmlwriter_write_element($writer,$key,$entry);
        }
        xmlwriter_end_element($writer);

      }elseif($dimension=='2') {
        xmlwriter_start_element($writer,'data');
        foreach($data as $entry) {
          xmlwriter_start_element($writer,$tag);
          if(!empty($tagattribute)) {
            xmlwriter_write_attribute($writer,'details',$tagattribute);
          }
          foreach($entry as $key=>$value) {
            if(is_array($value)){
              foreach($value as $k=>$v) {
                xmlwriter_write_element($writer,$k,$v);
              }
            } else {
              xmlwriter_write_element($writer,$key,$value);
            }
          }
          xmlwriter_end_element($writer);
        }
        xmlwriter_end_element($writer);

      }elseif($dimension=='3') {
        xmlwriter_start_element($writer,'data');
        foreach($data as $entrykey=>$entry) {
          xmlwriter_start_element($writer,$tag);
          if(!empty($tagattribute)) {
            xmlwriter_write_attribute($writer,'details',$tagattribute);
          }
          foreach($entry as $key=>$value) {
            if(is_array($value)){
              xmlwriter_start_element($writer,$entrykey);
              foreach($value as $k=>$v) {
                xmlwriter_write_element($writer,$k,$v);
              }
              xmlwriter_end_element($writer);
            } else {
              xmlwriter_write_element($writer,$key,$value);
            }
          }
          xmlwriter_end_element($writer);
        }
        xmlwriter_end_element($writer);
      }elseif($dimension=='dynamic') {
        xmlwriter_start_element($writer,'data');
        OC_OCS::toxml($writer,$data,'comment');
        xmlwriter_end_element($writer);
      }

      xmlwriter_end_element($writer);

      xmlwriter_end_document( $writer );
      $txt.=xmlwriter_output_memory( $writer );
      unset($writer);
      return($txt);
    }
  }

  public static function toXml($writer,$data,$node) {
    foreach($data as $key => $value) {
      if (is_numeric($key)) {
        $key = $node;
      }
      if (is_array($value)){
        xmlwriter_start_element($writer,$key);
        OC_OCS::toxml($writer,$value,$node);
        xmlwriter_end_element($writer);
      }else{
        xmlwriter_write_element($writer,$key,$value);
      }

    }
  }




  /**
   * return the config data of this server
   * @param string $format
   * @return string xml/json
   */
  private static function apiConfig($format) {
    $user=OC_OCS::checkpassword(false);
    $url=substr(OC_Helper::serverHost().$_SERVER['SCRIPT_NAME'],0,-11).'';

    $xml['version']='1.5';
    $xml['website']='ownCloud';
    $xml['host']=OC_Helper::serverHost();
    $xml['contact']='';
    $xml['ssl']='false';
    echo(OC_OCS::generatexml($format,'ok',100,'',$xml,'config','',1));
  }


  /**
   * check if the provided login/apikey/password is valid
   * @param string $format
   * @param string $login
   * @param string $passwd
   * @return string xml/json
   */
  private static function personCheck($format,$login,$passwd) {
    if($login<>''){
      if(OC_User::login($login,$passwd)){
        $xml['person']['personid']=$login;
        echo(OC_OCS::generatexml($format,'ok',100,'',$xml,'person','check',2));
      }else{
        echo(OC_OCS::generatexml($format,'failed',102,'login not valid'));
      }
    }else{
      echo(OC_OCS::generatexml($format,'failed',101,'please specify all mandatory fields'));
    }
  }



  // ACTIVITY API #############################################

  /**
   * get my activities
   * @param string $format
   * @param string $page
   * @param string $pagesize
   * @return string xml/json
   */
  private static function activityGet($format,$page,$pagesize) {
    $user=OC_OCS::checkpassword();
    
	//TODO

    $txt=OC_OCS::generatexml($format,'ok',100,'',$xml,'activity','full',2,$totalcount,$pagesize);
    echo($txt);
  }

  /**
   * submit a activity
   * @param string $format
   * @param string $message
   * @return string xml/json
   */
  private static function activityPut($format,$message) {
    // not implemented in ownCloud
    $user=OC_OCS::checkpassword();
    echo(OC_OCS::generatexml($format,'ok',100,''));
  }

  // PRIVATEDATA API #############################################

  /**
   * get private data and create the xml for ocs
   * @param string $format
   * @param string $app
   * @param string $key
   * @return string xml/json
   */
  private static function privateDataGet($format,$app="",$key="") {
    $user=OC_OCS::checkpassword();
	$result=OC_OCS::getData($user,$app,$key);
    $xml=array();
    foreach($result as $i=>$log) {
      $xml[$i]['key']=$log['key'];
      $xml[$i]['app']=$log['app'];
      $xml[$i]['value']=$log['value'];
    }


    $txt=OC_OCS::generatexml($format, 'ok', 100, '', $xml, 'privatedata', 'full', 2, count($xml), 0);//TODO: replace 'privatedata' with 'attribute' once a new libattice has been released that works with it
    echo($txt);
  }

  /**
   * set private data referenced by $key to $value and generate the xml for ocs
   * @param string $format
   * @param string $app
   * @param string $key
   * @param string $value
   * @return string xml/json
   */
	private static function privateDataSet($format, $app, $key, $value) {
		$user=OC_OCS::checkpassword();
		if(OC_OCS::setData($user,$app,$key,$value)){
			echo(OC_OCS::generatexml($format,'ok',100,''));
		}
	}

	/**
	* delete private data referenced by $key and generate the xml for ocs
	* @param string $format
	* @param string $app
	* @param string $key
	* @return string xml/json
	*/
	private static function privateDataDelete($format, $app, $key) {
		if($key=="" or $app==""){
			return; //key and app are NOT optional here
		}
		$user=OC_OCS::checkpassword();
		if(OC_OCS::deleteData($user,$app,$key)){
			echo(OC_OCS::generatexml($format,'ok',100,''));
		}
	}
	
	/**
	* get private data
	* @param string $user
	* @param string $app
	* @param string $key
	* @param bool $like use LIKE instead of = when comparing keys
	* @return array
	*/
	public static function getData($user,$app="",$key="") {
		if($app){
			$apps=array($app);
		}else{
			$apps=OC_Preferences::getApps($user);
		}
		if($key){
			$keys=array($key);
		}else{
			foreach($apps as $app){
				$keys=OC_Preferences::getKeys($user,$app);
			}
		}
		$result=array();
		foreach($apps as $app){
			foreach($keys as $key){
				$value=OC_Preferences::getValue($user,$app,$key);
				$result[]=array('app'=>$app,'key'=>$key,'value'=>$value);
			}
		}
		return $result;
	}

	/**
	* set private data referenced by $key to $value
	* @param string $user
	* @param string $app
	* @param string $key
	* @param string $value
	* @return bool
	*/
	public static function setData($user, $app, $key, $value) {
		return OC_Preferences::setValue($user,$app,$key,$value);
	}

	/**
	* delete private data referenced by $key
	* @param string $user
	* @param string $app
	* @param string $key
	* @return string xml/json
	*/
	public static function deleteData($user, $app, $key) {
		return OC_Preferences::deleteKey($user,$app,$key);
	}
}
