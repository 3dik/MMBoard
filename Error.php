<?
//Zeigt Fehler an

include ("Main.php");

if (isset ($_GET["ID"]) == false || $_GET["ID"] == "") die ("Ungültiger Parameter"); //HIER NOCH WEITERLEITUNG ERSTELLEN!!!

$Error = new HSError (new HSLayout ("Fehler", TEMPLATE, $DB), $DB, $_GET["ID"]);

?>
