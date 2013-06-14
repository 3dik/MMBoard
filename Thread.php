<?
//Threadseite

include ("Main.php");
include ("Classes/Elements/Thread.php");

if (IsRight ($DB, "ViewForum") == false)
{
    new HSRedirection (2);
    return;
}

new HSThread ($DB);

?>
