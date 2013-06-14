<?
//Globale Einstellungen für die Website
//Bei Installation von MM müssen diese Daten angepasst werden
//Außerdem muss noch die .htaccess Datei angepasst werden

error_reporting(E_ALL | E_STRICT);
ini_set("display_errors", true);

date_default_timezone_set ("Europe/Berlin");

define ("GUEST_ID", "1");
define ("LOGOUT_URL", "http://localhost/gg/mm/Login.php");
define ("PATH", "/home/wolk/Garage/Web/mm/");
define ("SQL_HOST", "localhost");
define ("SQL_USR", "root");
define ("SQL_PW", "");
define ("SQL_DB", "mm");
define ("TEMPLATE", "Bouncer");
define ("URL", "http://localhost/gg/mm/");
?>
