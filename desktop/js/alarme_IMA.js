
/* This file is part of Jeedom.
 *
 * Jeedom is a free software: absolutely. You can redistribute it and/or modify
 * it under the terms of the GNU General Public-License as published by
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


$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
/*
 * Fonction pour l'ajout de commande, appellé automatiquement par plugin.template
 */
function addCmdToTable(_cmd) {
  	//buildContactList();
  
    if (!isset(_cmd)) {
        var _cmd = {configuration: {}};
    }
    if (!isset(_cmd.configuration)) {
        _cmd.configuration = {};
    }
   		var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
		tr += '<legend><i class="fas fa-info"></i> Commandes Infos</legend>';
		tr += '<td>';
		tr += '<span class="cmdAttr" data-l1key="id" ></span>';
		tr += '</td>';
		
		tr += '<td>';
		tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" >';
		tr += '</td>';
	   
		tr += '<td>';
		//tr += '<span class="cmdAttr" data-l1key="type"></span>';
		//tr += '   /   ';
		tr += '<span class="cmdAttr" data-l1key="subType"></span>';
		tr += '</td>';
	   
		tr += '<td>';
		tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" checked/>{{Afficher}}</label></span> ';
		if (init(_cmd.subType) == 'numeric' || init(_cmd.subType) == 'binary') {
			tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized" checked/>{{Historiser}}</label></span> ';
		}
	  
		tr += '</td>';
		tr += '<td>';
		if (is_numeric(_cmd.id)) {
			tr += '<a class="btn btn-default btn-xs cmdAction expertModeVisible" data-action="configure"><i class="fas fa-cogs"></i></a> ';
			tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> {{Evaluer}}</a>';
		}
    
    tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i></td>';
    tr += '</tr>';
  
  	if (init(_cmd.type) == 'info') {
    	$('#table_cmdi tbody').append(tr);
    	$('#table_cmdi tbody tr:last').setValues(_cmd, '.cmdAttr');
    }
  	if (init(_cmd.type) == 'action') {
    	$('#table_cmda tbody').append(tr);
    	$('#table_cmda tbody tr:last').setValues(_cmd, '.cmdAttr');
    }
	//$('#table_cmd tbody').append(tr);
    //$('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
    if (isset(_cmd.type)) {
        $('#table_cmd tbody tr:last .cmdAttr[data-l1key=type]').value(init(_cmd.type));
    }
    jeedom.cmd.changeType($('#table_cmd tbody tr:last'), init(_cmd.subType));
}

setInstructions();

function printEqLogic(_eqLogic) {
  	//console.log('PrintEqLogic : ' + _eqLogic.id);
  //console.log('conf eqlogic : ' + _eqLogic.configuration['login_ima']);
  //console.log('conf eqlogic : ' + _eqLogic.configuration['cfgContactList']);
  //console.log("json : " + JSON.stringify(_eqLogic));
  
  if ($('.eqLogicAttr[data-l1key=configuration][data-l2key=login_ima]').value() != '' && $('.eqLogicAttr[data-l1key=configuration][data-l2key=password_ima]').value() !='') {
  	buildContactList($('.eqLogicAttr[data-l1key=id]').value(), _eqLogic.configuration['cfgContactList']);
  }
};

$('#bt_SynchronizeContact').on('click',function() {
  	var eqid= $('.eqLogicAttr[data-l1key=id]').value();
	$('#div_alert').showAlert({message: '{{Synchronisation en cours}}', level: 'warning'});
  	buildContactList(eqid,'');
});

$('#bt_RemoveDatasSession').on('click',function() {
  	var eqid= $('.eqLogicAttr[data-l1key=id]').value();
	$('#div_alert').showAlert({message: '{{Suppression données de session en cours}}', level: 'warning'});
  	removeDatasSession(eqid);
});



function setInstructions() {
  	$('#div_instruction').empty();
	$('#div_instruction').html('<div class="alert alert-info">'+getInstruction()+'</div>');

}

function getInstruction() {
  	var instruction ="<span><u>Paramétrage du plugin : </u></span>";
  	instruction += "</br>";
  	instruction += "<span>&nbsp;&nbsp;&nbsp;&nbsp;- Assurez-vous que vous avez bien accès à <a href=https://www.alarme_IMA.com/>IMA Protect</a> rubrique espace client</span>";
  	instruction += "</br>";
  	instruction += "<span>&nbsp;&nbsp;&nbsp;&nbsp;- Saisissez vos login et mot de passe</span>";
  	instruction += "</br>";
  	instruction += "<span>&nbsp;&nbsp;&nbsp;&nbsp;- Sauvegardez l'équipement</span>";
  	instruction += "</br>";
  	instruction += "<span>&nbsp;&nbsp;&nbsp;&nbsp;- Synchronisez les contacts</span>";
	instruction += "</br>";
  	instruction += "<span>&nbsp;&nbsp;&nbsp;&nbsp;- Choisissez le contact</span>";
  	instruction += "</br>";
  	instruction += "<span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- Le choix du contact permet au plugin de contrôler lors de l'arrêt de l'alarme que le code de desactivation passé par l'utilisateur correspond bien à celui du contact (code XO)</span>";
  	instruction += "</br>";
  	instruction += "<span>&nbsp;&nbsp;&nbsp;&nbsp;- Sauvegardez l'équipement ... le plugin doit être opérationnel</span>";
	instruction += "</br>";
	instruction += "</br>";
	instruction += "</br>";
	instruction +="<span><u>Supprimer les données de session : </u></span>";
	instruction += "</br>";
	instruction += "<span>&nbsp;&nbsp;&nbsp;&nbsp;- permet de remettre à 0 les appels vers l'api IMA Protect (jeton, durée expiration, etc ...) à n'utiliser qu'en cas de dysfonctionnement du plugin</span>";
  	return instruction;
}




function buildContactList(id,pkContact) {
    $.ajax({// fonction permettant de faire de l'ajax
        type: "POST", // méthode de transmission des données au fichier php
        url: "plugins/alarme_IMA/core/ajax/alarme_IMA.ajax.php", // url du fichier php
        data: {
            action: "getContactList",
          	input:id
        },
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error);
          	$('#div_alert').showAlert({message: '{{Erreur lors de la synchronisation}}', level: 'danger'});
        },
        success: function (data) { // si l'appel a bien fonctionné
            if (data.state != 'ok') {
				$('#div_alert').showAlert({message: '{{Erreur lors de la synchronisation}}', level: 'danger'});
            } else {
              var selectOption;
              var myObj = JSON.parse(JSON.stringify(data.result));

              for (i in myObj.contactList) {
                if (i ==0) {
                  	selectOption +='<option value=\"0\">{{}}</option>';
                }
                if (pkContact != '' && myObj.contactList[i].pk == pkContact) {
                  	selectOption +='<option selected value=\"'+myObj.contactList[i].pk +'\">{{' ;
                  	selectOption += myObj.contactList[i].fullName;
                  	selectOption += ' }}</option>';
                } else {
                  	selectOption +='<option value=\"'+myObj.contactList[i].pk +'\">{{';
                  	selectOption += myObj.contactList[i].fullName;
                  	selectOption += ' }}</option>';
                }
              }
              $('.eqLogicAttr[data-l1key=configuration][data-l2key=cfgContactList]').find('option').remove().end().append(selectOption);
              $('#div_alert').showAlert({message: '{{Synchronisation terminée avec succès}}', level: 'success'});
          }
        }
});
}

function removeDatasSession(id) {
    $.ajax({// fonction permettant de faire de l'ajax
        type: "POST", // méthode de transmission des données au fichier php
        url: "plugins/alarme_IMA/core/ajax/alarme_IMA.ajax.php", // url du fichier php
        data: {
            action: "removeDatasSession",
          	input:id
        },
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error);
          	$('#div_alert').showAlert({message: '{{Erreur lors de la suppression des données de sessions}}', level: 'danger'});
        },
        success: function (data) { // si l'appel a bien fonctionné
            if (data.state == 'ok') {
				$('#div_alert').showAlert({message: '{{Données de session correctement supprimées}}', level: 'success'});
        }
		
	}
	});
}
