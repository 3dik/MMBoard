<?
//Startseite

include ("Main.php");

$Layout = new HSLayout ("Startseite", TEMPLATE, $DB);

$Layout->Textblock (GetConfig ($DB, "FrontText"), HS_LAYOUT_TEXT);

?>
