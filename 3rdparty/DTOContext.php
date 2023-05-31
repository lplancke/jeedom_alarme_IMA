<?php
class DTOContext implements JsonSerializable {
	
	protected  $_clientId;
	protected  $_clientSecret;
	protected  $_username;
	protected  $_password;
	protected  $_accessToken;
	protected  $_refreshToken;
  	protected  $_tokenExpires;
	protected  $_code;

	public function __construct(array $data) {
		
			if ($data != NULL) {
				if (array_key_exists("clientId",$data)) {
					$this->_clientId=$data['clientId'];
				}
				
				if (array_key_exists("clientSecret",$data)) {
					$this->_clientSecret=$data['clientSecret'];
				}
				
				if (array_key_exists("userName",$data)) {
					$this->_username=$data['userName'];
				}
				
				if (array_key_exists("password",$data)) {
					$this->_password=$data['password'];
				}
				
				if (array_key_exists("accessToken",$data)) {
					$this->_accessToken=$data['accessToken'];
				}
				
				if (array_key_exists("refreshToken",$data)) {
					$this->_refreshToken=$data['refreshToken'];
				}
				
				if (array_key_exists("tokenExpires",$data)) {
					$this->_tokenExpires=$data['tokenExpires'];
				}
				
				if (array_key_exists("code",$data)) {
					$this->_code =$data['code'];        
				}
			}
    }
	
	 public function jsonSerialize() {
        return 
        [
            'clientId'   => $this->_clientId,
            'clientSecret' => $this->_clientSecret,
			'userName' => $this->_username,
			'password' => $this->_password,
			'accessToken' => $this->_accessToken,
			'refreshToken' => $this->_refreshToken,
			'tokenExpires' => $this->_tokenExpires,
			'code' => $this->_code
        ];
    }
	

	public function getClientId() {
		return $this->_clientId;
	}
	
	public function getClientSecret() {
		return $this->_clientSecret;
	}
	
	public function getUsername() {
		return $this->_username;
	}
	
	public function getPassword() {
		return $this->_password;
	}
	
	public function getAccessToken() {
		return $this->_accessToken;
	}
	
	public function getRefreshToken() {
		return $this->_refreshToken;
	}
	
	public function getTokenExpires() {
		return $this->_tokenExpires;
	}
	
	public function getCode() {
		return $this->_code;
	}
	
	

	
	public function setClientId($clientId) {
		 $this->_clientId=$clientId;
	}
	
	public function setClientSecret($clientSecret) {
		 $this->_clientSecret=$clientSecret;
	}
	
	public function setUsername($username) {
		 $this->_username=$username;
	}
	
	public function setPassword($password) {
		 $this->_password=$password;
	}
	
	public function setAccessToken($accessToken) {
		 $this->_accessToken=$accessToken;
	}
	
	public function setRefreshToken($refreshToken) {
		 $this->_refreshToken=$refreshToken;
	}
	
	public function setTokenExpires($tokenExpires) {
		 $this->_tokenExpires=$tokenExpires;
	}
	
	public function setCode($code) {
		 $this->_code=$code;
	}
}	
