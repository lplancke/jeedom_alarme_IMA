<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
//require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
  require_once __DIR__  . '/../../../../core/php/core.inc.php';

if (!class_exists('imaProtectNewAPI')) {
	//require_once dirname(__FILE__) . '/../../3rdparty/alarme_IMAAPI.class.php';
  	require_once __DIR__  . '/../../3rdparty/imaProtectNewAPI.class.php';
}

class alarme_IMA extends eqLogic {
    /*     * *************************Attributs****************************** */
	const IMA_ON=2;
	const IMA_PARTIAL=1;
	const IMA_OFF=0;
	const IMA_UNKNOWN=-1;
	const IMA_IGNORED=-2;

  	private function fmt_date($timeStamp) {
		setlocale(LC_TIME, 'fr_FR.utf8','fra');
		return(ucwords(strftime("%a %d %b %T",$timeStamp)));
	}
  
    /*
     * Fonction exécutée automatiquement toutes les minutes par Jeedom    */
	public static function cron() {
		$autorefresh = config::byKey('autorefresh', 'alarme_IMA');
		if ($autorefresh != '') {
			try {
                $c = new Cron\CronExpression(checkAndFixCron($autorefresh), new Cron\FieldFactory);
                if ($c->isDue()) {
                    log::add('alarme_IMA', 'debug', 'Exécution du cron Ima Protect');
                  	
		            foreach (eqLogic::byType('alarme_IMA', true) as $alarme_IMA) {
					
                      	$oldValue=$alarme_IMA->getCmd(null, 'statusAlarme')->execCmd();
						$newValue=$alarme_IMA->GetAlarmState();
                      
                        if (isset($newValue) and $newValue!=self::IMA_IGNORED)  {
                            $alarme_IMA->checkAndUpdateCmd('statusAlarme', $newValue);
                          	if (isset($oldValue) && is_numeric($oldValue)) {
                              if (strcmp($oldValue,$newValue) > 0 OR  strcmp($oldValue,$newValue) < 0) {
                                log::add('alarme_IMA', 'debug',  " Le statut de l alarme a change (old|new): $oldValue | $newValue");
                                $alarme_IMA->getCmd(null, 'refreshAlarmEvents')->execCmd();
                              } else {
                                log::add('alarme_IMA', 'debug',  " Le statut de l'alarme n'a pas changé (old|new): $oldValue | $newValue");
                              }
                            }
                        } else {
                            log::add('alarme_IMA', 'debug', "Retour ignoré");
                        }
                        $alarme_IMA->writeSeparateLine();
                   }
                   
				}
			} catch (Exception $exc) {
				log::add('alarme_IMA', 'error', __("Erreur lors de l'exécution du cron ", __FILE__) . $exc->getMessage());
			}
		}
	}


     /* Fonction exécutée automatiquement toutes les heures par Jeedom */
    public static function cronHourly() {
      log::add('alarme_IMA', 'debug', 'Exécution du cron hourly Alarme IMA - Start');
      foreach (eqLogic::byType('alarme_IMA', true) as $alarme_IMA) {
        $alarme_IMA->writeSeparateLine();
        $alarme_IMA->getCmd(null, 'refreshAlarmEvents')->execCmd();
        $alarme_IMA->getCmd(null, 'refreshCameraSnapshot')->execCmd();
        $alarme_IMA->writeSeparateLine();
      }
      log::add('alarme_IMA', 'debug', 'Exécution du cron hourly Alarme IMA - End');
    }

    /*
     * Fonction exécutée automatiquement tous les jours par Jeedom 
      public static function cronDayly() {

      }
	*/


    /*     * *********************Méthodes d'instance************************* */

    public function preInsert() {        
		log::add('alarme_IMA', 'debug',  "A");
    }

    public function postInsert() {        
		log::add('alarme_IMA', 'debug',  "B");
    }

    public function preSave() {        
		log::add('alarme_IMA', 'debug',  "C");
    }

    public function postSave() {
		log::add('alarme_IMA', 'debug',  "D");
    }
  
  	private function createCmd(){
      log::add('alarme_IMA', 'debug',  "Création des commandes : start");

        $cmd = $this->getCmd(null, 'statusAlarme');
		if (! is_object($cmd))
		{
			$alarme_IMACmd = new alarme_IMACmd();
			$alarme_IMACmd->setName(__('Statut alarme', __FILE__));
			$alarme_IMACmd->setOrder(1);
			$alarme_IMACmd->setEqLogic_id($this->id);
			$alarme_IMACmd->setLogicalId('statusAlarme');
			$alarme_IMACmd->setConfiguration('data', 'statusAlarme');
			$alarme_IMACmd->setConfiguration('historizeMode', 'none');
			$alarme_IMACmd->setType('info');
			$alarme_IMACmd->setSubType('numeric');
			$alarme_IMACmd->setTemplate('dashboard', 'line');
			$alarme_IMACmd->setTemplate('mobile', 'line');
			$alarme_IMACmd->setIsHistorized(1);
			$alarme_IMACmd->setDisplay('graphStep', '1');
			$alarme_IMACmd->setConfiguration("MaxValue", self::IMA_ON);
			$alarme_IMACmd->setConfiguration("MinValue", self::IMA_UNKNOWN);
			$alarme_IMACmd->save();
          	log::add('alarme_IMA', 'debug', 'Création de la commande '.$alarme_IMACmd->getName().' (LogicalId : '.$alarme_IMACmd->getLogicalId().')');
        }
      
      	$cmd = $this->getCmd(null, 'alarmeEvents');
		if (! is_object($cmd))
		{
          	$cmd = new alarme_IMACmd();
            $cmd->setName('Evenements');
			$cmd->setOrder(2);
            $cmd->setEqLogic_id($this->getId());
            $cmd->setLogicalId('alarmeEvents');
            $cmd->setUnite('');
            $cmd->setType('info');
            $cmd->setSubType('string');
            $cmd->setIsVisible(1);
            $cmd->setIsHistorized(0);
          	$cmd->setConfiguration('cmdsMaked', true);
          	$cmd->setTemplate('dashboard', 'default');
			$cmd->setTemplate('mobile','default');
          	$cmd->save();
          	log::add('alarme_IMA', 'debug', 'Création de la commande '.$cmd->getName().' (LogicalId : '.$cmd->getLogicalId().')');
        }

      	$cmd = $this->getCmd(null, 'alarmeEventsBrute');
		if (! is_object($cmd))
		{
          	$cmd = new alarme_IMACmd();
            $cmd->setName('Evenements données brutes');
			$cmd->setOrder(4);
            $cmd->setEqLogic_id($this->getId());
            $cmd->setLogicalId('alarmeEventsBrute');
            $cmd->setUnite('');
            $cmd->setType('info');
            $cmd->setSubType('string');
            $cmd->setIsVisible(1);
            $cmd->setIsHistorized(0);
          	$cmd->setConfiguration('cmdsMaked', true);
          	$cmd->setTemplate('dashboard', 'default');
			$cmd->setTemplate('mobile','default');
          	$cmd->save();
          	log::add('alarme_IMA', 'debug', 'Création de la commande '.$cmd->getName().' (LogicalId : '.$cmd->getLogicalId().')');
        }
		
      	$cmdCameraSnapshot = $this->getCmd(null, 'cameraSnapshot');
		if (! is_object($cmdCameraSnapshot))
		{
          	$cmdCameraSnapshot = new alarme_IMACmd();
            $cmdCameraSnapshot->setName('Images caméras');
			$cmdCameraSnapshot->setOrder(3);
            $cmdCameraSnapshot->setEqLogic_id($this->getId());
            $cmdCameraSnapshot->setLogicalId('cameraSnapshot');
            $cmdCameraSnapshot->setUnite('');
            $cmdCameraSnapshot->setType('info');
            $cmdCameraSnapshot->setSubType('string');
            $cmdCameraSnapshot->setIsVisible(1);
            $cmdCameraSnapshot->setIsHistorized(0);
          	$cmdCameraSnapshot->setTemplate('dashboard', 'default');
			$cmdCameraSnapshot->setTemplate('mobile','default');
          	$cmdCameraSnapshot->save();
          	log::add('alarme_IMA', 'debug', 'Création de la commande '.$cmdCameraSnapshot->getName().' (LogicalId : '.$cmdCameraSnapshot->getLogicalId().')');
        }
      
      	$cmdCameraSnapshotBrute = $this->getCmd(null, 'cameraSnapshotBrute');
		if (! is_object($cmdCameraSnapshotBrute))
		{
          	$cmdCameraSnapshotBrute = new alarme_IMACmd();
            $cmdCameraSnapshotBrute->setName('Images caméras données brutes');
			$cmdCameraSnapshotBrute->setOrder(5);
            $cmdCameraSnapshotBrute->setEqLogic_id($this->getId());
            $cmdCameraSnapshotBrute->setLogicalId('cameraSnapshotBrute');
            $cmdCameraSnapshotBrute->setUnite('');
            $cmdCameraSnapshotBrute->setType('info');
            $cmdCameraSnapshotBrute->setSubType('string');
            $cmdCameraSnapshotBrute->setIsVisible(1);
            $cmdCameraSnapshotBrute->setIsHistorized(0);
          	$cmdCameraSnapshotBrute->setTemplate('dashboard', 'default');
			$cmdCameraSnapshotBrute->setTemplate('mobile','default');
          	$cmdCameraSnapshotBrute->save();
          	log::add('alarme_IMA', 'debug', 'Création de la commande '.$cmdCameraSnapshotBrute->getName().' (LogicalId : '.$cmdCameraSnapshotBrute->getLogicalId().')');
        }
      
      	$cmdRefreshAlarmStatus = $this->getCmd(null, 'refreshAlarmeStatus');
		if (!is_object($cmdRefreshAlarmStatus)) {
			$cmdRefreshAlarmStatus = new alarme_IMACmd();
			$cmdRefreshAlarmStatus->setOrder(6);
			$cmdRefreshAlarmStatus->setName('Rafraichir statut alarme');
			$cmdRefreshAlarmStatus->setEqLogic_id($this->getId());
			$cmdRefreshAlarmStatus->setLogicalId('refreshAlarmeStatus');
			$cmdRefreshAlarmStatus->setType('action');
			$cmdRefreshAlarmStatus->setSubType('other');
          	$cmdRefreshAlarmStatus->setTemplate('dashboard', 'default');
			$cmdRefreshAlarmStatus->setTemplate('mobile','default');
          	$cmdRefreshAlarmStatus->dontRemoveCmd();
			$cmdRefreshAlarmStatus->save();
			log::add('alarme_IMA', 'debug', 'Création de la commande '.$cmdRefreshAlarmStatus->getName().' (LogicalId : '.$cmdRefreshAlarmStatus->getLogicalId().')');
		}
      
      	$cmdRefreshCameraSnapshot = $this->getCmd(null, 'refreshCameraSnapshot');
		if (!is_object($cmdRefreshCameraSnapshot)) {
			$cmdRefreshCameraSnapshot = new alarme_IMACmd();
			$cmdRefreshCameraSnapshot->setOrder(8);
			$cmdRefreshCameraSnapshot->setName('Rafraichir capture caméras');
			$cmdRefreshCameraSnapshot->setEqLogic_id($this->getId());
			$cmdRefreshCameraSnapshot->setLogicalId('refreshCameraSnapshot');
			$cmdRefreshCameraSnapshot->setType('action');
			$cmdRefreshCameraSnapshot->setSubType('other');
          	$cmdRefreshCameraSnapshot->setTemplate('dashboard', 'default');
			$cmdRefreshCameraSnapshot->setTemplate('mobile','default');
			$cmdRefreshCameraSnapshot->save();
			log::add('alarme_IMA', 'debug', 'Création de la commande '.$cmdRefreshCameraSnapshot->getName().' (LogicalId : '.$cmdRefreshCameraSnapshot->getLogicalId().')');
		}
      

      	$cmdRefreshEventsAlarm = $this->getCmd(null, 'refreshAlarmEvents');
		if (!is_object($cmdRefreshEventsAlarm)) {
			$cmdRefreshEventsAlarm = new alarme_IMACmd();
			$cmdRefreshEventsAlarm->setOrder(7);
			$cmdRefreshEventsAlarm->setName('Rafraichir évènements alarme');
			$cmdRefreshEventsAlarm->setEqLogic_id($this->getId());
			$cmdRefreshEventsAlarm->setLogicalId('refreshAlarmEvents');
			$cmdRefreshEventsAlarm->setType('action');
			$cmdRefreshEventsAlarm->setSubType('other');
          	$cmdRefreshEventsAlarm->setTemplate('dashboard', 'default');
			$cmdRefreshEventsAlarm->setTemplate('mobile','default');
			$cmdRefreshEventsAlarm->save();
			log::add('alarme_IMA', 'debug', 'Création de la commande '.$cmdRefreshEventsAlarm->getName().' (LogicalId : '.$cmdRefreshEventsAlarm->getLogicalId().')');
		}
	  
      	$cmdActionModeAlarme = $this->getCmd(null, 'setModeAlarme');
        if ( ! is_object($cmdActionModeAlarme)) {
          $cmdActionModeAlarme = new alarme_IMACmd();
          $cmdActionModeAlarme->setOrder(9);
          $cmdActionModeAlarme->setName('Action mode alarme');
          $cmdActionModeAlarme->setEqLogic_id($this->getId());
          $cmdActionModeAlarme->setLogicalId('setModeAlarme');
          $cmdActionModeAlarme->setType('action');
          $cmdActionModeAlarme->setSubType('message');
          log::add('alarme_IMA', 'debug', 'Création de la commande '.$cmdActionModeAlarme->getName().' (LogicalId : '.$cmdActionModeAlarme->getLogicalId().')');
        }
      
      	$listValue="2|total;1|partiel;0|eteind";
		$cmdActionModeAlarme->setConfiguration('listValue', $listValue);
      	$cmdStatutAlarme= $this->getCmd(null, 'statusAlarme');
      	$cmdActionModeAlarme->setValue($cmdStatutAlarme->getId());
		$cmdActionModeAlarme->save();
      
      	$cmdActionScreenshot = $this->getCmd(null, 'actionScreenshot');
		if (!is_object($cmdActionScreenshot)) {
			$cmdActionScreenshot = new alarme_IMACmd();
			$cmdActionScreenshot->setOrder(10);
			$cmdActionScreenshot->setName('Actions sur une image caméra');
			$cmdActionScreenshot->setEqLogic_id($this->getId());
			$cmdActionScreenshot->setLogicalId('actionScreenshot');
			$cmdActionScreenshot->setType('action');
			$cmdActionScreenshot->setSubType('message');
          	$cmdActionScreenshot->setTemplate('dashboard', 'default');
			$cmdActionScreenshot->setTemplate('mobile','default');
			$cmdActionScreenshot->save();
			log::add('alarme_IMA', 'debug', 'Création de la commande '.$cmdActionScreenshot->getName().' (LogicalId : '.$cmdActionScreenshot->getLogicalId().')');
		}
		log::add('alarme_IMA', 'debug',  "Création des commandes - End");
    }

    public function preUpdate() {
		log::add('alarme_IMA', 'debug',  "appel preUpdate");
   		if (empty($this->getConfiguration('login_ima'))) {
			throw new Exception(__('L\'identifiant ne peut pas être vide',__FILE__));
		}

		if (empty($this->getConfiguration('password_ima'))) {
			throw new Exception(__('Le mot de passe ne peut etre vide',__FILE__));
		}
      
      
    }

    public function postUpdate() {
      	$this->createCmd();
    }

    public function preRemove() {
        
    }

    public function postRemove() {
        
    }

    /*
     * Non obligatoire mais permet de modifier l'affichage du widget si vous en avez besoin */
//    public function toHtml($_version = 'dashboard') {
//		$ret=parent::toHtml();
//        log::add('alarme_IMA', 'debug', "ceci".$ret);
//		return $ret;
//      }

    /*
     * Non obligatoire mais ca permet de déclancher une action après modification de variable de configuration
    public static function postConfig_<Variable>() {
    }
     */

    /*
     * Non obligatoire mais ca permet de déclancher une action avant modification de variable de configuration
    public static function preConfig_<Variable>() {
    }
     */

    /*     * **********************Getteur Setteur*************************** */
  public function GetAlarmState()	{
  	log::add('alarme_IMA', 'debug',  "  GetAlarmState Start");
  	log::add('alarme_IMA', 'debug',  "	* instanciation api ima protect");
    try {
      	$myImaProtectAlarm = $this->getInstanceIMAApi();
		log::add('alarme_IMA', 'debug',  "	* Recuperation statut de l'alarme");
		
		$alarmeStatus = $myImaProtectAlarm->getAlarmStatus();
      
      	if (!isset($alarmeStatus)) {
            log::add('alarme_IMA', 'error', "	    - Impossible de trouver le status");
			$oldValue=$alarme_IMA->getCmd(null, 'statusAlarme')->execCmd();
            $oldValue;
			//return self::IMA_UNKNOWN;
        }

        $convStatusToNumeric=array(
          "on" => "2",
          "partial" => "1",
          "off"=> "0"
        );

        $numericStatus=$convStatusToNumeric[$alarmeStatus];
        log::add('alarme_IMA', 'debug', "	    - Nouveau status numerique alarme: $numericStatus | $alarmeStatus");
        log::add('alarme_IMA', 'debug',  "  GetAlarmState End");	
        return $numericStatus;
    } catch (Exception $e) {
      	$this->manageErrorAPI("GetAlarmState",$e->getMessage());
    }
  }
 
  public function GetAlarmEvents()	{
  	log::add('alarme_IMA', 'debug',  "  GetAlarmEvents Start");
  	log::add('alarme_IMA', 'debug',  "	* instanciation api ima protect");
	try{
		$myImaProtectAlarm = $this->getInstanceIMAApi();
		log::add('alarme_IMA', 'debug',  "	* Recover alarm events");
		$alarmEvent = $myImaProtectAlarm->getAlarmEvent();
        log::add('alarme_IMA', 'debug',  "  GetAlarmEvents End");
		return $alarmEvent;
	} catch (Exception $e) {
		$this->manageErrorAPI("GetAlarmEvents",$e->getMessage());
	}
  }
  
  public function GetCamerasSnapshot()	{
  	log::add('alarme_IMA', 'debug',  "  GetCamerasSnapshot Start");
  	log::add('alarme_IMA', 'debug',  "	* instanciation api ima protect");
	try {
		$myImaProtectAlarm = $this->getInstanceIMAApi();
		log::add('alarme_IMA', 'debug',  "	* Recover alarm events");
		$cameraEvents = $myImaProtectAlarm->getCamerasSnapshot();
		log::add('alarme_IMA', 'debug',  "  GetCamerasSnapshot End");
      	return $cameraEvents;
       
	} catch (Exception $e) {
		$this->manageErrorAPI("GetCamerasSnapshot",$e->getMessage());
	}
  }

	private function getLastPictureTaken($cameraEvents) {
		log::add('alarme_IMA', 'debug',  "		* getLastPictureTaken - Start : ".$cameraEvents);
		$response='';
    	$resultArr=json_decode($cameraEvents,true);
        foreach($resultArr as $event) {        
          foreach($event['images'] as $img) {
            	if (!$this->IsNullOrEmpty($img)) {
					$response=$img;
					break;
                }
          }
		  
			if (!$this->IsNullOrEmpty($response)) {
				break;
			}
        }
		
		return $response;
	}
	
  public function buildTabCamerasEvents($cameraEvents){
    	log::add('alarme_IMA', 'debug',  "		* buildTabCamerasEvents - Start : ".$cameraEvents);
    	$resultArr=json_decode($cameraEvents,true);
    
    	//$cameraEventTab = "<link rel=\"stylesheet\" href=\"plugins/alarme_IMA/core/template/dashboard/alarme_IMA.css\">";
    	$cameraEventTab  = "<div class=\"tableWrap\">";
		$cameraEventTab .= "<table>";
		$cameraEventTab .= "<thead>";
		$cameraEventTab .= "<tr>";
    	$cameraEventTab .= "<th></th>";
		$cameraEventTab .= "<th>Date</th>";
		$cameraEventTab .= "<th>Etat</th>";
		$cameraEventTab .= "<th>Elément</th>";
		$cameraEventTab .= "<th>Photos</th>";
		$cameraEventTab .= "</tr>";
		$cameraEventTab .= "</thead>";
		$cameraEventTab .= "<tbody>";
    

        foreach($resultArr as $event) {
          $date=$event['date'];
          $etat=$event['type'];
          $element=$event['name'];
          $photos="";
          $pk=$event['pk'];
          $item=0;
          
          foreach($event['images'] as $img) {
            	if (!$this->IsNullOrEmpty($img)) {
                  if ($item > 0) {
                    $photos.=',' . $img;
                  } else {	
                    $photos=$img;
                  }
                  $item++;
                }
          }

		  
          $cameraEventTab .=  "<tr>";
		  $cameraEventTab .= "<td><i class=\"fa fa-trash\" aria-hidden=\"true\" onclick=deletePicture(\"";
		  $cameraEventTab .= $pk;
		  $cameraEventTab .= "\")></i></td>";
          $cameraEventTab .=  "<td>$date</td>";
          $cameraEventTab .=  "<td>$etat</td>";
          $cameraEventTab .=  "<td>$element</td>";
          $cameraEventTab .=  "<td>";
		  $cameraEventTab .=  "<a class=\"zoom\" href=\"#\" onclick=getPicture(\"";
		  $cameraEventTab .= $photos;
		  $cameraEventTab .=  "\") data-eqLogic_id=\"#id#\">";
		  //$cameraEventTab .=  "<a class=\"zoom\" href=\"#\" onclick=getPicture(". $photos .") data-eqLogic_id=\"#id#\">";
          
          if ($item > 1) {
            $cameraEventTab .=  " photos</a>";
          } else {
            $cameraEventTab .=  " photo</a>";
          }

          
          $cameraEventTab .=  "</td>";
          $cameraEventTab .=  "</tr>";  
        }

          $cameraEventTab .=  "</tbody>";
          $cameraEventTab .=  "</table>";
          $cameraEventTab .=  "</div>";
          log::add('alarme_IMA', 'debug',  "		* buildTabCamerasEvents- End => $cameraEventTab");
          return $cameraEventTab;
  }


  public function buildTabAlarmEvents($alarmEvents){
    	log::add('alarme_IMA', 'debug',  "		* build alarm events V2tab - Start");
    	$resultArr=json_decode($alarmEvents,true);
        //$alarmeEventTab = "<link rel=\"stylesheet\" href=\"plugins/alarme_IMA/core/template/dashboard/alarme_IMA.css\">";
    	$alarmeEventTab ="<div class=\"tableWrap\">";
		$alarmeEventTab .= "<table>";
		$alarmeEventTab .= "<thead>";
		$alarmeEventTab .= "<tr>";
		$alarmeEventTab .= "<th></th>";
		$alarmeEventTab .= "<th>Date</th>";
		$alarmeEventTab .= "<th>Evènement</th>";
		$alarmeEventTab .= "<th>Détail</th>";
		$alarmeEventTab .= "</tr>";
		$alarmeEventTab .= "</thead>";
		$alarmeEventTab .= "<tbody>";

        foreach($resultArr as $journalK=>$journalV) {
          foreach($journalV as $eventDateK=>$eventDateV){
			  
			  foreach($eventDateV as $eventDetailK=>$eventDetailV){
					$event=str_replace("'"," ",$eventDetailV['fields']['title']);
					$detail=str_replace("'"," ",$eventDetailV['fields']['subtitle']);
					$icon=$eventDetailV['fields']['icon'];
					$date=str_replace(['T','+02:00'],' ',$eventDetailV['fields']['creationDatetime']);
					
					$alarmeEventTab .=  "<tr>";
					$alarmeEventTab .=  "<td><img src=\"$icon\" alt=\"me\" style=\"width: 30px\"/</td>";
					$alarmeEventTab .=  "<td>$date</td>";
					$alarmeEventTab .=  "<td>$event</td>";
					$alarmeEventTab .=  "<td>$detail</td>";
					$alarmeEventTab .=  "</tr>"; 
			  }
			  /*
            if ($key == "fields") {
				$date="";
				$etat="";
				$lieu="";
				$element="";
				$utilisateur="";
				foreach($value as $detailEventkey=>$detailEventValue) {
                  	$detailEventValue = str_replace(array('\'', '"'), ' ', $detailEventValue);
					switch ($detailEventkey) {
						case "creation_datetime":
							$date=$this->fmt_date($detailEventValue);
							break;
						case "title":
							$etat=$detailEventValue;
							break;
						case "device_name":
							$lieu=$detailEventValue;
							break;
						case "device_type":
							$element=$detailEventValue;
							break;
						case "user_name":
							$utilisateur=$detailEventValue;
						break;
					}
				  }
				$alarmeEventTab .=  "<tr>";
				$alarmeEventTab .=  "<td>$date</td>";
				$alarmeEventTab .=  "<td>$etat</td>";
				$alarmeEventTab .=  "<td>$utilisateur</td>";
				$alarmeEventTab .=  "<td>$element</td>";
				$alarmeEventTab .=  "<td>$lieu</td>";
				$alarmeEventTab .=  "</tr>";            
            }
			*/
          }	
        }
        $alarmeEventTab .=  "</tbody>";
		$alarmeEventTab .=  "</table>";
    	$alarmeEventTab .=  "</div>";
    	log::add('alarme_IMA', 'debug',  "		* build alarm events tab - End => $alarmeEventTab");
    	return $alarmeEventTab;
  }

	public function removeDatasSession($input) {
		log::add('alarme_IMA', 'debug',  __FUNCTION__ .' - id : ' . $input );
		config::remove('imaToken_session_'.$input,'alarme_IMA');
	}
  
  public function getContactList($input){   
    log::add('alarme_IMA', 'debug',  "  getContactList Start : " . $input);
  	log::add('alarme_IMA', 'debug',  "	* instanciation api ima protect");
    $response='';
	try {
      	$eqlogic = eqLogic::byId($input);
      	$imaProtectAPI = new imaProtectNewAPI($eqlogic->getConfiguration('login_ima'),$eqlogic->getConfiguration('password_ima'),$eqlogic->getConfiguration('cfgContactList'),$input);
		
      	//if (!($imaProtectAPI->getContextFromTmpFile())) {
		if (!($imaProtectAPI->getDatasSession())) {
			log::add('alarme_IMA', 'debug',  "	* Validation couple user / mdp");
			$imaProtectAPI->Login();
			log::add('alarme_IMA', 'debug',  "	* Recuperation information compte IMA Protect");
			$imaProtectAPI->getTokens();
		}
      	
      	log::add('alarme_IMA', 'debug',  "	* Call backend getContactList()");
      	$response =  $imaProtectAPI->getContactList();
	} catch (Exception $e) {
	  $this->manageErrorAPI("getContactList",$e->getMessage());
	}
    log::add('alarme_IMA', 'debug',  "  getContactList End : ");
    return $response;
  }
  
  
  public function setAlarmToOff($pwd){   
    log::add('alarme_IMA', 'debug',  "  SetAlarmToOff Start");
  	log::add('alarme_IMA', 'debug',  "	* instanciation api ima protect");
	try {
		$myImaProtectAlarm = $this->getInstanceIMAApi();
	    log::add('alarme_IMA', 'debug',  "	* Extinction alarme");
		$myImaProtectAlarm->setAlarmToOff($pwd);
	} catch (Exception $e) {
	  $this->manageErrorAPI("setAlarmToOff",$e->getMessage());
	}
    log::add('alarme_IMA', 'debug',  "  SetAlarmToOff End");
  }
  
  public function setAlarmToOn(){   
    log::add('alarme_IMA', 'debug',  "  setAlarmToOn Start");
  	log::add('alarme_IMA', 'debug',  "	* instanciation api ima protect");
	try{
		$myImaProtectAlarm = $this->getInstanceIMAApi();
	    log::add('alarme_IMA', 'debug',  "	* Mise en route alarme");
		$myImaProtectAlarm->setAlarmToOn();
	} catch (Exception $e) {
	  $this->manageErrorAPI("setAlarmToOff",$e->getMessage());
	}
    log::add('alarme_IMA', 'debug',  "  setAlarmToOn End");
  }
  
  public function setAlarmToPartial(){   
    log::add('alarme_IMA', 'debug',  "  setAlarmToPartial Start");
  	log::add('alarme_IMA', 'debug',  "	* instanciation api ima protect");
	try{
		$myImaProtectAlarm = $this->getInstanceIMAApi();
	    log::add('alarme_IMA', 'debug',  "	* Mise en route alarme");
		$myImaProtectAlarm->setAlarmToPartial();
	} catch (Exception $e) {
	  $this->manageErrorAPI("setAlarmToOff",$e->getMessage());
	}
    log::add('alarme_IMA', 'debug',  "  setAlarmToPartial End");
  }
  
  public function getPictures($pictureUrl){   
	
    log::add('alarme_IMA', 'debug',  "  getPictures Start => $pictureUrl");
  	log::add('alarme_IMA', 'debug',  "	* instanciation api ima protect");
	try {
		$myImaProtectAlarm = $this->getInstanceIMAApi();
		$byteArray=$myImaProtectAlarm->getPictures($pictureUrl);
	    if (isset($byteArray)) {
			$str=base64_encode($byteArray);
			return base64_encode($byteArray);
		} else {
			$this->manageErrorAPI("getPictures","Empty byte array recover");
		}
	} catch (Exception $e) {
      	$this->manageErrorAPI("getPictures",$e->getMessage());
    } 
  }
  
  public function deletePictures($picture){   
    log::add('alarme_IMA', 'debug',  "  deletePictures Start => $picture");
  	log::add('alarme_IMA', 'debug',  "	* instanciation api ima protect");
	try {
		$myImaProtectAlarm = $this->getInstanceIMAApi();
		$result=$myImaProtectAlarm->deletePictures($picture);
		$cameraSnapshot=$this->GetCamerasSnapshot();
		$this->checkAndUpdateCmd('cameraSnapshot', $this->buildTabCamerasEvents($cameraSnapshot));
	} catch (Exception $e) {
      	$this->manageErrorAPI("getPictures",$e->getMessage());
    } 
    log::add('alarme_IMA', 'debug',  "  deletePictures End");
  }
  

  public function takeSnapshot($roomId) {
	log::add('alarme_IMA', 'debug',  "  takeSnapshot Start => $roomId");
  	log::add('alarme_IMA', 'debug',  "	* instanciation api ima protect");
	try {
		$myImaProtectAlarm = $this->getInstanceIMAApi();
		$result=$myImaProtectAlarm->takeSnapshot($roomId);
		log::add('alarme_IMA', 'debug',  "	* pause of 20s in order to be able to retrieve new snapshot");
		sleep(20);
		$cameraSnapshot=$this->GetCamerasSnapshot();
		if (isset($cameraSnapshot)) {
			log::add('alarme_IMA', 'debug', " * MAJ cameraSnapshotBrute");
			$this->checkAndUpdateCmd('cameraSnapshotBrute', $cameraSnapshot);
			log::add('alarme_IMA', 'debug', " * MAJ cameraSnapshot");
			$this->checkAndUpdateCmd('cameraSnapshot', $this->buildTabCamerasEvents($cameraSnapshot));
			return $this->getLastPictureTaken($cameraSnapshot);
		}
      	return '';
	} catch (Exception $e) {
      	$this->manageErrorAPI("takeSnapshot",$e->getMessage());
    } 
    log::add('alarme_IMA', 'debug',  "  takeSnapshot End");
  }

  
  private function getInstanceIMAApi(){
    try {
      	$imaProtectAPI = new imaProtectNewAPI($this->getConfiguration('login_ima'),$this->getConfiguration('password_ima'),$this->getConfiguration('cfgContactList'),$this->getId());
				
      	//if (!($imaProtectAPI->getContextFromTmpFile())) {
		if (!($imaProtectAPI->getDatasSession())) {
			log::add('alarme_IMA', 'debug',  "	* Validation couple user / mdp");
			$imaProtectAPI->Login();
			log::add('alarme_IMA', 'debug',  "	* Recuperation token IMA Protect");
			$imaProtectAPI->getTokens();
			log::add('alarme_IMA', 'debug',  "	* Recuperation informations sur les caméras IMA Protect");
			$imaProtectAPI->getOtherInfo();
			$this->setRoomsList($imaProtectAPI);
		}
      	return $imaProtectAPI;
    } catch (Exception $e) {
      	$this->manageErrorAPI("getInstanceIMAApi",$e->getMessage());
    }
  }
  

  private function setRoomsList($imaProtectAPI){
		log::add('alarme_IMA', 'debug',  "	* setRoomsList Start : ". json_encode($imaProtectAPI->rooms));
		$cmdActionScreenshot = $this->getCmd(null, 'actionScreenshot');
		if (is_object($cmdActionScreenshot)) {
          	$listValue='';
			$roomsList=$imaProtectAPI->rooms;
			for ($i = 0; $i < count($roomsList); $i++) {
              	if (!empty($roomsList[$i]["room"])){
                  if (!$this->IsNullOrEmpty($listValue)) {
                  		$listValue.= ";";
                  }
                  $listValue.= $roomsList[$i]["pk"] . "|" . $roomsList[$i]["room"];
                }
			}
			
			if ($listValue != '') {
				$cmdActionScreenshot->setConfiguration('listValue', $listValue);
				$cmdActionScreenshot->save();
			}
		}
	  log::add('alarme_IMA', 'debug',  "	* setRoomsList End");
  }


  
  private function IsNullOrEmpty($input){
    return (!isset($input) || trim($input)==='');
  }
  
  public function manageErrorAPI($function,$errorMessage) {
    	$message="$function => ".$errorMessage;
    	throw new Exception($message);
  }
  public function writeSeparateLine(){
          	log::add('alarme_IMA', 'debug',  "*********************************************************************");
  }
  
	public function toHtml($_version = 'dashboard') {
	  log::add('alarme_IMA', 'debug',  "Function toHtml - Start");
	  
	  $replace = $this->preToHtml($_version);
	  log::add('alarme_IMA', 'debug',  "Function toHtml - replace avant remplacement : $replace");
	  //$replace=array();
	  log::add('alarme_IMA', 'debug',  "Function toHtml - ap pretohtml");
	  if (!is_array($replace)) {
		log::add('alarme_IMA', 'debug',  "Function toHtml - dans le if");
		return $replace;
		log::add('alarme_IMA', 'debug',  "Function toHtml - return replace");
		
	  }

      $version = jeedom::versionAlias($_version);
		log::add('alarme_IMA', 'debug',  "Function toHtml - new version $version");
		$cmdis=$this->getCmd('info', null);
		foreach ($cmdis as $cmd) {
			$cmd_LogId=$cmd->getLogicalId(); 
			log::add('alarme_IMA', 'debug',  "Function toHtml - commande info : $cmd_LogId | id : ". $cmd->getId());
			$replace['#' . $cmd_LogId . '#'] = $cmd->execCmd();
			$replace['#' . $cmd_LogId . '_id#'] = $cmd->getId();
			$replace['#' . $cmd_LogId . '_collectDate#'] =date('d-m-Y H:i:s',strtotime($cmd->getCollectDate()));
			$replace['#' . $cmd_LogId . '_updatetime#'] =date('d-m-Y H:i:s',strtotime( $this->getConfiguration('updatetime')));
			
		}
	  
		$cmdas=$this->getCmd('action', null);
		foreach ($cmdas as $cmd) {
			$cmd_LogId=$cmd->getLogicalId(); 
			$replace['#' . $cmd_LogId . '_id#'] = $cmd->getId();
			log::add('alarme_IMA', 'debug',  "Function toHtml - commande action : $cmd_LogId | id : ". $cmd->getId());
			if ($cmd->getConfiguration('listValue', '') != '') {
				$listOption = '';
				$elements = explode(';', $cmd->getConfiguration('listValue'));
				$foundSelect = false;
				foreach ($elements as $element) {
					//list($item_val, $item_text) = explode('|', $element);
					$coupleArray = explode('|', $element);
					$item_val = $coupleArray[0];
					$item_text  = (isset($coupleArray[1])) ? $coupleArray[1]: $item_val;
				  
					$cmdValue = $cmd->getCmdValue();
					
					if (is_object($cmdValue) && $cmdValue->getType() == 'info') {
						if ($cmdValue->execCmd() == $item_val) {
							$valSelected=$item_text;
							$listOption .= '<option value="' . $item_val . '" selected>' . $item_text . '</option>';
							$foundSelect = true;
						} else {
							$listOption .= '<option value="' . $item_val . '">' . $item_text . '</option>';
						}
					} else {
						$listOption .= '<option value="' . $item_val . '">' . $item_text . '</option>';
					}
				}
				if (!$foundSelect) {
					$listOption = '<option value="" selected>Aucun</option>' . $listOption;
					$replace['#' . $cmd->getLogicalId() . '_Value#'] = 'Aucun';
				}else{
					$replace['#' . $cmd->getLogicalId() . '_Value#'] = $valSelected;
				}
				  
				
				$replace['#' . $cmd->getLogicalId() . '_listValue#'] = $listOption;
			}
		}
		  
      log::add('alarme_IMA', 'debug',  "Function toHtml - Value replace : ".json_encode($replace));	
      $html = template_replace($replace, getTemplate('core', $_version, 'default_alarme_IMA', 'alarme_IMA'));
      cache::set('widgetHtml' . $_version . $this->getId(), $html, 1);
      log::add('alarme_IMA', 'debug',  "Function toHtml - End");
      return $html;
	}
}


class alarme_IMACmd extends cmd {
  	public function execute($_options = array()) {
      	$eqlogic = $this->getEqLogic();
      	$logicalId=$this->getLogicalId();
      	log::add('alarme_IMA', 'debug',  "  * Execution cmd alarmeIMA | cmd : $logicalId => title : ".$_options['title'] . " | message : " .$_options['message']);
      	switch ($logicalId) {
				case 'setModeAlarme':
            		log::add('alarme_IMA', 'debug',  "Click on setModeAlarme equipement");
					$eqlogic->writeSeparateLine();
            		
            		if (isset($_options['title'])){
                      if ($_options['title'] == 'on') {
                        	$eqlogic->setAlarmToOn();
                        	//$eqlogic->checkAndUpdateCmd('statusAlarme', '2');
                      } else if ($_options['title'] == 'partial') {
                        	$eqlogic->setAlarmToPartial();
                        	//$eqlogic->checkAndUpdateCmd('statusAlarme', '1');
                      } else if ($_options['title'] == 'off') {
                        if (isset($_options['message'])) {
                          	$eqlogic->setAlarmToOff($_options['message']);
                          	//$eqlogic->checkAndUpdateCmd('statusAlarme', '0');
                        } else {
                          log::add('alarme_IMA', 'debug',  "Click on setModeAlarme equipement ==> message absent");
                        }
                      } else {
                        log::add('alarme_IMA', 'debug',  "Click on setModeAlarme equipement ==> action demandée non gérée");
                      }
                    } else {
                      log::add('alarme_IMA', 'debug',  "Click on setModeAlarme equipement ==> aucune action demandée");
                    }
            		//log::add('alarme_IMA', 'debug',  "Simulate click on refresh alarm status after action on it");
                    $eqlogic->writeSeparateLine();
            		$eqlogic->getCmd(null, 'refreshAlarmeStatus')->execCmd();
            		break;
          		case 'refreshAlarmeStatus':
            		$eqlogic->writeSeparateLine();
            		log::add('alarme_IMA', 'debug',  "Click on refresh alarm status");
            		$oldValue=$eqlogic->getCmd(null, 'statusAlarme')->execCmd();
					$newValue = $eqlogic->GetAlarmState();
            		
            		if (isset($newValue))  {
                      	$eqlogic->checkAndUpdateCmd('statusAlarme', $newValue);
                      	if	(isset($oldValue) && is_numeric($oldValue)) {
                          if (strcmp($oldValue,$newValue) > 0 OR  strcmp($oldValue,$newValue) < 0) {
                            log::add('alarme_IMA', 'debug',  " Le statut de l alarme a change (old|new): $oldValue | $newValue");
                            sleep(3);
                            $eqlogic->getCmd(null, 'refreshAlarmEvents')->execCmd();
                          }
                        }
                    }
            		$eqlogic->writeSeparateLine();
            		break;
          		case 'refreshAlarmEvents':
            		$eqlogic->writeSeparateLine();
            		log::add('alarme_IMA', 'debug',  "Click on refresh alarm events");
					$alarmEvent=$eqlogic->GetAlarmEvents();
            		if (isset($alarmEvent)) {
                      	log::add('alarme_IMA', 'debug', " * MAJ alarmeEventsBrute");
                      	$eqlogic->checkAndUpdateCmd('alarmeEventsBrute', $alarmEvent);
						log::add('alarme_IMA', 'debug', " * MAJ alarmeEvents");
						$eqlogic->checkAndUpdateCmd('alarmeEvents', $eqlogic->buildTabAlarmEvents($alarmEvent));
                    }
            		$eqlogic->writeSeparateLine();
            		break;            
         	 	case 'refreshCameraSnapshot':
            		$eqlogic->writeSeparateLine();
            		log::add('alarme_IMA', 'debug',  "Click on refresh camera snapshot");
            		$cameraSnapshot=$eqlogic->GetCamerasSnapshot();
            		if (isset($cameraSnapshot)) {
						log::add('alarme_IMA', 'debug', " * MAJ cameraSnapshotBrute");
                      	$eqlogic->checkAndUpdateCmd('cameraSnapshotBrute', $cameraSnapshot);
						log::add('alarme_IMA', 'debug', " * MAJ cameraSnapshot");
						$eqlogic->checkAndUpdateCmd('cameraSnapshot', $eqlogic->buildTabCamerasEvents($cameraSnapshot));
                    }
            		$eqlogic->writeSeparateLine();
                    break;
          		case 'actionScreenshot':
            		$eqlogic->writeSeparateLine();
            		log::add('alarme_IMA', 'debug',  "  * Requête title : ".$_options['title'] . " | message : " .$_options['message']);
            		if (isset($_options['message']) and isset($_options['title'])){
                      	if ($_options['title']=="get") {
	                      	return $eqlogic->getPictures($_options['message']);
                        } else if ($_options['title']=="delete"){
                          	$eqlogic->deletePictures($_options['message']);
                        }  else if ($_options['title']=="take"){
							return $eqlogic->takeSnapshot($_options['message']);
						}else {
                          	log::add('alarme_IMA', 'debug',  "  * Requête non prise en charge : ".$_options['title']);
                        }
                    } else {
                      	log::add('alarme_IMA', 'debug',  "  * Requête non complète => manque title ou message");
                    }
            		$eqlogic->writeSeparateLine();
            		break;
        }
	}

}
