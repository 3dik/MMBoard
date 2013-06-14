<?
//Standard-Template für MM

$this->OpenSite (true);
$this->Title (GetConfig ($this->m_DB, "MainTitle"). $this->m_strTitle);
$this->Metatag ("Content-Language", "de");
$this->Metatag ("description", "Das ultimative CMS für Kindercoder und so, so the world is yours!");
$this->Metatag ("keywords", "code, coder, coderei");
$this->LinkRel ("icon", "Icon.ico", "image/x-icon");
$this->OpenBody ();

$Result = include ("Caption.php");
if ($Result == false)
{
    $this->Exception ("SQL-Fehler - Caption");
    return false;
}
$this->OpenDiv ("Middle");
$Result = include ("Sidebar.php");
if ($Result == false)
{
    $this->Exception ("SQL-Fehler - Sidebar");
    return false;
}
$Result = include ("Content.php");
if ($Result == false)
{
    $this->Exception ("SQL-Fehler - Content");
    return false;
}
$this->CloseDiv ();
$Result = include ("Footer.php");
if ($Result == false)
{
    $this->Exception ("SQL-Fehler - Footer");
    return false;
}

$this->CloseSite ();

?>
