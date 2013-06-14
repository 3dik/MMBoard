<?
//Linke Menüleiste

$this->OpenDiv ("Sidebar");

$Stmt = $this->m_DB->prepare ("SELECT `Name` FROM `Users` WHERE `ID`=?;");
if ($Stmt == false) return false;
$Stmt->bind_param ("i", $_SESSION["ID"]);
if ($Stmt->execute () == false) return false;
$Stmt->bind_result ($strUserName);
if ($Stmt->fetch () == false) $strUserName = "Unbekannter User";
$Stmt->close ();

if ($_SESSION["ID"] != GUEST_ID)
{
    $this->OpenList ("None");
    $this->Listitem ("<a href=\"User.php?ID=". $_SESSION["ID"]. "\">". $strUserName. "</a>");
    
    $Stmt = $this->m_DB->prepare ("SELECT COUNT(*) FROM `Letters` WHERE `ToID`=? AND `Read`=0;");
    if ($Stmt == false) return false;
    $Stmt->bind_param ("i", $_SESSION["ID"]);
    if ($Stmt->execute () == false) return false;
    $Stmt->bind_result ($nLetters);
    $Stmt->fetch ();
    $Stmt->close ();
    
    $strPreText  = "";
    $strPostText = "en";
    if ($nLetters == 1)
    {
        $strPreText  = "1  neue ";
        $strPostText = "";
    }
    if ($nLetters > 1) $strPreText  = $nLetters. " neue ";
    
    $this->Listitem ("<a href=\"Letter.php\">". $strPreText. "Nachricht". $strPostText. "</a>");
    $this->Listitem ("<a href=\"Login.php\">Ausloggen</a>");
    $this->CloseList ();
}
else
{
    $this->Paragraph ("<a href=\"Login.php\">Einloggen</a>");
}

$strPinboard = GetConfig ($this->m_DB, "Pinboard");
if ($strPinboard != "" && IsRight ($this->m_DB, "SeePinboard"))
{
    $this->Line ();
    $this->Textblock ($strPinboard, HS_LAYOUT_TEXT);
}

$this->CloseDiv ();

return true;

?>
