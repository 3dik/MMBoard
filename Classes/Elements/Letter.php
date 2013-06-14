<?

if (defined ("HS_LETTER")) return 0;
define ("HS_LETTER", "");

//Nachrichtenklasse
//Public
//
//CheckReceiver (&$nUserID)          Prüft ob übergebene UserID gültiger Empfänger ist
//ListLetters   (&$Layout, $bIncome) Listet alle Nachrichten des angegebenen Users auf, Parameter bestimmt ob Ein- bzw. Ausgehende
////

class HSLetter extends HSElement
{
    //ListElements
    protected function ListElements ()
    {
        $Layout = new HSLayout ("Private Nachrichten", TEMPLATE, $this->m_DB);
        $this->ListLetters ($Layout, true);
        $this->ListLetters ($Layout, false);
    }//ListElements
    
    
    //ShowElement
    protected function ShowElement ()
    {
        $Stmt = $this->m_DB->prepare ("
SELECT `Letters`.`Title`, `Letters`.`Message`, `Letters`.`Creation`,
       `FromUsers`.`ID`, `FromUsers`.`Name`, `FromUsers`.`Info`, `ToUsers`.`ID`, `ToUsers`.`Name`
FROM `Letters`
INNER JOIN `Users` AS FromUsers
ON `Letters`.`FromID` = `FromUsers`.`ID`
INNER JOIN `Users` AS ToUsers
ON `Letters`.`ToID` = `ToUsers`.`ID`
WHERE `Letters`.`ID`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("i", $_GET["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->bind_result ($strLetterTitle, $strLetterMessage, $nLetterCreation, $nFromUserID, $strFromUserName, $strFromUserInfo, $nToUserID, $strToUserName);
        $Stmt->fetch ();
        $Stmt->close ();
        
        $Time = new HSTime ($nLetterCreation);
        
        $Layout = new HSLayout ("Nachricht: ". $strLetterTitle, TEMPLATE, $this->m_DB);
        $Layout->OpenFieldset ("Infos");
        $Layout->Paragraph ("Titel: ". $strLetterTitle);
        $Layout->Paragraph ("Erstellungsdatum: ". $Time->Get ());
        $Layout->Paragraph ("Absender: <a href=\"User.php?ID=". $nFromUserID. "\">". $strFromUserName. "</a>");
        $Layout->Paragraph ("Empfänger: <a href=\"User.php?ID=". $nToUserID. "\">". $strToUserName. "</a>");
        $Layout->CloseFieldset ();
        $Layout->OpenFieldset ("Nachricht");
        $Layout->Textblock ($this->Convert ($strLetterMessage, true), HS_LAYOUT_TEXT);
        $Layout->Line ();
        $Layout->Textblock ($this->Convert ($strFromUserInfo, true), HS_LAYOUT_TEXT);
        $Layout->CloseFieldset ();
        
        $pUserID   = &$nFromUserID;
        if ($pUserID == $_SESSION["ID"]) $pUserID = &$nToUserID; 
        
        $Layout->Paragraph ("<a href=\"Letter.php?Action=New&UserID=". $pUserID. "\">Nachricht schicken</a>");
        $Layout->Paragraph ("<a href=\"Letter.php?Action=Kick&ID=". $_GET["ID"]. "\">Nachricht löschen</a>");
    }//ShowElement
    
    
    //EditElement
    protected function EditElement ()
    {
        new HSRedirection (2);
        return;
    }//EditElement
    
    
    //ExistsElement
    protected function ExistsElement ()
    {
        $Stmt = $this->m_DB->prepare (
"SELECT `Read`, `ToID` FROM `Letters` WHERE `ID`=? AND ((`FromID`=? AND `KickedFrom`=0) OR (`ToID`=? AND `KickedTo`=0));");
        if ($this->CheckStmt ($Stmt, false) == false) return false;
        $Stmt->bind_param ("iii", $_GET["ID"],  $_SESSION["ID"], $_SESSION["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return false;
        $Stmt->bind_result ($bLetterRead, $nToID);
        if ($Stmt->fetch () == false) return false;
        $Stmt->close ();
        
        if ($bLetterRead == false && $nToID == $_SESSION["ID"])
        {
            $Stmt = $this->m_DB->prepare ("UPDATE `Letters` SET `Read`=1 WHERE `ID`=?;");
            if ($this->CheckStmt ($Stmt, false) == false) return false;
            $Stmt->bind_param ("i", $_GET["ID"]);
            if ($this->ChecKStmt ($Stmt, true) == false) return false;
            $Stmt->close ();
        }
        
        return true;
    }//ExsitsElement
    
    
    //NewElement
    protected function NewElement ()
    {
        if (isset ($_GET["Execute"]) == false)
        {
            if ($this->CheckReceiver ($_GET["UserID"]) == false) return;
            
            $Layout = new HSLayout ("Nachricht schreiben", TEMPLATE, $this->m_DB);
            $this->OpenElementForm ("Nachricht schreiben", "Letter.php", $Layout);
            $Layout->Hidden ("UserID", $_GET["UserID"]);
            $Layout->Input ("Titel", "text");
            $Layout->Input ("Nachricht", "textarea");
            $this->CloseElementForm ($Layout);
            
            return;
        }
        
        if ($this->Check ($_POST["Titel"]) == false) return;
        if ($this->Check ($_POST["Nachricht"], 5000) == false) return;
        if ($this->CheckReceiver ($_POST["UserID"]) == false) return;
        
        $Stmt = $this->m_DB->prepare ("INSERT INTO `Letters` (`Title`, `Message`, `Creation`, `FromID`, `ToID`) VALUES (?,?,?,?,?);");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $_POST["Titel"] = $this->Filter ($_POST["Titel"]);
        $_POST["Nachricht"] = $this->Filter ($_POST["Nachricht"]);
        $Stmt->bind_param ("ssiii", $_POST["Titel"], $_POST["Nachricht"], time(), $_SESSION["ID"], $_POST["UserID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->close ();
        
        new HSRedirection ("Letter.php?ID=". $this->m_DB->insert_id);
        return;
    }//NewElement
    
    
    //KickElement
    protected function KickElement ()
    {
        if (isset ($_GET["Execute"]) == false)
        {
            $Layout = new HSLayout ("Nachricht löschen", TEMPLATE, $this->m_DB);
            $Layout->Paragraph ("Um die Nachricht zu löschen musst du in das Eingabefeld \"Löschen\" eingeben. Dies ist nötig um versehentliche Löschungen zu vermeiden.");
            $this->OpenElementForm ("Nachricht löschen", "Letter.php", $Layout);
            $Layout->Input ("Löschen?", "text", "", "Aktion");
            $this->CloseElementForm ($Layout);
            return;
        }
        
        if ($this->Check ($_POST["Aktion"]) == false) return;
        if ($_POST["Aktion"] != "Löschen")
        {
            new HSRedirection (10);
            return;
        }
        
        $Stmt = $this->m_DB->prepare ("SELECT `FromID` FROM `Letters` WHERE `ID`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("i", $_GET["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->bind_result ($nFromID);
        $Stmt->fetch ();
        $Stmt->close ();
        
        $strVarToChange = "To";
        if ($nFromID == $_SESSION["ID"]) $strVarToChange = "From";
        $nValue = 1;
        
        $Stmt = $this->m_DB->prepare ("UPDATE `Letters` SET `Kicked". $strVarToChange. "` = ? WHERE `ID`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("ii", $nValue, $_GET["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->close ();
        
        new HSRedirection ("Letter.php");
        return;
    }//KickElement
    
    
    //CheckReceiver
    private function CheckReceiver (&$nUserID)
    {
        if ($this->Check ($nUserID) == false) return false;
        
        $Stmt = $this->m_DB->prepare ("SELECT COUNT(*) FROM `Users` WHERE `ID`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return false;
        $Stmt->bind_param ("i", $nUserID);
        if ($this->CheckStmt ($Stmt, true) == false) return false;
        $Stmt->bind_result ($nUsers);
        $Stmt->fetch ();
        $Stmt->close ();
        if ($nUsers == 0)
        {
            new HSRedirection (4);
            return false;
        }
        if ($nUserID == $_SESSION["ID"] || $nUserID == GUEST_ID)
        {
            new HSRedirection (14);
            return false;
        }
        
        return true;
    }//CheckReceiver
    
    
    //ListLetters
    private function ListLetters (&$Layout, $bIncome)
    {
        if ($Layout == false) return;
        
        if ($bIncome)
        {
            $Layout->OpenFieldset ("Eingehende Nachrichten");
        }
        else
        {
            $Layout->OpenFieldset ("Ausgehende Nachrichten");
        }
        
        $strUserTarget = "From";
        if ($bIncome)
        {
            $strUserTarget = "To";
        }
        
        $Stmt = $this->m_DB->prepare ("
SELECT `Letters`.`ID`, `Letters`.`Title`, `Letters`.`Creation`, `Letters`.`Read`, `Users`.`ID`, `Users`.`Name`
FROM `Letters`
INNER JOIN `Users`
ON (`Letters`.`FromID` = `Users`.`ID` AND 1=?) OR (`Letters`.`ToID` = `Users`.`ID` AND 1=?)
WHERE `Letters`.`". $strUserTarget. "ID`=? AND `Kicked". $strUserTarget. "`=0
ORDER BY `Letters`.`Creation` DESC;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $bFrom = false;
        $bTo   = true;
        if ($bIncome)
        {
            $bFrom = true;
            $bTo   = false;
        }
        //$Stmt->bind_param ("i", $_SESSION["ID"]);
        $Stmt->bind_param ("iii", $bFrom, $bTo, $_SESSION["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->bind_result ($nLetterID, $strLetterTitle, $nLetterCreation, $bLetterRead, $nUserID, $strUserName);
        if ($Stmt->fetch () == false)
        {
            $Layout->Paragraph ("Es sind noch keine Nachrichten vorhanden.");
        }
        else
        {
            $Time = new HSTime;
            $Layout->OpenList ();
            do
            {
                if ($bLetterRead == false && $bIncome)
                {
                    $strNew = "Neu: ";
                }
                else
                {
                    $strNew = "";
                }
                $Layout->Listitem (
$strNew. "<a href=\"Letter.php?ID=". $nLetterID. "\">". $strLetterTitle. "</a> - <a href=\"User.php?ID=". $nUserID. "\">". $strUserName. "</a> - ". $Time->Get ("", $nLetterCreation));
            } while ($Stmt->fetch ());
            $Layout->CloseList ();
        }
        $Stmt->close ();
        
        $Layout->CloseFieldset ();
    }//Listletters
}//HSLetter
