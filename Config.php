<?
//Configseite

include ("Main.php");
include ("Classes/Elements/Config.php");

if (IsRight ($DB, "ControlConfig") == false)
{
    new HSRedirection (2);
    return;
}

new HSConfig ($DB);

?>
