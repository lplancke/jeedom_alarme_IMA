# Changelog plugin Alarme IMA

>**IMPORTANT**
>
>Pour rappel s'il n'y a pas d'information sur la mise à jour, c'est que celle-ci concerne uniquement de la mise à jour de documentation, de traduction ou de texte.

# 03/01/2023
- Correction libellé onglet Notification dans paramétrage d'un équipement
- Correction bug sur la MAJ des cmds lors de la création d'un nouvel équipement IMA 
   *  Statut alarme
   *  Statut binaire alarme
   *  Mode alarme

# 16/11/2022
Ajout valorisation et affichage des commandes de type info dans l'onglet des commandes
Gestion envoi de notification en fonction du journal d'évènement

* activation / désactivation
* intrusion
* porte ouverte et alarme activée

# 21/10/2022
Ne plus afficher dans la log le corps du retour de l'api IMA si celui-ci n'est pas de type JSON

# 20/10/2022
Correction bug récupération des tokens IMA

# 08/11/2021
- Modification gestion des jetons de session (stockage en BDD)
- possibilité de supprimer les jetons de session (permet de réinitialiser les appels aux api IMA Protect)
- corrections suite aux modifications de l'api IMA Protect

# 23/07/2021
- récupération des évènements de l'alarme

# 08/06/2021
- Prise en charge de la fonction de prise d'instantanée par les caméras IMA

# 29/05/2021
- Mise en place lien vers la documentation du plugin
- Mise en place lien vers le changelog

# 28/05/2021
- Mise en stable de la nouvelle version avec consommation des nouveaux web service IMA
