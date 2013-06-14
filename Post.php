<?
//Postseite

include ("Main.php");
include ("Classes/Elements/Post.php");

if (IsRight ($DB, "ViewForum") == false)
{
    new HSRedirection (2);
    return;
}

new HSPost ($DB);

?>
