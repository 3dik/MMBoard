<?
//Nachrichtenseite

include ("Main.php");
include ("Classes/Elements/Letter.php");

if ($_SESSION["ID"] == GUEST_ID)
{
    new HSRedirection (2);
    return;
}

new HSLetter ($DB);
?>
