<?
//Forumseite

include ("Main.php");
include ("Classes/Elements/Forum.php");

if (IsRight ($DB, "ViewForum") == false)
{
    new HSRedirection (2);
    return;
}

new HSForum ($DB);

?>
