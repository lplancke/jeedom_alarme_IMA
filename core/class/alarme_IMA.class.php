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
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';


class alarme_IMA extends eqLogic {
    /*     * *************************Attributs****************************** */
	const IMA_ON=2;
	const IMA_PARTIAL=1;
	const IMA_OFF=0;
	const IMA_UNKNOWN=-1;
	const IMA_IGNORED=-2;

    /*     * ***********************Methode static*************************** */
	private static function getIma($url, $cookie)
	{
		log::add('alarme_IMA', 'debug', "Appel get url $url");
		$ch=curl_init($url);
		curl_setopt($ch, CURLOPT_COOKIE, $cookie);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		$httpcode= curl_getinfo($ch, CURLINFO_HTTP_CODE);		
		log::add('alarme_IMA', 'debug', "Code retour http: $httpcode");
		log::add('alarme_IMA', 'debug', "Retour: $result");
		curl_close($ch);
		return array($httpcode, $result);
	}
	
	private static function postIma($url, $postData)
	{
		log::add('alarme_IMA', 'debug', "Appel post url $url");
		$ch = curl_init ($url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		log::add('alarme_IMA', 'debug', "Code retour http: $httpcode");
		log::add('alarme_IMA', 'debug', "Retour: $result");
		curl_close($ch);
		return array($httpcode, $result);
		
	}
	
	private function getNumericStatus()
	{
		$tmpFile=sys_get_temp_dir()."/alarme_IMA_session_".$this->getId();
		log::add('alarme_IMA', 'debug', "Fichier temporaire: $tmpFile");
		
		if (is_file($tmpFile)) {
			$fd=fopen($tmpFile, "r");
			$sessionId=trim(fgets($fd, 4096));
			$pk=trim(fgets($fd, 4096));
			fclose($fd);
			log::add('alarme_IMA', 'debug', "sessionId: $sessionId, pk: $pk");
			$url="https://pilotageadistance.imateleassistance.com/proxy/api/1.0/hss/$pk/status/?_=".time()."000";
			list($httpcode, $result)=self::getIma($url, "sessionid=".$sessionId);
		}
		else $httpcode=0;
		
		
		log::add('alarme_IMA', 'debug', "Status de retour status sur première tentative: $httpcode");
		if ($httpcode!=200)
		{
			$login_ima=$this->getConfiguration('login_ima');
			$password_ima=$this->getConfiguration('password_ima');
			log::add('alarme_IMA', 'debug', "Login: $login_ima");
			//log::add('alarme_IMA', 'debug', "Password: $password_ima");

			$url="https://pilotageadistance.imateleassistance.com/proxy/api/1.0/keychain/web-login/";
			list($httpcode, $result)=self::postIma($url, "username=".urlencode($login_ima)."&password=".urlencode($password_ima));
			if ($httpcode!=200)
			{
				if ($httpcode==404) return self::IMA_IGNORED; // j'ai un 404 précisément toutes les 7 minutes, un bug de fonctionnement du site probablement...
				log::add('alarme_IMA', 'error', "Vérifiez vos identifiants ($httpcode). Ceux-ci doivent permettre de s'authentifier sur le site https://pilotageadistance.imateleassistance.com.");
				return self::IMA_UNKNOWN;
			}

			preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $result, $matches);
			$cookies = array();
			foreach($matches[1] as $item) {
				parse_str($item, $cookie);
				$cookies = array_merge($cookies, $cookie);
			}
			$sessionId=$cookies["sessionid"];
			log::add('alarme_IMA', 'debug', 'Login ok, sessionId: '.$sessionId);
			curl_close($ch); 


			$url="https://pilotageadistance.imateleassistance.com/proxy/api/1.0/hss/me/?_=".time()."000";
			list($httpcode, $result)=self::getIma($url, "sessionid=".$sessionId);
			if ($httpcode!=200)
			{
				if ($httpcode==404) return self::IMA_IGNORED; // j'ai un 404 précisément toutes les 7 minutes, un bug de fonctionnement du site probablement...
				log::add('alarme_IMA', 'error', "Erreur d'appel au site pilotageadistance (étape me)");
				return self::IMA_UNKNOWN;
			}

			$resultArr=json_decode($result,true);
			// $pk=$resultArr[0]["fields"]["contract_set"][0]["fields"]["site"]["pk"];
			// remplacé par la ligne ci-dessous car pour certains utilisateurs, les 2 valeurs sont différentes et c'est la deuxième qui est la bonne
			$pk=$resultArr[0]["fields"]["contract_set"][0]["fields"]["site"]["fields"]["hss_pk"];
			if (!$pk) {
				log::add('alarme_IMA', 'error', "Impossible de trouver la clef pk");
				return self::IMA_UNKNOWN;
			}
			curl_close($ch); 

			$fd=fopen($tmpFile, "w");
			fputs($fd, $sessionId."\n");
			fputs($fd, $pk."\n");
			fclose($fd);

			$url="https://pilotageadistance.imateleassistance.com/proxy/api/1.0/hss/$pk/status/?_=".time()."000";
			list($httpcode, $result)=self::getIma($url, "sessionid=".$sessionId);

			if ($httpcode!=200)
			{
				if ($httpcode==404) return self::IMA_IGNORED; // j'ai un 404 précisément toutes les 7 minutes, un bug de fonctionnement du site probablement...
				log::add('alarme_IMA', 'error', "Erreur d'appel au site pilotageadistance (étape status): $httpcode");
				return self::IMA_UNKNOWN;
			}

		}
		$resultArr=json_decode($result,true);
		$newStatus=$resultArr[0]["fields"]["status"];
		if (!$newStatus) {
			log::add('alarme_IMA', 'error', "Impossible de trouver le status");
			return self::IMA_UNKNOWN;
		}
		log::add('alarme_IMA', 'debug', "Nouveau status alarme: $newStatus");
		
		$convStatusToNumeric=array(
			"on" => "2",
			"partial" => "1",
			"off"=> "0"
			);
		$numericStatus=$convStatusToNumeric[$newStatus];
		log::add('alarme_IMA', 'debug', "Nouveau status numerique alarme: $numericStatus");
		return $numericStatus;
	}

	
    /*
     * Fonction exécutée automatiquement toutes les minutes par Jeedom    */
	public static function cron() {
		log::add('alarme_IMA', 'debug', 'Démarrage du cron minute');
		foreach (eqLogic::byType('alarme_IMA', true) as $alarme_IMA) {

			$numericStatus=$alarme_IMA->getNumericStatus();

			log::add('alarme_IMA', 'debug', "Nouveau status numerique alarme: $numericStatus");
			if ($numericStatus!=self::IMA_IGNORED)
			{
				//	checkAndUpdateCmd n'insère pas une donnée chaque fois dans l'historique
				$alarme_IMA->checkAndUpdateCmd('statusAlarme', $numericStatus);
				//	$cmd=$alarme_IMA->getCmd('info', 'statusAlarme');
				//	$cmd->event($numericStatus);
			}
			else
			{
				log::add('alarme_IMA', 'debug', "Retour ignoré");
			}
		}
	}
 


    /*
     * Fonction exécutée automatiquement toutes les heures par Jeedom
      public static function cronHourly() {

      }
     */

    /*
     * Fonction exécutée automatiquement tous les jours par Jeedom 
      public static function cronDayly() {

      }
	*/


    /*     * *********************Méthodes d'instance************************* */

    public function preInsert() {
        
    }

    public function postInsert() {
        
    }

    public function preSave() {
        
    }

    public function postSave() {
		log::add('alarme_IMA', 'debug',  "appel postSave");
        
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
		log::add('alarme_IMA', 'debug',  "appel postUpdate");
        $cmd = $this->getCmd(null, 'statusAlarme');
		if (! is_object($cmd))
		{
			$alarme_IMACmd = new alarme_IMACmd();
			$alarme_IMACmd->setName(__('Statut alarme', __FILE__));
			$alarme_IMACmd->setEqLogic_id($this->id);
			$alarme_IMACmd->setLogicalId('statusAlarme');
			$alarme_IMACmd->setConfiguration('data', 'statusAlarme');
			$alarme_IMACmd->setType('info');
			$alarme_IMACmd->setSubType('numeric');
			$alarme_IMACmd->setTemplate('dashboard', 'line');
			$alarme_IMACmd->setTemplate('mobile', 'line');
//			$alarme_IMACmd->setUnite('L');
			$alarme_IMACmd->setIsHistorized(1);
			$alarme_IMACmd->setDisplay('graphStep', '1');
			$alarme_IMACmd->setConfiguration("MaxValue", self::IMA_ON);
			$alarme_IMACmd->setConfiguration("MinValue", self::IMA_UNKNOWN);
			$alarme_IMACmd->save();
        }
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
}

class alarme_IMACmd extends cmd {
    /*     * *************************Attributs****************************** */

    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /*
     * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
      public function dontRemoveCmd() {
      return true;
      }
     */

	 public function formatValueWidget($v)
	 {
		$arr=array(
					0=>'<div style="margin:10px;background:#d9534f">OFF</div>', 
					1=>'<div style="margin:10px;background:#f0ad4e">PARTIAL</div>', 
					2=>'<div style="margin:10px;background:#5cb85c">ON</div>',
					-1=>'<div style="margin:10px;background:#333333">UNKNOWN</div>'
				);
		return $arr[$v];
	 }
	 
/*
	public function toHtml($_version = 'dashboard', $_options = '', $_cmdColor = null) {
		$version2 = jeedom::versionAlias($_version, false);
		if ($this->getDisplay('showOn' . $version2, 1) == 0) {
			return '';
		}
		$version = jeedom::versionAlias($_version);
		$html = '';
		$replace = array(
			'#id#' => $this->getId(),
			'#name#' => $this->getName(),
			'#name_display#' => ($this->getDisplay('icon') != '') ? $this->getDisplay('icon') : $this->getName(),
//			'#name_display#' => "mon display",
			'#history#' => '',
			'#displayHistory#' => 'display : none;',
			'#unite#' => $this->getUnite(),
			'#minValue#' => $this->getConfiguration('minValue', 0),
			'#maxValue#' => $this->getConfiguration('maxValue', 100),
			'#logicalId#' => $this->getLogicalId(),
			'#uid#' => 'cmd' . $this->getId() . eqLogic::UIDDELIMITER . mt_rand() . eqLogic::UIDDELIMITER,
			'#version#' => $_version,
			'#hideCmdName#' => '',
		);
		if ($this->getConfiguration('listValue', '') != '') {
			$listOption = '<option value="">Aucun</option>';
			$elements = explode(';', $this->getConfiguration('listValue', ''));
			foreach ($elements as $element) {
				$coupleArray = explode('|', $element);
				$cmdValue = $this->getCmdValue();
				if (is_object($cmdValue) && $cmdValue->getType() == 'info') {
					if ($cmdValue->execCmd() == $coupleArray[0]) {
						$listOption .= '<option value="' . $coupleArray[0] . '" selected>' . $coupleArray[1] . '</option>';
					} else {
						$listOption .= '<option value="' . $coupleArray[0] . '">' . $coupleArray[1] . '</option>';
					}
				} else {
					$listOption .= '<option value="' . $coupleArray[0] . '">' . $coupleArray[1] . '</option>';
				}
			}
			$replace['#listValue#'] = $listOption;
		}
		if ($this->getDisplay('showNameOn' . $version2, 1) == 0) {
			$replace['#hideCmdName#'] = 'display:none;';
		}
		if ($this->getDisplay('showIconAndName' . $version2, 0) == 1) {
			$replace['#name_display#'] = $this->getDisplay('icon') . ' ' . $this->getName();
		}
		$template = $this->getWidgetTemplateCode($_version);

		if ($_cmdColor === null && $version != 'scenario') {
			$eqLogic = $this->getEqLogic();
			$vcolor = ($version == 'mobile') ? 'mcmdColor' : 'cmdColor';
			if ($eqLogic->getPrimaryCategory() == '') {
				$replace['#cmdColor#'] = jeedom::getConfiguration('eqLogic:category:default:' . $vcolor);
			} else {
				$replace['#cmdColor#'] = jeedom::getConfiguration('eqLogic:category:' . $eqLogic->getPrimaryCategory() . ':' . $vcolor);
			}
		} else {
			$replace['#cmdColor#'] = $_cmdColor;
		}

		if ($this->getType() == 'info') {
			$replace['#state#'] = '';
			$replace['#tendance#'] = '';
			$replace['#state#'] = $this->execCmd();
			if (strpos($replace['#state#'], 'error::') !== false) {
				$template = getTemplate('core', $version, 'cmd.error');
				$replace['#state#'] = str_replace('error::', '', $replace['#state#']);
			} else {
				if ($this->getSubType() == 'binary' && $this->getDisplay('invertBinary') == 1) {
					$replace['#state#'] = ($replace['#state#'] == 1) ? 0 : 1;
				}
				if ($this->getSubType() == 'numeric' && trim($replace['#state#']) === '') {
					$replace['#state#'] = 0;
				}
			}
			if (method_exists($this, 'formatValueWidget')) {
				$replace['#state#'] = $this->formatValueWidget($replace['#state#']);
			}
			$replace['#collectDate#'] = $this->getCollectDate();
			$replace['#valueDate#'] = $this->getValueDate();
			$replace['#alertLevel#'] = $this->getCache('alertLevel', 'none');
			if ($this->getIsHistorized() == 1) {
				$replace['#history#'] = 'history cursor';
				if (config::byKey('displayStatsWidget') == 1 && strpos($template, '#displayHistory#') !== false) {
					if ($this->getDisplay('showStatsOn' . $version2, 1) == 1) {
						$startHist = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . ' -' . config::byKey('historyCalculPeriod') . ' hour'));
						$replace['#displayHistory#'] = '';
						$historyStatistique = $this->getStatistique($startHist, date('Y-m-d H:i:s'));
						if ($historyStatistique['avg'] == 0 && $historyStatistique['min'] == 0 && $historyStatistique['max'] == 0) {
							$replace['#averageHistoryValue#'] = round($replace['#state#'], 1);
							$replace['#minHistoryValue#'] = round($replace['#state#'], 1);
							$replace['#maxHistoryValue#'] = round($replace['#state#'], 1);
						} else {
							$replace['#averageHistoryValue#'] = round($historyStatistique['avg'], 1);
							$replace['#minHistoryValue#'] = round($historyStatistique['min'], 1);
							$replace['#maxHistoryValue#'] = round($historyStatistique['max'], 1);
						}
						$startHist = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . ' -' . config::byKey('historyCalculTendance') . ' hour'));
						$tendance = $this->getTendance($startHist, date('Y-m-d H:i:s'));
						if ($tendance > config::byKey('historyCalculTendanceThresholddMax')) {
							$replace['#tendance#'] = 'fa fa-arrow-up';
						} else if ($tendance < config::byKey('historyCalculTendanceThresholddMin')) {
							$replace['#tendance#'] = 'fa fa-arrow-down';
						} else {
							$replace['#tendance#'] = 'fa fa-minus';
						}
					}
				}
			}
			$parameters = $this->getDisplay('parameters');
			if (is_array($parameters)) {
				foreach ($parameters as $key => $value) {
					$replace['#' . $key . '#'] = $value;
				}
			}
		log::add('alarme_IMA', 'debug', "ici");
			return template_replace($replace, $template);
		} else {
			$cmdValue = $this->getCmdValue();
			if (is_object($cmdValue) && $cmdValue->getType() == 'info') {
				$replace['#state#'] = $cmdValue->execCmd();
				$replace['#valueName#'] = $cmdValue->getName();
				$replace['#unite#'] = $cmdValue->getUnite();
				if (trim($replace['#state#']) === '' && ($cmdValue->getSubtype() == 'binary' || $cmdValue->getSubtype() == 'numeric')) {
					$replace['#state#'] = 0;
				}
			} else {
				$replace['#state#'] = ($this->getLastValue() !== null) ? $this->getLastValue() : '';
				$replace['#valueName#'] = $this->getName();
				$replace['#unite#'] = $this->getUnite();
			}
			$parameters = $this->getDisplay('parameters');
			if (is_array($parameters)) {
				foreach ($parameters as $key => $value) {
					$replace['#' . $key . '#'] = $value;
				}
			}

			$html .= template_replace($replace, $template);
			if (trim($html) == '') {
				return $html;
			}
			if ($_options != '') {
				$options = jeedom::toHumanReadable($_options);
				if (is_json($options)) {
					$options = json_decode($options, true);
				}
				if (is_array($options)) {
					foreach ($options as $key => $value) {
						$replace['#' . $key . '#'] = $value;
					}
				}
			}
			if (!isset($replace['#title#'])) {
				$replace['#title#'] = '';
			}
			if (!isset($replace['#message#'])) {
				$replace['#message#'] = '';
			}
			if (!isset($replace['#slider#'])) {
				$replace['#slider#'] = '';
			}
			if (!isset($replace['#color#'])) {
				$replace['#color#'] = '';
			}
			$replace['#title_placeholder#'] = $this->getDisplay('title_placeholder', __('Titre', __FILE__));
			$replace['#message_placeholder#'] = $this->getDisplay('message_placeholder', __('Message', __FILE__));
			$replace['#message_cmd_type#'] = $this->getDisplay('message_cmd_type', 'info');
			$replace['#message_cmd_subtype#'] = $this->getDisplay('message_cmd_subtype', '');
			$replace['#message_disable#'] = $this->getDisplay('message_disable', 0);
			$replace['#title_disable#'] = $this->getDisplay('title_disable', 0);
			$replace['#title_color#'] = $this->getDisplay('title_color', 0);
			$replace['#title_possibility_list#'] = str_replace("'", "\'", $this->getDisplay('title_possibility_list', ''));
			$replace['#slider_placeholder#'] = $this->getDisplay('slider_placeholder', __('Valeur', __FILE__));
			$replace['#other_tooltips#'] = ($replace['#name#'] != $this->getName()) ? $this->getName() : '';
			$html = template_replace($replace, $html);
			
			return $html;
		}
	}

*/
    /*     * **********************Getteur Setteur*************************** */


}


