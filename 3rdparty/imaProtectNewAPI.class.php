<?php
class imaProtectNewAPI {
	
	const BASE_URL='https://www.imaprotect.com/fr/';
	
	private $id;
	
	//username of ima protect account
	private $username;
	
	//password of ima protect account
	private $password;
  
  	//private key of contact
  	private $pkContact;
	
	//Expiration date for sessionID
  	private $expireImaCookie;
  
  
	private $imainternational;
	
	//Cookies
	private $TS013a2ec2;
	private $TS0192ac0d;
	
	//Token for updating status
	private $statusToken;
	
	//Token for get, post and delete pictures
	private $captureToken;
	
		
	public function __construct($username,$password,$pkContact,$id) {
        log::add('imaProtect', 'debug', "			==> constructor of class imaProtectAPi - Start");
		$this->id=$id;
		$this->username = $username;
		$this->password = $password;
      	$this->pkContact= $pkContact;
		$this->expireImaCookie=null;
		$this->imainternational=null;
		$this->TS013a2ec2=null;
		$this->TS0192ac0d=null;
		$this->statusToken=null;
		$this->captureToken=null;
	}


	public function __destruct()  {
	
	}
   
	//Execute all https request to Ima protect API
	private function doRequest($url, $data, $method, $headers) {		
      	log::add('imaProtect', 'debug', "			==> doRequest");
      	log::add('imaProtect', 'debug', "				==> Params : $url | $data | $method | ".json_encode($headers));
      	log::add('imaProtect', 'debug', "				==> Params json input : " . json_encode($data));
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL,				$url);
      	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($curl, CURLOPT_HEADER, 			true);
      
      
		//voir la gestion de $cookie
		switch($method)  {
			case "GET":
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 	true);
				curl_setopt($curl, CURLOPT_HTTPHEADER, 		$headers);
				break;
			case "POST":
          	case "DELETE":
            	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_ENCODING, "");
                curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
                curl_setopt($curl, CURLOPT_TIMEOUT, 0);
                curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
				break;
		}
				
		$resultCurl = curl_exec($curl);
		
		//Get http response code
        $httpRespCode  = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		
		//Get header info
		$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
      	$header = substr($resultCurl, 0, $header_size);
		
		//Get body
		$body = substr($resultCurl, $header_size);
		
		//close curl
      	curl_close($curl);
      
      	if ($method == "POST") {
			$this->getCookiesFromPostRequest($header);
        }
		
      	log::add('imaProtect', 'debug', "				==> Response");
      	log::add('imaProtect', 'debug', "					# Code Http : $httpRespCode");
      	log::add('imaProtect', 'debug', "					# Response  : ".$resultCurl);
      	log::add('imaProtect', 'debug', "					# Body  : ".$body);
      	log::add('imaProtect', 'debug', "					# Header  : ".$header);
				
		return array($httpRespCode, $body);
	}

	private function getCookiesFromPostRequest($header) {
		preg_match_all('/^Set-Cookie:\s*(.*?);(.*?);/mi', $header, $matches);
		
		$int=0;
		foreach($matches[1] as $item) {
          parse_str($item, $id);
		  if ($int == 0) {
			$this->imainternational=$id['imainternational'];
		  }
		  if ($int == 3) {
		   $this->TS013a2ec2=$id['TS013a2ec2'];
		  }
		  if ($int == 4) {
		   $this->TS0192ac0d=$id['TS0192ac0d'];
		  }
		  ++$int;
		}
		
		$int=0;
		foreach($matches[2] as $item) {
		  parse_str($item, $id);
		  if ($int == 0) {
			$this->expireImaCookie=$id['expires'];
		  }
		  ++$int;
		}
      	
      	log::add('imaProtect', 'debug', '				==> Recover cookies : '. $this->imainternational .'|'. $this->TS013a2ec2 .'|'. $this->TS0192ac0d .'|'.$this->expireImaCookie);

	}

	private function setHeaders()   {				
		
		$headers = array();
		$headers[] = "Referer: https://www.imaprotect.com/fr/client/";
		$headers[] = "Accept: application/json, text/plain, *\/*";
		//$headers[] = "Content-Type:application/json";
		
		if (isset($this->imainternational) && !empty($this->imainternational) && isset($this->TS013a2ec2) && !empty($this->TS013a2ec2) && isset($this->TS0192ac0d) && !empty($this->TS0192ac0d)) {
			$headers[] = "imainternational: ".$this->imainternational;
			$headers[] = "TS013a2ec2: ".$this->TS013a2ec2;		
			$headers[] = "TS0192ac0d: ".$this->TS0192ac0d;
			$headers[] = sprintf('Cookie: TS013a2ec2=%s;TS0192ac0d=%s;imainternational=%s', $this->TS013a2ec2,$this->TS0192ac0d,$this->imainternational);
		}
		return $headers;
	}	

	private function setParams($request,$pwd) {			//Set params for https request to Verisure Cloud
		
		switch($request)  {
			case "LOGIN":
				$params = array( '_username' => $this->username, '_password' => $this->password );
				break;
          	case "ALARM_OFF":
            	$params = array('status' => 'off','token' => $this->$statusToken);
            	break;
			case "ALARM_ON":
            	$params = array('status' => 'on','token' => $this->$statusToken);
            	break;
			case "ALARM_PARTIAL":
            	$params = array('status' => 'partial','token' => $this->$statusToken);
            	break;
		}
		$params_string = http_build_query($params);
		return $params_string;
    }
	
	private function cookieIsValid($cookieExpiredDate){
		if (isset($cookieExpiredDate)) {
			$diff=round(strtotime($cookieExpiredDate)-time(),1);
			if ($diff > 10) {
				log::add('imaProtect', 'debug', "				* sessionID is valid");
				return true;
			} else {
				log::add('imaProtect', 'debug', "				* sessionID is expired");
				return false;
			}
		} else {
			log::add('imaProtect', 'debug', "			==> Expiration of sessionID is missing");
			return false;
		}
	}

	private function storeContextToTmpFile($contextArray){
		log::add('imaProtect', 'debug', "			==> storeContextToTmpFile : " . json_encode($contextArray));
		if(isset($contextArray)){
			if (isset($this->id)) {
				$tmpFile=sys_get_temp_dir()."/imaProtect_session_".$this->id;
				$fd=fopen($tmpFile, "w");
				fputs($fd,json_encode($contextArray));
				fclose($fd);
			} else {
				//ToDo Log error
				log::add('imaProtect', 'debug', "			==> Equipment ID null ... impossible to follow !!!");
				return false;
			}
		} else {
			log::add('imaProtect', 'debug', "			==> No datas send to store in temporary file !!!");
			return false;
		}
	}

	//Recover datas from config file
	public function getContextFromTmpFile(){
      	log::add('imaProtect', 'debug', "			==> getContextFromTmpFile");
		if (isset($this->id)) {
          	$tmpFile=sys_get_temp_dir()."/imaProtect_session_".$this->id;
			if (is_file($tmpFile)) {
				$fd=fopen($tmpFile, "r");
              	$readLine=fgets($fd);

				if (isset($readLine)) {
                  	log::add('imaProtect', 'debug', "			==> Read config file .. datas $readLine");
					$arrayDecode=json_decode($readLine,true);
                  
                  	//check if temp fil is OK
                  	if ($this->IsNullOrEmpty($arrayDecode["expireImaCookie"]) || $this->IsNullOrEmpty($arrayDecode["imainternational"]) || $this->IsNullOrEmpty($arrayDecode["TS013a2ec2"]) || $this->IsNullOrEmpty($arrayDecode["TS0192ac0d"]) || $this->IsNullOrEmpty($arrayDecode["statusToken"]) || $this->IsNullOrEmpty($arrayDecode["captureToken"])) {
                      log::add('imaProtect', 'debug', "			==> No all datas read in temporary file !!!");
                      return false;
                    }
					
					if (isset($arrayDecode["expireImaCookie"])) {
						$this->expireImaCookie=$arrayDecode["expireImaCookie"];
					}
					
					if (isset($arrayDecode["imainternational"])) {
							$this->imainternational=$arrayDecode["imainternational"];
					}
					
					if (isset($arrayDecode["TS013a2ec2"])) {
							$this->TS013a2ec2=$arrayDecode["TS013a2ec2"];
					}
					
					if (isset($arrayDecode["TS0192ac0d"])) {
							$this->TS0192ac0d=$arrayDecode["TS0192ac0d"];
					}
					
					if (isset($arrayDecode["statusToken"])) {
							$this->statusToken=$arrayDecode["statusToken"];
					}
					
					if (isset($arrayDecode["captureToken"])) {
						$this->captureToken=$arrayDecode["captureToken"];
					}
	
                  	return $this->cookieIsValid($this->expireImaCookie);
				} else {
					log::add('imaProtect', 'debug', "			==> No datas read in temporary file !!!");
					return false;
				}
              	fclose($fd);
				return true;
			} else {
				log::add('imaProtect', 'debug', "			==> No config file ... init to call");
				return false;
			}
		} else {
			log::add('imaProtect', 'debug', "			==> Equipment ID null ... impossible to follow !!!");
			return false;
		}
	}
  
  	private function manageErrorMessage($httpCode,$error) {
      	log::add('imaProtect', 'debug', "			Function manageErrorMessage : " . $error . "|" .$httpCode);
      	$errorMessage="Unknown error";
		if (!$this->IsNullOrEmpty($error)) {
			$errorMessage=$error;
			$errorArray=json_decode($error,true);
			if (!$this->IsNullOrEmpty($errorArray["localizable_title"])) {
				$errorMessage=$errorArray["localizable_title"];
				if (!$this->IsNullOrEmpty($errorArray["localizable_description"])) {
					$errorMessage.=  " ==> " . $errorArray["localizable_description"];
				}
				if (!$this->IsNullOrEmpty($errorArray["error_code"])) {
					$errorMessage.= "(return code : " .$errorArray["error_code"] . ")";
				}
			}
		}
		
		if (!$this->IsNullOrEmpty($httpCode)) {
			$errorMessage .= "(". $httpCode . ")";
		}
		
      	log::add('imaProtect', 'debug', "			==> errorMessage : " . $errorMessage);
      	return $errorMessage;
    }
  
  	private function IsNullOrEmpty($input){
    	return (!isset($input) || trim($input)==='');
	}
  
  	private function getHeadersLogin() {
      	$headers = array();
		$headers[] = "Referer: https://www.imaprotect.com";
		$headers[] = "Content-Type: application/x-www-form-urlencoded";
      	return $headers;
    }

  //Log to IMA Account
	public function Login()  {		
		log::add('imaProtect', 'debug', "			==> Login ");
		list($httpcode, $result) = $this->doRequest(self::BASE_URL.'client/login_check',$this->setParams("LOGIN",null), "POST", $this->getHeadersLogin());
      	if (isset($httpcode) and $httpcode >= 400 ) {
          	throw new Exception($this->manageErrorMessage($httpcode,$result));
        }
	}
	
	//Get IMA Tokens for futur actions
	public function getTokens() {
      	list($httpcode, $result) = $this->doRequest(self::BASE_URL.'client/management',"", "GET", $this->setHeaders());
      
      	if (isset($httpcode) and $httpcode >= 400 ) {
			throw new Exception($result);
        } else {
			$this->retrieveIMATokens($result);			
			$contextArray =	array(
				"expireImaCookie" => $this->expireImaCookie,
				"imainternational" => $this->imainternational,
				"TS013a2ec2" =>  $this->TS013a2ec2,
				"TS0192ac0d" => $this->TS0192ac0d,
				"statusToken" => $this->statusToken,
				"captureToken"=> $this->captureToken
			);
			$this->storeContextToTmpFile($contextArray);
						
			return true;
        }		
	}


	private function retrieveIMATokens($result) {
		$array = array();
		preg_match( '/Alarm-status ref="myAlarm" data-token="([^"]*)"/i', $result, $array ) ;
		$alarmToken=$array[1];
		$this->statusToken=$array[1];
						
		$array = array();
		preg_match( '/Capture-list ref="myCapture" data-token="([^"]*)"/i', $result, $array ) ;
		$this->captureToken=$array[1];
	}


  //Get alarm status
	public function getAlarmStatus() {
      	log::add('imaProtect', 'debug', "			==> getAlarmStatus ");
		list($httpcode, $result) = $this->doRequest(self::BASE_URL.'client/management/status',"", "GET",  $this->setHeaders());
      
      	if (isset($httpcode) and $httpcode >= 400 ) {
          	throw new Exception($this->manageErrorMessage($httpcode,$result));
        } else {
      		return json_decode($result,true);
        }
	}
	
	public function getContactList(){   
		log::add('imaProtect', 'debug', "			==> getContactList ");
		list($httpcode, $result) = $this->doRequest(self::BASE_URL.'client/contact/list',"", "GET",  $this->setHeaders());
      
      	if (isset($httpcode) and $httpcode >= 400 ) {
          	throw new Exception($this->manageErrorMessage($httpcode,$result));
        } else {
      		return json_decode($result,true);
        }
	}
  
  /*
  //Get IMA other info like room id
	public function getOtherInfo() {
		log::add('imaProtect', 'debug', "			==> getOtherInfo ");
		$urlOtherInfo="https://pilotageadistance.imateleassistance.com/proxy/api/1.0/hss/". $this->pk . "/?_=".time()."000";
		$method = "GET";
		$headers = $this->setHeaders();
      	list($httpcode, $result) = $this->doRequest($urlOtherInfo,"", $method, $headers);
      
      	if (isset($httpcode) and $httpcode >= 400 ) {
			throw new Exception($result);
        } else {
			$roomsInfo=$this->readResponseForGetOtherInfo($result);
			if(isset($roomsInfo)) {
				$this->rooms=$roomsInfo;
			     $contextArray =	array(
                    "sessionID" => $this->sessionID,
                    "sessionIDExpires" => $this->sessionIDExpires,
                    "xcsrfToken" =>  $this->xcsrfToken,
                    "xcsrfTokenExpires" => $this->xcsrfTokenExpires,
                    "pk" => $this->pk,
					"rooms"=> $this->rooms
                );
                $this->storeContextToTmpFile($contextArray);
				
                return true;
            } else {
				throw new Exception("Error extracting rooms informations");
			}
        }
	}
    */
	/*
	//Recover pk of rooms
	private function readResponseForGetOtherInfo($result) {
		log::add('imaProtect', 'debug', "				==> readResponseForGetOtherInfo - Start");
		$response= array();
		$resultArr=json_decode($result,true);
		
		foreach($resultArr as $event) {
          foreach($event as $key=>$value){
            if ($key == "fields") {
				foreach($value as $detailEventkey=>$detailEventValue) {
					if ($detailEventkey == "device_set") {
						foreach($detailEventValue as $equipmentKey=>$equipmentValue) {
							log::add('imaProtect', 'debug', "					==> name : " .$equipmentValue["fields"]["name"] . "| pk : " .$equipmentValue["pk"]);
							array_push($response,array("room"=>$equipmentValue["fields"]["name"],"pk"=>$equipmentValue["pk"]));
						}
						
					}
				  }     
            }
          }	
        }
		log::add('imaProtect', 'debug', "				==> readResponseForGetOtherInfo - End -> response :  ".json_encode($response));
		return $response;
	}
	*/
	
	/*
	//Get IMA info in order to retrieve id psk of installation and other informations	
	public function getIMAAccountInfo(){
      	log::add('imaProtect', 'debug', "			==> getIMAAccountInfo ");
		$urlImaAccount="https://pilotageadistance.imateleassistance.com/proxy/api/1.0/hss/me/?_=".time()."000";
		$method = "GET";
		$headers = $this->setHeaders();
      	list($httpcode, $result) = $this->doRequest($urlImaAccount,"", $method, $headers);
      
      	if (isset($httpcode) and $httpcode >= 400 ) {
			throw new Exception($this->manageErrorMessage($httpcode,$result));
        } else {
			$this->pk=($this->getPK($result));
			if (!isset($this->pk)) {
              	log::add('imaProtect', 'debug', "				# key pk empty");
				throw new Exception("key pk empty");
            }
          	
        }	
	}
	*/
  
  /*
  	private function getPK($input) {
      log::add('imaProtect', 'debug', "			==> getPK - input : $input");
      $resultArr=json_decode($input,true);
      
      //ToDo ==> code à améliorer
      $pk=$resultArr[0]["fields"]["contract_set"][0]["fields"]["site"]["fields"]["hss_pk"];
      $brandLogo=$resultArr[0]["fields"]["contract_set"][0]["fields"]["site"]["fields"]["branding_logo_url"];
      $brandName=$resultArr[0]["fields"]["contract_set"][0]["fields"]["site"]["fields"]["partner_name"];
      //$userAdr=[0]["fields"]["contract_set"][0]["fields"]["site"]["fields"]["address_1"] . " " . [0]["fields"]["contract_set"][0]["fields"]["site"]["fields"]["city_name"] . " " . [0]["fields"]["contract_set"][0]["fields"]["site"]["fields"]["address_1"] . " ( " . [0]		["fields"]["contract_set"][0]["fields"]["site"]["fields"]["postal_code"];
      log::add('imaProtect', 'debug', "			    ## pk : $pk");
      log::add('imaProtect', 'debug', "			    ## Brand logo : $brandLogo");
      log::add('imaProtect', 'debug', "			    ## Brand name : $brandName");
      //log::add('imaProtect', 'debug', "			    ## User adresse : $userAdr");
      return $pk;
    }
	*/
	
  
	//Set alarm to off
	public function setAlarmToOff($pwd) {
      	log::add('imaProtect', 'debug', "			==> setAlarmToOff");
      
      	//Check XO code and pwd in input
      	$this->checkAlarmPwd($pwd);
      
		list($httpcode, $result) = $this->doRequest(self::BASE_URL.'client/management/status',array('status' => 'off','token' => $this->statusToken), "POST", $this->setHeaders());
      	if (isset($httpcode) and $httpcode >= 400 ) {
          	throw new Exception($this->manageErrorMessage($httpcode,$result));
        }
    }
      
	private function checkAlarmPwd($pwd) {
      	$response=$this->getContactList();
      	foreach($response['contactList'] as $contact) {
          	if ($contact['pk'] == $this->pkContact) {
              	if ($contact['idCode'] == $pwd){
                  	return true;
                } else {
                  	throw new Exception('Le mot de passe est incorrect');
                }
            }
        }
      	throw new Exception('Le contact n\'est pas présent dans le référentiel des contacts');
	}
	
	//set alarm to on
	public function setAlarmToOn() {
      	log::add('imaProtect', 'debug', "			==> setAlarmToOn");
		list($httpcode, $result) = $this->doRequest(self::BASE_URL.'client/management/status',array('status' => 'on','token' => $this->statusToken), "POST", $this->setHeaders());	
      	if (isset($httpcode) and $httpcode >= 400 ) {
          	throw new Exception($this->manageErrorMessage($httpcode,$result));
        }
	}
  
	//Set alarm to partial
	public function setAlarmToPartial() {
      	log::add('imaProtect', 'debug', "			==> setAlarmToPartial");
		list($httpcode, $result) = $this->doRequest(self::BASE_URL.'client/management/status',array('status' => 'partial','token' => $this->statusToken), "POST", $this->setHeaders());
      	if (isset($httpcode) and $httpcode >= 400 ) {
          	throw new Exception($this->manageErrorMessage($httpcode,$result));
        }
	}
  
  
	//Get camera snapshot of alarm
	public function getCamerasSnapshot() {
		list($httpcode, $result) = $this->doRequest(self::BASE_URL.'client/management/captureList',"", "GET", $this->setHeaders());
      	
      	if (isset($httpcode) and $httpcode >= 400 ) {
          	throw new Exception($this->manageErrorMessage($httpcode,$result));
        } else {
			return $result;
		}
	}
  
  	//Get selected picture
  	public function getPictures($pictureUrl) {
      	log::add('imaProtect', 'debug', "			==> getPictures : $pictureUrl");
		$headers = $this->setHeaders();
      	list($httpcode, $result) = $this->doRequest($pictureUrl,"", "GET", $this->setHeaders());
      	
      	if (isset($httpcode) and $httpcode >= 400 ) {
          	throw new Exception($this->manageErrorMessage($httpcode,$result));
        } else {
			return $result;
		}
    }
  
  function getHeadersPost() {
    $headers[] = "Origin: https://www.imaprotect.com";
    $headers[] = "Referer: https://www.imaprotect.com/fr/client/management";
    $headers[] = "Accept: application/json, text/plain, */*";
    $headers[] = "Content-Type:application/json";
    $headers[]="imainternational: ".$this->imainternational;
    $headers[]="TS013a2ec2: ".$this->TS013a2ec2;
    $headers[]="TS0192ac0d: ".$this->TS0192ac0d;
    $headers[] = sprintf('Cookie: TS013a2ec2=%s;TS0192ac0d=%s;imainternational=%s', $this->TS013a2ec2,$this->TS0192ac0d,$this->imainternational);
    return $headers;
  }
  
  	//Delete selected picture
  	public function deletePictures($picture) {
      	log::add('imaProtect', 'debug', "			==> deletePictures : $pictureUrl");
		$urlDeletePictures="https://pilotageadistance.imateleassistance.com/proxy/api/1.0/hss/". $this->pk . "/captures/$picture";
      	list($httpcode, $result) = $this->doRequest(self::BASE_URL.'client/management/capture/delete/'.$picture,json_encode(array('token' => $this->captureToken)), "POST", $this->getHeadersPost());
      	
      	if (isset($httpcode) and $httpcode >= 400 ) {
          	throw new Exception($this->manageErrorMessage($httpcode,$result));
        } else {
			return $result;
		}
    }
	
	/*
	//Get alarm events
	public function getAlarmEvent(){
      	log::add('imaProtect', 'debug', "			==> getAlarmEvent ");
		$urlEvents="https://pilotageadistance.imateleassistance.com/proxy/api/1.0/hss/". $this->pk . "/events/?_=".time()."000";
		$method = "GET";
		$headers = $this->setHeaders();
      	list($httpcode, $result) = $this->doRequest($urlEvents,"", $method, $headers);
      	
		if (isset($httpcode) and $httpcode >= 400 ) {
          	throw new Exception($this->manageErrorMessage($httpcode,$result));
        } else {
			return $result;
		}

	}
  
	
  
  	

	//Get camera snapshot of alarm
	public function takeSnapshot($roomID) {
      	log::add('imaProtect', 'debug', "			==> takeSnapshot : $roomID");
		$urlTakeSnapshot="https://pilotageadistance.imateleassistance.com/proxy/api/1.0/hss/devices/$roomID/captures/";
		$method = "POST";
		$headers = $this->setHeaders();
      	list($httpcode, $result) = $this->doRequest($urlTakeSnapshot,"", $method, $headers);
      	
      	if (isset($httpcode) and $httpcode >= 400 ) {
          	throw new Exception($this->manageErrorMessage($httpcode,$result));
        } else {
			return $result;
		}
    }
	*/
}

?>