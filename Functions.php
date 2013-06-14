<?
//Globale Funktionen
////
//GetClearReturn ($strText)         Entfernt alle Zeilenumbrüche
//GetConfig      (&$DB, $strConfig) Gibt Config-Wert zurück
//IsRight        (&$DB, $strRight)  Prüft, ob User angegebenes Recht besitzt
//DurToText      ($nDur)            Gibt eine Zeitspanne in leßbares Format zurück
////


//GetClearReturn
function GetClearReturn ($strText)
{
    $strText = str_replace ("\n", "", $strText);
    $strText = str_replace ("\r", "", $strText);
    return $strText;
}//GetClearReturn


//GetConfig
function GetConfig (&$DB, $strConfig)
{
    if ($DB == false || $strConfig == "") return false;
    
    $Stmt = $DB->prepare ("SELECT `Value` FROM `Configs` WHERE `Name`=?;");
    if ($Stmt == false) return false;
    $Stmt->bind_param ("s", $strConfig);
    if ($Stmt->execute () == false) return false;
    $Stmt->bind_result ($strValue);
    if ($Stmt->fetch () == false) return false;
    $Stmt->close ();
    
    return $strValue;
}//GetConfig


//IsRight
function IsRight (&$DB, $strRight)
{
    if ($DB == false || $strRight == "" ||
        isset ($_SESSION["ID"]) == false || $_SESSION["ID"] == 0) return false;
    
    $Stmt = $DB->prepare ("
SELECT COUNT(*)
FROM `Rights`
INNER JOIN `Grouprights`
 ON `Rights`.`ID` = `Grouprights`.`RightID`
INNER JOIN `Groupjoins`
 ON `Grouprights`.`GroupID` = `Groupjoins`.`GroupID` AND `Groupjoins`.`UserID` = ?
WHERE `Rights`.`Name` = ?;");
    if ($Stmt == false) return false;
    $Stmt->bind_param ("is", $_SESSION["ID"], $strRight);
    if ($Stmt->execute () == false) return false;
    $Stmt->bind_result ($nRights);
    $Stmt->fetch ();
    $Stmt->close ();
    
    if ($nRights == 0) return false;
    return true;
}//IsRight


//DurToText
function DurToText ($nDur)
{
    $strText = "";
    $nUnit   = 0;
    
    #Hours
    if ($nDur >= (60 * 60))
    {
        $nUnit = floor ($nDur / (60 * 60));
        $nDur %= 60 * 60;
        $strText .= " $nUnit Stunde";
        if ($nUnit != 1) $strText .= "n";
    }
    #Minutes
    if ($nDur >= 60)
    {
        $nUnit = floor ($nDur / 60);
        $nDur %= 60;
        $strText .= " $nUnit Minute";
        if ($nUnit != 1) $strText .= "n";
    }
    #Seconds
    if ($nDur > 0)
    {
        $strText .= " $nDur Sekunden";
    }
    
    return substr ($strText, 1);
}

?>
