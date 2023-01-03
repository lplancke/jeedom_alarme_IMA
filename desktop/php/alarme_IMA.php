<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
$plugId = 'alarme_IMA';
$plugin = plugin::byId($plugId);
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
$plugName=$plugin->getName();

?>

<div class="row row-overflow">
   <div class="col-xs-12 eqLogicThumbnailDisplay">
  <legend><i class="fas fa-cog"></i>  {{Gestion}}</legend>
  <div class="eqLogicThumbnailContainer">
    <div class="cursor eqLogicAction logoPrimary" data-action="add">
        <i class="fas fa-plus-circle"></i>
        <br>
        <span>{{Ajouter}}</span>
    </div>
  <div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
      <i class="fas fa-wrench"></i>
    <br>
    <span>{{Configuration}}</span>
  </div>
  </div>
  <legend><i class="fas fa-table"></i> {{Mes alarmes IMA Protect}}</legend>
   <input class="form-control" placeholder="{{Rechercher}}" id="in_searchEqlogic" />
<div class="eqLogicThumbnailContainer">
    <?php
foreach ($eqLogics as $eqLogic) {
	$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
	echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
	echo '<img src="' . $plugin->getPathImgIcon() . '"/>';
	echo '<br>';
	echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
	echo '</div>';
}
?>
</div>
</div>
<div class="col-xs-12 eqLogic" style="display: none;">
		<div class="input-group pull-right" style="display:inline-flex">
			<span class="input-group-btn">
				<a class="btn btn-default btn-sm eqLogicAction roundedLeft" data-action="configure"><i class="fa fa-cogs"></i> {{Configuration avancée}}</a><a class="btn btn-default btn-sm eqLogicAction" data-action="copy"><i class="fas fa-copy"></i> {{Dupliquer}}</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a><a class="btn btn-danger btn-sm eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
			</span>
		</div>
  <ul class="nav nav-tabs" role="tablist">
    <li role="presentation">
		<a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay">
			<i class="fa fa-arrow-circle-left"></i>
		</a>
	</li>
    <li role="presentation" class="active">
		<a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab">
			<i class="fas fa-tachometer-alt"></i> {{Equipement}}
		</a>
	</li>
	<li role="presentation">
		<a href="#tabNotifications" aria-controls="home" role="tab" data-toggle="tab">
			<i class="fas fa-bell"></i> 
			{{Notifications}}
		</a>
	</li>
    <li role="presentation">
		<a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab">
			<i class="fa fa-list-alt"></i> {{Commandes}}
		</a>
	</li>
  </ul>
  <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
    <div role="tabpanel" class="tab-pane active" id="eqlogictab">
      <br/>
	<div class="row">
	<div class="col-sm-7">
    <form class="form-horizontal">
        <fieldset>
            <div class="form-group">
                <label class="col-sm-3 control-label">{{Nom de l'équipement alarme IMA}}</label>
                <div class="col-sm-3">
                    <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                    <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement alarme IMA}}"/>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-3 control-label" >{{Objet parent}}</label>
                <div class="col-sm-3">
                    <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
                        <option value="">{{Aucun}}</option>
                        <?php
foreach (jeeObject::all() as $object) {
	echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
}
?>
                   </select>
               </div>
           </div>
	   <div class="form-group">
                <label class="col-sm-3 control-label">{{Catégorie}}</label>
                <div class="col-sm-9">
                 <?php
                    foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                    echo '<label class="checkbox-inline">';
                    echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
                    echo '</label>';
                    }
                  ?>
               </div>
           </div>
	<div class="form-group">
		<label class="col-sm-3 control-label"></label>
		<div class="col-sm-9">
			<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
			<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
		</div>
	</div>
       <div class="form-group">
        <label class="col-sm-3 control-label">{{Login IMA téléassistance}}</label>
        <div class="col-sm-3">
            <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="login_ima" placeholder="votre login ima téléassistance"/>
        </div>
    </div>
	<div class="form-group">
        <label class="col-sm-3 control-label">{{Password IMA téléassistance}}</label>
        <div class="col-sm-3">
            <input type="password" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="password_ima" placeholder="votre password ima téléassistance"/>
        </div>
    </div>
    </br>
	<div class="form-group">
		<label class="col-sm-3 control-label">{{Contact}}
			<sup>
				<i class="fa fa-question-circle tooltips" title="{{Contact utilisé pour le code de validation XO}}"></i>
			</sup>
		</label>
		<div class="col-sm-3">
			<div class="input-group">
				<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="cfgContactList">
				</select>
			</div>
		</div>
        <a class="btn btn-danger btn-sm cmdAction" id="bt_SynchronizeContact"><i class="fas fa-sync"></i> {{Synchroniser les contacts}}</a>
		
	</div>
</fieldset>
	
</form>
</div>
						<div id="info" class="col-sm-4">
							<fieldset>
								<legend>{{Informations}}</legend>
								<div class="form-group">									
									<div id="div_instruction"></div>
									<a class="btn btn-danger btn-sm cmdAction" id="bt_RemoveDatasSession"><i class="fas fa-sync tooltips"  title="{{Contact utilisé pour le code de validation XO}}"></i> {{Supprimer les données de session}}</a>
								</div>
								
							</fieldset>					
						</div>
					</div>

				</div
</div>
<!--Modif ChD-->
<div role="tabpanel" class="tab-pane" id="commandtab">
<legend>
<center class="title_cmdtable">{{Tableau de commandes <?php echo ' - '.$plugName.': ';?>}}
	<span class="eqName"></span>
</center>
</legend>

<legend><i class="fas fa-info"></i>  {{Infos}}</legend>
	
	<table id="table_cmdi" class="table table-bordered table-condensed ">
		<!--<table class="table  tablesorter tablesorter-bootstrap tablesorter hasResizable table-striped hasFilters" id="table_update" style="margin-top: 5px;" role="grid"><colgroup class="tablesorter-colgroup"></colgroup>
		</table>-->
		<thead>
			<tr>
				<th style="width: 80px;">Id</th>
				<th style="width: 280px;">{{Nom}}</th>
				<th style="width: 120px;">{{Type}}</th>
                <th style="">{{Etat}}</th>
				<th style="width: 280px;">{{Options}}</th>
				<th style="width: 140px;">{{Action}}</th>
			</tr>
		</thead>
		<tbody></tbody>
	</table>

	<!--<legend><i class="fas fa-list-alt"></i>  {{Actions}}</legend>-->
	<legend><i class="fas fa-play"></i>  {{Actions}}</legend>
	<table id="table_cmda" class="table table-bordered table-condensed">
		
		<thead>
			<tr>
				<th style="width: 80px;">Id</th>
				<th style="width: 280px;">{{Nom}}</th>
				<th style="width: 120px;">{{Type}}</th>
                <th style="">{{Etat}}</th>
				<th style="width: 280px;">{{Options}}</th>
				<th style="width: 140px;">{{Action}}</th>
			</tr>
		</thead>
		<tbody></tbody>
	</table>
	
	<legend><i class="fas fa-sync"></i>  {{Refresh}}</legend>
	<table id="table_cmdr" class="table table-bordered table-condensed">
		
		<thead>
			<tr>
				<th style="width: 80px;">Id</th>
				<th style="width: 280px;">{{Nom}}</th>
				<th style="width: 120px;">{{Type}}</th>
                <th style="">{{Etat}}</th>
				<th style="width: 280px;">{{Options}}</th>
				<th style="width: 140px;">{{Action}}</th>
			</tr>
		</thead>
		<tbody></tbody>
	</table>


</div>

<div role="tabpanel" class="tab-pane" id="tabNotifications">
	<br/>
	<div class="row">
		<div class="col-sm-7">
			<form class="form-horizontal">
				<fieldset>
					
					<div class="form-group">
						<label class="col-sm-3 control-label" >
							{{Activer les notifications}}
							<sup>
								<i class="fa fa-question-circle tooltips" title="{{Voulez-vous recevoir des notifications sur certains évènements ?}}"></i>
							</sup>
						</label>
						<div class="col-sm-5">
							<input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="cfgSendMsg"/>
						</div>
					</div>
					<div class="form-group" data-l1key="configuration" data-l2key="cfgFormChangeStatus">
						<label class="col-md-6 control-label">{{Statut de l'alarme}}
							<sup>
								<i class="fa fa-question-circle tooltips" title="{{Notification lors d'un changement de statut de l'alarme}}"></i>
							</sup>
						</label>
						<div class="col-md-6">
							<div class="input-group">
								<input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="cfgAlertChangeStatus"/>
							</div>
						</div>
					</div>
					<div class="form-group" data-l1key="configuration" data-l2key="cfgFormAlertIntrusion">
						<label class="col-md-6 control-label">{{Intrusion}}
							<sup>
								<i class="fa fa-question-circle tooltips" title="{{Notification lors d'une  alerte d'intrusion}}"></i>
							</sup>
						</label>
						<div class="col-md-6">
							<div class="input-group">
								<input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="cfgAlertIntrusion"/>
							</div>
						</div>
					</div>
					<div class="form-group" data-l1key="configuration" data-l2key="cfgFormAlertOpenedDoor">
						<label class="col-md-6 control-label">{{Ouvrant}}
							<sup>
								<i class="fa fa-question-circle tooltips" title="{{Notification lors d'une alerte porte ouverte}}"></i>
							</sup>
						</label>
						<div class="col-md-6">
							<div class="input-group">
								<input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="cfgAlertOpenedDoor"/>
							</div>
						</div>
					</div>
						</br>
						</br>

					<!--</div>		-->
					<div class="form-group" data-l1key="configuration" data-l2key="cfgFormCmdSendMsg">
						<label class="col-sm-3 control-label">{{Commande de notification}}
							<sup>
								<i class="fa fa-question-circle tooltips" title="{{Sélectionner la commande permettant d'envoyer la notification}}"></i>
							</sup>
						</label>
						<div class="col-sm-5">
							<div class="input-group">
								<input type="text" class="eqLogicAttr form-control CmdAction" data-l1key="configuration" data-l2key="cfgCmdSendMsg" placeholder="{{Séléctionner une commande}}"/>
								<span class="input-group-btn">
									<a class="btn btn-success btn-sm listCmdActionMessage" data-type="action">
										<i class="fa fa-list-alt"></i>
									</a>
								</span>
							</div>
						</div>
					</div>
					<div class="form-group" data-l1key="configuration" data-l2key="cfgFormMsgTitle">
						<label class="col-sm-3 control-label" >
							{{Titre de la notification}}
							<sup>
								<i class="fa fa-question-circle tooltips" title="{{Saisissez le titre de la notification }}"></i>
							</sup>
						</label>
						<div class="col-sm-5">
							<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="cfgMsgTitle" placeholder="{{Titre de la notification}}"/>
						</div>
					</div>
				</fieldset>
			</form>
			

		</div>
		<div id="infoNodeDesign" class="col-sm-4">
			<fieldset>
				<legend>{{Informations}}</legend>
				<div class="form-group">
					<div id="div_instruction_notification"></div>
				</div>							
			</fieldset>					
		</div> 
		
	</div>

</div>

<!--
      <div role="tabpanel" class="tab-pane" id="commandtab">
<a class="btn btn-success btn-sm cmdAction pull-right" data-action="add" style="margin-top:5px;"><i class="fa fa-plus-circle"></i> {{Commandes}}</a><br/><br/>
<table id="table_cmd" class="table table-bordered table-condensed">
    <thead>
        <tr>
            <th>{{Nom}}</th>
            <th>{{Type}}</th>
            <th style="width: 150px;">{{Paramètres}}</th>
            <th style="width: 150px;"></th>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>
</div>
</div>

</div>
</div>
-->

<?php include_file('desktop', 'alarme_IMA', 'js', 'alarme_IMA');?>
<?php include_file('core', 'plugin.template', 'js');?>
