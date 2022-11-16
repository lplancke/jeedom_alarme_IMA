<?php
if (!class_exists('DTOContext')) {
	//require_once dirname(__FILE__) . '/../../3rdparty/alarme_IMAAPI.class.php';
  	require_once __DIR__  . '/DTOContext.class.php';
}

class imaProtectOauth2 {
	
	const ENV='https://api.preprod.ima.eu';
	const CALLBACK_URL='urn:ietf:wg:oauth:2.0:oob';
	const AUTH_URL=$ENV.'/imaprotect/oauth2/auth';
	const ACCESS_TOKEN_URL=$ENV.'/imaprotect/oauth2/token';
	const RESSOURCE_URL=$ENV.'/remote-control/v2';
	const HOMES_URL=$ENV.RESSOURCE_URL.'/homes';
	const DEVICES_URl=$ENV.RESSOURCE_URL.'/homes/%s/devices';
	const CAPABILITIES=$ENV.RESSOURCE_URL.'/devices/%s/capabilities/%s';
	
	const scope='';
	
	/*
	$cookie=sprintf('Cookie:tr=%s; imainternational=%s; TS013a2ec2=%s', 'REFERER%3Awww.imaprotect.com', $this->imainternational, $this->TS013a2ec2);
	*/
	

	private $_dtoContext;
	
		
	public function __construct($username,$password,$clientId,$clientSecret) {
        log::add('alarme_IMA_OAuth2', 'debug', "			==> constructor of class imaProtectOauth2 - Start");
		
		$this->_dtoContext= new DTOContext();
		$_dtoContex->setClientId($clientId);	
		$_dtoContex->setClientSecret($clientSecret);
		$_dtoContex->setUsername($username);
		$_dtoContex->setPassword($password);
		
		//serialize($dto)
	}

	public function __destruct()  {
	
	}
   
	//Execute all https request to Ima protect API
	private function doRequest($url, $data, $method, $headers) {		
      	log::add('alarme_IMA_OAuth2', 'debug', "			==> doRequest");
      	log::add('alarme_IMA_OAuth2', 'debug', "				==> Params : $url | $data | $method | ".json_encode($headers));
      	log::add('alarme_IMA_OAuth2', 'debug', "				==> Params json input : " . json_encode($data));
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

      	log::add('alarme_IMA_OAuth2', 'debug', "				==> Response");
      	log::add('alarme_IMA_OAuth2', 'debug', "					# Code Http : $httpRespCode");
      	log::add('alarme_IMA_OAuth2', 'debug', "					# Body  : ".$body);
      	log::add('alarme_IMA_OAuth2', 'debug', "					# Header  : ".$header);
		
		return array($httpRespCode, $body, $header);
	}
	
	/*
	private function getCookiesFromGetRequest($header,$body) {
		$regExpCsrf='/<input type="hidden" name="_csrf_token"(.*?)value="(.*?)"/ims';
		$regExpIma='/Set-Cookie: imainternational=(.*?);/ims';
		$regExeTS='/Set-Cookie: TS013a2ec2=(.*?);/ims';
	  
		preg_match_all($regExpCsrf, $body, $matchCsrf);
		preg_match_all($regExpIma, $header, $matchIma);
		preg_match_all($regExeTS, $header, $matchTS);
		
		$csrf=str_replace(array('\\"','[',']','"',' ',"\n"),'',json_encode($matchCsrf[2]));  
		$ima=str_replace(array('"','[',']','\\'),array('','','',''),json_encode($matchIma[1]));
		$TS013a2ec2=str_replace(array('"','[',']','\\'),array('','','',''),json_encode($matchTS[1]));
	  
		$regExexpires='/Set-Cookie: imainternational='.$ima.'; expires=(.*?);/ims';
		preg_match_all($regExexpires, $header, $matchExpires);
		$expire=str_replace(array('"','[',']','\\'),array('','','',''),json_encode($matchExpires[1]));

		if (!$this->IsNullOrEmpty($TS013a2ec2)) {
			$this->TS013a2ec2=$TS013a2ec2;
		} else {
			throw new Exception($this->manageErrorMessage('500','login function - Erreur récuperation cookie (TS013a2ec2)'));
		}

		if(!$this->IsNullOrEmpty($ima)) {
			$this->imainternational=$ima;
		} else {
			throw new Exception($this->manageErrorMessage('500','login function - Erreur récuperation cookie (imainternational)'));
		}
		
		if(!$this->IsNullOrEmpty($expire)) {
			$this->expireImaCookie=$expire;
		} else {
			throw new Exception($this->manageErrorMessage('500','login function - Erreur récuperation cookie (expireImaCookie)'));
		}
		
		if(!$this->IsNullOrEmpty($csrf)) {
			$this->csrfToken=$csrf;
		} else {
			throw new Exception($this->manageErrorMessage('500','login function - Erreur récuperation cookie (csrf)'));
		}	
		
		log::add('alarme_IMA', 'debug', '				==> Recover cookies get : '. $this->imainternational .'|'. $this->TS013a2ec2 .'|'. $this->expireImaCookie.'|'.$this->csrfToken);
	}
	
	private function getCookiesFromPostRequest($header) {
		$regExpIma='/Set-Cookie: imainternational=(.*?);/ims';
		$regExeTS013='/Set-Cookie: TS013a2ec2=(.*?);/ims';
		$regExeTS019='/Set-Cookie: TS0192ac0d=(.*?);/ims';

		preg_match_all($regExpIma, $header, $matchIma);
		preg_match_all($regExeTS013, $header, $matchTS013);
		preg_match_all($regExeTS019, $header, $matchTS019);

		$ima=str_replace(array('"','[',']','\\'),array('','','',''),json_encode($matchIma[1][0]));
		$TS013a2ec2=str_replace(array('"','[',']','\\'),array('','','',''),json_encode($matchTS013[1][0]));
		$TS0192ac0d=str_replace(array('"','[',']','\\'),array('','','',''),json_encode($matchTS019[1]));

		$regExexpires='/Set-Cookie: imainternational='.$ima.'; expires=(.*?);/ims';
		preg_match_all($regExexpires, $header, $matchExpires);
		$expire=str_replace(array('"','[',']','\\'),array('','','',''),json_encode($matchExpires[1]));
		
		if(!$this->IsNullOrEmpty($TS0192ac0d)) {
			$this->TS0192ac0d=$TS0192ac0d;
		} else {
			throw new Exception($this->manageErrorMessage('500','login_check function - Erreur récuperation cookie (TS0192ac0d)'));
		}
		
		if(!$this->IsNullOrEmpty($TS013a2ec2)) {
			$this->TS013a2ec2=$TS013a2ec2;
		} else {
			throw new Exception($this->manageErrorMessage('500','login_check function - Erreur récuperation cookie (TS013a2ec2)'));
		}
		
		if(!$this->IsNullOrEmpty($ima)) {
			$this->imainternational=$ima;
		} else {
			throw new Exception($this->manageErrorMessage('500','login_check function - Erreur récuperation cookie (imainternational)'));
		}
		
		if(!$this->IsNullOrEmpty($expire)) {
			$this->expireImaCookie=$expire;
		} else {
			throw new Exception($this->manageErrorMessage('500','login_check function - Erreur récuperation cookie (expireImaCookie)'));
		}
		     	
      	log::add('alarme_IMA', 'debug', '				==> Recover cookies : '. $this->imainternational .'|'. $this->TS013a2ec2 .'|'. $this->TS0192ac0d .'|'.$this->expireImaCookie);
	}
	*/

	private function setHeaders()   {		

		$headers = array();

		$headers[] ='Host: www.imaprotect.com';
		$headers[] ='text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9';
		$headers[] ='Referer: https://www.imaprotect.com/fr/client/';
		$headers[] ='Accept-Encoding: gzip, deflate, br';
		$cookie=sprintf('Cookie:tr=%s; imainternational=%s; TS013a2ec2=%s', 'REFERER%3Awww.imaprotect.com', $this->imainternational, $this->TS013a2ec2);
		$headers[]=$cookie;
		
		return $headers;
	}	
	
	/*
	private function gunzip($zipped) {
		$offset = 0;
		if (substr($zipped,0,2) == "\x1f\x8b") {
			$offset = 2;
		}
		
		if (substr($zipped,$offset,1) == "\x08")  {
		 return gzinflate(substr($zipped, $offset + 8));
		}
		
		return "Unknown Format";
	}	

*/
	private function setParams($request,$pwd) {			//Set params for https request to Verisure Cloud
		
		switch($request)  {
			case "LOGIN":
				$params = array( '_username' => $this->username, '_password' => $this->password, '_csrf_token' => $this->csrfToken );
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
	
	private function tokenIsValid($tokenExpires){
		if (isset($tokenExpires)) {
			$diff=round(strtotime($tokenExpires)-time(),1);
			if ($diff > 10) {
				log::add('alarme_IMA_OAuth2', 'debug', "				* sessionID is valid");
				return true;
			} else {
				log::add('alarme_IMA_OAuth2', 'debug', "				* sessionID is expired");
				return false;
			}
		} else {
			log::add('alarme_IMA_OAuth2', 'debug', "			==> Expiration of sessionID is missing");
			return false;
		}
	}
	
	
	/*
	private function cookieIsValid($cookieExpiredDate){
		if (isset($cookieExpiredDate)) {
			$diff=round(strtotime($cookieExpiredDate)-time(),1);
			if ($diff > 10) {
				log::add('alarme_IMA', 'debug', "				* sessionID is valid");
				return true;
			} else {
				log::add('alarme_IMA', 'debug', "				* sessionID is expired");
				return false;
			}
		} else {
			log::add('alarme_IMA', 'debug', "			==> Expiration of sessionID is missing");
			return false;
		}
	}
	*/

	public function getDatasSession(){
      	log::add('alarme_IMA_OAuth2', 'debug', "			==> " . __FUNCTION__);
		if (config::byKey('imaOAuth_session', 'alarme_IMA') == '') {
            log::add('alarme_IMA_OAuth2', 'debug', "			==> No plugin config ... init to call");
			return false;
        } else {
			log::add('alarme_IMA_OAuth2', 'debug', '			==> plugin config : '.json_encode(config::byKey('imaOAuth_session', 'alarme_IMA')));
			return self::checkDatasSession(json_encode(config::byKey('imaOAuth_session', 'alarme_IMA')));
		}
	}
	
	private function checkDatasSession($datasSession) {
		if (isset($datasSession)) {
			log::add('alarme_IMA_OAuth2', 'debug', "			==> Read datas session ... datas $datasSession");
			$arrayDecode=json_decode($datasSession,true);
		  
		  
			if (isset($arrayDecode["clientId"])) {
				$this->clientId= $arrayDecode["clientId"];
			}
			
			if (isset($arrayDecode["clientSecret"])) {
				$this->clientSecret= $arrayDecode["clientSecret"];
			}
			
			if (isset($arrayDecode["username"])) {
				$this->username= $arrayDecode["username"];
			}
			
			if (isset($arrayDecode["password"])) {
				$this->password= $arrayDecode["password"];
			}
			
			if (isset($arrayDecode["accessToken"])) {							
				$this->accessToken= $arrayDecode["accessToken"];
			}
			
			if (isset($arrayDecode["refresjToken"])) {
				$this->refresjToken= $arrayDecode["refresjToken"];
			}
			
			if (isset($arrayDecode["tokenExpires"])) {
				$this->tokenExpires= $arrayDecode["tokenExpires"];
			}
			
			if (isset($arrayDecode["code"])) {
				$this->code= $arrayDecode["code"];
			}
			
			return $this->tokenIsValid($this->tokenExpires);

			/*
			if ($this->IsNullOrEmpty($arrayDecode["expireImaCookie"]) || $this->IsNullOrEmpty($arrayDecode["imainternational"]) || $this->IsNullOrEmpty($arrayDecode["TS013a2ec2"]) || $this->IsNullOrEmpty($arrayDecode["TS0192ac0d"]) || $this->IsNullOrEmpty($arrayDecode["statusToken"]) || $this->IsNullOrEmpty($arrayDecode["captureToken"]) || $this->IsNullOrEmpty($arrayDecode["csrfToken"])) {
			  log::add('alarme_IMA_OAuth2', 'debug', "			==> No all datas read in temporary file !!!");
			  return false;
			}
			*/
			

		} else {
			log::add('alarme_IMA_OAuth2', 'debug', "			==> No datas read in temporary file !!!");
			return false;
		}
	}
	
  
  	private function manageErrorMessage($httpCode,$error) {
      	log::add('alarme_IMA_OAuth2', 'debug', "			" . __FUNCTION__ . " : " . $error . "|" .$httpCode);
      	$errorMessage="Unknown error";
		if (!$this->IsNullOrEmpty($error)) {
			$errorMessage=str_replace("\"","",$error);
          	$errorArray=json_decode($error,true);
          	if (!$this->IsNullOrEmpty($errorArray["error"])) {
              	$errorMsg=json_decode($errorArray,true);
              	if (!$this->IsNullOrEmpty($errorMsg["code"]) and !$this->IsNullOrEmpty($errorMsg["message"]) ) {
					$errorMsgCode=$errorMsg["code"];
                	$errorMsgMessage=$errorMsg["message"];
                  	$errorMessage=$errorMsg["message"] .' - '. $errorMsgCode;
	              	log::add('alarme_IMA', 'debug', "				==> decode json  : " . $errorMsgCode . "|" . $errorMsgMessage);
                }
            }
		}
		
		if (!$this->IsNullOrEmpty($httpCode)) {
			$errorMessage .= " (". $httpCode . ")";
		}
		
      	log::add('alarme_IMA', 'debug', "			==> errorMessage : " . $errorMessage);
      	return $errorMessage;
    }
  
  	private function IsNullOrEmpty($input){
    	return (!isset($input) || trim($input)==='');
	}
  
  /*
  	private function getHeadersLoginCheck	() {
		
		$headers = array();
		$headers[] = "Host: www.imaprotect.com";
		$headers[] = "Origin: https://www.imaprotect.com";
		$headers[] = "Referer: https://www.imaprotect.com/fr/client/login";
		$headers[] = "Content-Type: application/x-www-form-urlencoded";
		$cookie=sprintf('Cookie: imainternational=%s; tr=%s;TS013a2ec2=%s', $this->imainternational,'REFERER%3Awww.imaprotect.com',$this->TS013a2ec2);
		$headers[]=$cookie;

      	return $headers;
    }

  
	//Log to IMA Account
	public function login()  {		
		log::add('alarme_IMA', 'debug','		* ' .  __FUNCTION__);
		list($httpcode, $result, $header) = $this->doRequest(self::BASE_URL.'client/login',null, "GET", null);
      	if (isset($httpcode) and $httpcode >= 400 ) {
          	throw new Exception($this->manageErrorMessage($httpcode,$result));
        } else {
          	//store cookie from response
          	$this->getCookiesFromGetRequest($header,$result);
			//get cookie for accessing to ima api
			$this->loginCheck();
		}
	}
	
	private function loginCheck() {
		log::add('alarme_IMA', 'debug','		* ' .  __FUNCTION__);
		list($httpcode, $result, $header) = $this->doRequest(self::BASE_URL.'client/login_check',$this->setParams("LOGIN",null), "POST", $this->getHeadersLoginCheck());
      	if (isset($httpcode) and $httpcode >= 400 ) {
          	throw new Exception($this->manageErrorMessage($httpcode,$result));
        } else {
          	//store cookies from response
          	$this->getCookiesFromPostRequest($header);	
        }

	}
	
	//Get IMA Tokens for futur actions
	public function getTokens() {
      	list($httpcode, $result, $header) = $this->doRequest(self::BASE_URL.'client/management',"", "GET", $this->setHeaders());
      
      	if (isset($httpcode) and $httpcode >= 400 ) {
			throw new Exception($result);
        } else {
			$this->retrieveIMATokens($this->gunzip($result));			
			$contextArray =	array(
				"expireImaCookie" => $this->expireImaCookie,
				"imainternational" => $this->imainternational,
				"TS013a2ec2" =>  $this->TS013a2ec2,
				"TS0192ac0d" => $this->TS0192ac0d,
				"statusToken" => $this->statusToken,
				"captureToken"=> $this->captureToken,
				"csrfToken"=> $this->csrfToken
			);
				
			config::save('imaToken_session_'.$this->id,json_encode($contextArray),'alarme_IMA');
						
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
		$response='';
		for ($i = 1; $i <= 3; $i++) {
          	log::add('alarme_IMA', 'debug', "			==> getAlarmStatus - attemp : " . $i);
			list($httpcode, $result, $header) = $this->doRequest(self::BASE_URL.'client/management/status',"", "GET",  $this->setHeaders());

          	if (isset($httpcode) and $httpcode >= 400 ) {
				throw new Exception($this->manageErrorMessage($httpcode,$result));
			} else {			
				if ($httpcode >= 300) {
					//regeneration of cookies and token for the next time
					$this->Login();
					$this->getTokens();					
				} else {
 	              $response = $result;
                  break;
                }
			}
		}
		return json_decode($response,true);
	}
	
	public function getContactList(){   
		log::add('alarme_IMA', 'debug', "			==> getContactList ");
		list($httpcode, $result, $header) = $this->doRequest(self::BASE_URL.'client/contact/list',"", "GET",  $this->setHeaders());
      
      	if (isset($httpcode) and $httpcode >= 400 ) {
          	throw new Exception($this->manageErrorMessage($httpcode,$result));
        } else {
      		return json_decode($result,true);
        }
	}
  

  //Get IMA other info like room id
	public function getOtherInfo() {
		log::add('alarme_IMA', 'debug', "			==> getOtherInfo ");
			
		list($httpcode, $result, $header) = $this->doRequest(self::BASE_URL.'client/management/cameras',"", "GET",  $this->setHeaders());
      
      	if (isset($httpcode) and $httpcode >= 400 ) {
          	throw new Exception($this->manageErrorMessage($httpcode,$result));
        } else {
			$roomsInfo=$this->readResponseForGetOtherInfo($result);
			if(isset($roomsInfo)) {
				$this->rooms=$roomsInfo;
				$contextArray =	array(
					"expireImaCookie" => $this->expireImaCookie,
					"imainternational" => $this->imainternational,
					"TS013a2ec2" =>  $this->TS013a2ec2,
					"TS0192ac0d" => $this->TS0192ac0d,
					"statusToken" => $this->statusToken,
					"captureToken"=> $this->captureToken,
					"csrfToken"=> $this->csrfToken,
					"rooms"=> $this->rooms
				);
                return true;
            } else {
				throw new Exception("Error extracting rooms informations");
			}
        }
	}

	//Recover pk of rooms
	private function readResponseForGetOtherInfo($result) {
		log::add('alarme_IMA', 'debug', "				==> readResponseForGetOtherInfo - Start");
		$response= array();
		$resultArr=json_decode($result,true);
		
		foreach($resultArr as $room) {
		  array_push($response,array("room"=>$room["name"],"pk"=>$room["pk"]));
		}
		
		log::add('alarme_IMA', 'debug', "				==> readResponseForGetOtherInfo - End -> response :  ".json_encode($response));
		return $response;
	}

	  
	//Set alarm to off
	public function setAlarmToOff($pwd) {
      	log::add('alarme_IMA', 'debug', "			==> setAlarmToOff");
      
      	//Check XO code and pwd in input
      	$this->checkAlarmPwd($pwd);
      
		list($httpcode, $result, $header) = $this->doRequest(self::BASE_URL.'client/management/status',array('status' => 'off','token' => $this->statusToken), "POST", $this->setHeaders());
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
      	log::add('alarme_IMA', 'debug', "			==> setAlarmToOn");
		list($httpcode, $result, $header) = $this->doRequest(self::BASE_URL.'client/management/status',array('status' => 'on','token' => $this->statusToken), "POST", $this->setHeaders());	
      	if (isset($httpcode) and $httpcode >= 400 ) {
          	throw new Exception($this->manageErrorMessage($httpcode,$result));
        }
	}
  
	//Set alarm to partial
	public function setAlarmToPartial() {
      	log::add('alarme_IMA', 'debug', "			==> setAlarmToPartial");
		list($httpcode, $result, $header) = $this->doRequest(self::BASE_URL.'client/management/status',array('status' => 'partial','token' => $this->statusToken), "POST", $this->setHeaders());
      	if (isset($httpcode) and $httpcode >= 400 ) {
          	throw new Exception($this->manageErrorMessage($httpcode,$result));
        }
	}
  
  
	//Get camera snapshot of alarm
	public function getCamerasSnapshot() {
		list($httpcode, $result, $header) = $this->doRequest(self::BASE_URL.'client/management/captureList',"", "GET", $this->setHeaders());
      	
      	if (isset($httpcode) and $httpcode >= 400 ) {
          	throw new Exception($this->manageErrorMessage($httpcode,$result));
        } else {
			return $result;
		}
	}
  
  	//Get selected picture
  	public function getPictures($pictureUrl) {
      	log::add('alarme_IMA', 'debug', "			==> getPictures : $pictureUrl");
		$headers = $this->setHeaders();
      	list($httpcode, $result, $header) = $this->doRequest($pictureUrl,"", "GET", $this->setHeaders());
      	
      	if (isset($httpcode) and $httpcode >= 400 ) {
          	throw new Exception($this->manageErrorMessage($httpcode,$result));
        } else {
			return $result;
		}
    }
    */
	
	private function getHeadersPost() {
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
  
  /*
  	//Delete selected picture
  	public function deletePictures($picture) {
      	log::add('alarme_IMA', 'debug', "			==> deletePictures : $pictureUrl");
      	list($httpcode, $result, $header) = $this->doRequest(self::BASE_URL.'client/management/capture/delete/'.$picture,json_encode(array('token' => $this->captureToken)), "POST", $this->getHeadersPost());
      	
      	if (isset($httpcode) and $httpcode >= 400 ) {
          	throw new Exception($this->manageErrorMessage($httpcode,$result));
        } else {
			return $result;
		}
    }
	
	//Get alarm events
	public function getAlarmEvent(){
      	log::add('alarme_IMA', 'debug', "			==> getAlarmEvent ");
      	list($httpcode, $result, $header) = $this->doRequest("https://www.imaprotect.com/fr/client/management/journal","", "GET", $this->setHeaders());
      	
      	if (isset($httpcode) and $httpcode >= 400 ) {
          	throw new Exception($this->manageErrorMessage($httpcode,$result));
        } else {
			return $result;
		}

	}
	

	//Get camera snapshot of alarm
	public function takeSnapshot($roomID) {
      	log::add('alarme_IMA', 'debug', "			==> takeSnapshot : $roomID");

		list($httpcode, $result, $header) = $this->doRequest(self::BASE_URL.'client/management/capture/new/'.$roomID,json_encode(array('device_id' => $this->guidv4())), "POST", $this->getHeadersPost());
      	
      	if (isset($httpcode) and $httpcode >= 400 ) {
          	throw new Exception($this->manageErrorMessage($httpcode,$result));
        } else {
			return $result;
		}      	
    }
	*/
	
	private function guidv4() {
		// Generate 16 bytes (128 bits) of random data or use the data passed into the function.
		$data = random_bytes(16);

		$data[6] = chr(ord($data[6]) & 0x0f | 0x40);
		$data[8] = chr(ord($data[8]) & 0x3f | 0x80);
		
		return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
	}


}

?>