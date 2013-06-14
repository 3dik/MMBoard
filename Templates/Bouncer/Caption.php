<?
//Obere Menüleiste

$this->OpenDiv ("Caption");

$strSlogan = GetConfig ($this->m_DB, "Slogan");
if ($strSlogan == "") $strSlogan = "kein Slogan vorhanden ist";
$strLocked = " ";
if (GetConfig ($this->m_DB, "Locked") != "") $strLocked = " - <strong>Sperrmodus</strong> ";

$this->Textblock ("<p>MMBoard ". GetConfig ($this->m_DB, "SiteVersion"). $strLocked. "- Weil ". $strSlogan. "</p>", HS_LAYOUT_LINE);
$this->OpenList ();
$this->Listitem ("<a href=\"./\">Startseite</a>");
if (IsRight ($this->m_DB, "ViewForum")) $this->Listitem ("<a href=\"Forum.php\">Forum</a>");
$this->Listitem ("<a href=\"User.php\">Userliste</a>");
$this->Listitem ("<a href=\"Group.php\">Gruppenliste</a>");
$this->Listitem ("<a href=\"Right.php\">Rechteliste</a>");
if (IsRight ($this->m_DB, "ControlConfig")) $this->Listitem ("<a href=\"Config.php\">Configliste</a>");
$this->CloseList ();
$this->CloseDiv ();

return true;

?>
