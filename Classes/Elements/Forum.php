<?

if (defined ("HS_FORUM")) return 0;
define ("HS_FORUM", "");

//Forumklasse
//Public
//
//ExistsName  ($strName)                      Ist das übergebene Forenname schon vorhanden
//ListThreads ($strLegend, &$Stmt, &$Layout)  Listet alle Threads des Statements auf
////

class HSForum extends HSElement
{
    //ListElements
    protected function ListElements ()
    {
        $Layout = new HSLayout ("Forum", TEMPLATE, $this->m_DB);
        
        $strHide = "";
        if (IsRight ($this->m_DB, "ViewHiddenForum") == false) $strHide = "WHERE `Columns`.`Hidden`=0";
        
        $Stmt = $this->m_DB->prepare ("
SELECT `Threads`.`ID`, `Threads`.`Title`, `Threads`.`Creation`, `Threads`.`Closed`, `FirstUser`.`ID`, `FirstUser`.`Name`, `Posts`.`Creation`, `LastUser`.`ID`, `LastUser`.`Name`, (SELECT COUNT(*)-1 FROM `Posts` WHERE `ToID`=`Threads`.`ID`)
FROM `Threads`
INNER JOIN `Users` AS FirstUser
    ON `Threads`.`FromID` = `FirstUser`.`ID`
INNER JOIN `Posts`
    ON `Threads`.`ID` AND `Posts`.`ToID` AND `Posts`.`ID` =
        (SELECT `Posts`.`ID`
        FROM `Posts`
        WHERE `ToID`=`Threads`.`ID`
        ORDER BY `Posts`.`Creation` DESC
        LIMIT 0,1)
INNER JOIN `Users` AS LastUser
    ON `Posts`.`FromID` = `LastUser`.`ID`
INNER JOIN `Columns`
ON `Threads`.`ToID` = `Columns`.`ID`". $strHide. "
ORDER BY `Posts`.`Creation` DESC
LIMIT 0,7");
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $this->ListThreads ("Aktuelle Threads", $Stmt, $Layout);
        
        
        $Layout->OpenFieldset ("Forum");
        if ($strHide != "") $strHide = "WHERE `Hidden`=0";
        $Stmt = $this->m_DB->prepare ("
SELECT `Columns`.`ID` AS ColumnID, `Columns`.`Name`, `Columns`.`Description`, `Columns`.`Hidden`, (SELECT COUNT(*) FROM `Threads` WHERE `ToID` = `Columns`.`ID`) AS nThreads,
(SELECT COUNT(*)
 FROM `Posts`
 INNER JOIN `Threads`
 ON `Posts`.`ToID` = `Threads`.`ID`
 INNER JOIN `Columns`
 ON `Threads`.`ToID` = `Columns`.`ID`
 WHERE `Columns`.`ID` = `ColumnID`) AS nPosts
FROM `Columns` ". $strHide. "
ORDER BY `Columns`.`Hidden` DESC, `nThreads` DESC, `Columns`.`Name` ASC;");
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->bind_result ($nID, $strName, $strDescription, $bHidden, $nThreads, $nPosts);
        
        if ($Stmt->fetch () == false)
        {
            $Layout->Paragraph ("Es sind keine dir zugänglichen Rubriken vorhanden.");
        }
        else
        {
            $Layout->OpenTable ();
            $Layout->Cell ("Name", true);
            $Layout->Cell ("Status", true);
            $Layout->Cell ("Threads", true);
            $Layout->Cell ("Beiträge", true);
            do
            {
                $Layout->OpenRow ();
                $Layout->Cell ("<a href=\"Forum.php?ID=". $nID. "\">". $strName. "</a>");
                $strHidden = "Offen";
                if ($bHidden) $strHidden = "Versteckt";
                $Layout->Cell ($strHidden);
                $Layout->Cell ("". $nThreads);
                $Layout->Cell ("". $nPosts);
                $Layout->CloseRow ();
            } while ($Stmt->fetch ());
            
            $Layout->CloseTable ();
        }
        $Stmt->close ();
        
        $Layout->CloseFieldset ();
        
        if (IsRight ($this->m_DB, "NewForum")) $Layout->Paragraph ("<a href=\"Forum.php?Action=New\">Forum erstellen</a>");
    }//ListElements
    
    
    //ShowElement
    protected function ShowElement ()
    {
        $Stmt = $this->m_DB->prepare ("SELECT `Name`, `Description`, `Hidden` FROM `Columns` WHERE `ID`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("i", $_GET["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->bind_result ($strName, $strDescription, $bHidden);
        $Stmt->fetch ();
        $Stmt->close ();
        
        $Layout = new HSLayout ("Forum: ". $strName, TEMPLATE, $this->m_DB);
        $Layout->OpenFieldset ("Beschreibung");
        $Layout->Textblock ($strDescription, HS_LAYOUT_TEXT);
        $Layout->CloseFieldset ();
        
        $Stmt = $this->m_DB->prepare ("
SELECT `Threads`.`ID`, `Threads`.`Title`, `Threads`.`Creation`, `Threads`.`Closed`, `FirstUser`.`ID`, `FirstUser`.`Name`, `Posts`.`Creation`, `LastUser`.`ID`, `LastUser`.`Name`, (SELECT COUNT(*)-1 FROM `Posts` WHERE `ToID`=`Threads`.`ID`)
FROM `Threads`
INNER JOIN `Users` AS FirstUser
    ON `Threads`.`FromID` = `FirstUser`.`ID`
INNER JOIN `Posts`
    ON `Threads`.`ID` AND `Posts`.`ToID` AND `Posts`.`ID` =
        (SELECT `Posts`.`ID`
        FROM `Posts`
        WHERE `ToID`=`Threads`.`ID`
        ORDER BY `Posts`.`Creation` DESC
        LIMIT 0,1)
INNER JOIN `Users` AS LastUser
    ON `Posts`.`FromID` = `LastUser`.`ID`
WHERE `Threads`.`ToID`=? AND `Threads`.`Sticky`=?
ORDER BY `Posts`.`Creation` DESC");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $bSticky = 1;
        $Stmt->bind_param ("ii", $_GET["ID"], $bSticky);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $this->ListThreads ("Stickys", $Stmt, $Layout);
        
        $bSticky = 0;
        $Stmt->bind_param ("ii", $_GET["ID"], $bSticky);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $this->ListThreads ("Threads", $Stmt, $Layout);
        
        if (IsRight ($this->m_DB, "EditForum")) $Layout->Paragraph ("<a href=\"Forum.php?Action=Edit&ID=". $_GET["ID"]. "\">Forum editieren</a>");
        if (IsRight ($this->m_DB, "KickForum")) $Layout->Paragraph ("<a href=\"Forum.php?Action=Kick&ID=". $_GET["ID"]. "\">Forum löschen</a>");
        if (IsRight ($this->m_DB, "NewThread")) $Layout->Paragraph ("<a href=\"Thread.php?Action=New&ID=". $_GET["ID"]. "\">Thread erstellen</a>");
    }//ShowElement
    
    
    //EditElement
    protected function EditElement ()
    {
        if (IsRight ($this->m_DB, "EditForum") == false)
        {
            new HSRedirection (2);
            return;
        }
        
        if (isset ($_GET["Execute"]) == false)
        {
            $Stmt = $this->m_DB->prepare ("SELECT `Name`, `Description`, `Hidden` FROM `Columns` WHERE `ID`=?;");
            if ($this->CheckStmt ($Stmt, false) == false) return;
            $Stmt->bind_param ("i", $_GET["ID"]);
            if ($this->CheckStmt ($Stmt, true) == false) return;
            $Stmt->bind_result ($strName, $strDescription, $bHidden);
            $Stmt->fetch ();
            $Stmt->close ();
            
            $Layout = new HSLayout ("Forum editieren", TEMPLATE, $this->m_DB);
            $this->OpenElementForm ("Forum editieren", "Forum.php", $Layout);
            $Layout->Input ("Name", "text", $strName);
            $Layout->Input ("Beschreibung", "textarea", $strDescription);
            if (IsRight ($this->m_DB, "ViewHiddenForum")) $Layout->Input ("Versteckt", "bool", $bHidden);
            $this->CloseElementForm ($Layout);
            return;
        }
        
        if ($this->Check ($_POST["Name"]) == false) return;
        if ($this->Check ($_POST["Beschreibung"], 50000) == false) return;
        
        $_POST["Name"] = $this->Filter ($_POST["Name"]);
        $_POST["Beschreibung"] = $this->Filter ($_POST["Beschreibung"]);
        
        $Stmt = $this->m_DB->prepare ("SELECT `Name` FROM `Columns` WHERE `ID`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("i", $_GET["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->bind_result ($strName);
        $Stmt->fetch ();
        $Stmt->close ();
        
        if ($strName != $_POST["Name"] && $this->ExistsName ($_POST["Name"])) return;
        
        $Stmt = $this->m_DB->prepare ("UPDATE `Columns` SET `Name`=?, `Description`=? WHERE `ID`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("ssi", $_POST["Name"], $_POST["Beschreibung"], $_GET["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->close ();
        
        if (IsRight ($this->m_DB, "ViewHiddenForum"))
        {
            if ($this->FilterBool ($_POST["Versteckt"]) == false) return;
            
            $Stmt = $this->m_DB->prepare ("UPDATE `Columns` SET `Hidden`=? WHERE `ID`=?;");
            if ($this->CheckStmt ($Stmt, false) == false) return;
            $Stmt->bind_param ("ii", $_POST["Versteckt"], $_GET["ID"]);
            if ($this->CheckStmt ($Stmt, true) == false) return;
            $Stmt->close ();
        }
        
        new HSRedirection ("Forum.php?ID=". $_GET["ID"]);
    }//EditElement
    
    
    //ExistsElement
    protected function ExistsElement ()
    {
        $Stmt = $this->m_DB->prepare ("SELECT `Hidden` FROM `Columns` WHERE `ID`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return false;
        $Stmt->bind_param ("i", $_GET["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return false;
        $Stmt->bind_result ($bHidden);
        if ($Stmt->fetch () == false) return false;
        $Stmt->close ();
        
        if ($bHidden && IsRight ($this->m_DB, "ViewHiddenForum") == false) return false;
        return true;
    }//ExistsElement
    
    
    //NewElement
    protected function NewElement ()
    {
        if (IsRight ($this->m_DB, "NewForum") == false)
        {
            new HSRedirection (2);
            return;
        }
        
        if (isset ($_GET["Execute"]) == false)
        {
            $Layout = new HSLayout ("Neues Forum erstellen", TEMPLATE, $this->m_DB);
            $this->OpenElementForm ("Neues Forum erstellen", "Forum.php", $Layout);
            $Layout->Input ("Name", "text");
            $Layout->Input ("Beschreibung", "textarea");
            $this->CloseElementForm ($Layout);
            return;
        }
        
        if ($this->Check ($_POST["Name"]) == false) return;
        if ($this->Check ($_POST["Beschreibung"], 50000) == false) return;
        
        $_POST["Name"] = $this->Filter ($_POST["Name"]);
        $_POST["Beschreibung"] = $this->Filter ($_POST["Beschreibung"]);
        
        if ($this->ExistsName ($_POST["Name"])) return;
        
        $Stmt = $this->m_DB->prepare ("INSERT INTO `Columns` (`Name`, `Description`) VALUES (?,?);");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("ss", $_POST["Name"], $_POST["Beschreibung"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->close ();
        
        new HSRedirection ("Forum.php?ID=". $this->m_DB->insert_id);
    }//NewElement
    
    
    //KickElement
    protected function KickElement ()
    {
        if (IsRight ($this->m_DB, "KickForum") == false)
        {
            new HSRedirection (2);
            return;
        }
        
        if (isset ($_GET["Execute"]) == false)
        {
            $Layout = new HSLayout ("Forum löschen", TEMPLATE, $this->m_DB);
            $Layout->Paragraph ("Geben sie in das Eingabefeld \"Löschen\" ein. Dies ist nötig um versehentliche Löschungen zu vermeiden.");
            $this->OpenElementForm ("Forum löschen", "Forum.php", $Layout);
            $Layout->Input ("Bestätigung", "text");
            $this->CloseElementForm ($Layout);
            return;
        }
        
        if ($this->Check ($_POST["Bestätigung"]) == false) return;
        
        if ($_POST["Bestätigung"] != "Löschen")
        {
            new HSRedirection (10);
            return;
        }
        
        $Stmt = $this->m_DB->prepare ("
DELETE `Columns`, `Threads`, `Posts` FROM `Columns` INNER JOIN `Threads` INNER JOIN `Posts`
WHERE `Columns`.`ID`=? AND `Columns`.`ID` = `Threads`.`ToID` AND `Threads`.`ID` = `Posts`.`ToID`;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("i", $_GET["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->close ();
        
        new HSRedirection ("Forum.php");
    }//KickElement
    
    
    //ExistsName
    private function ExistsName ($strName)
    {
        if ($strName == "") return false;
        
        $Stmt = $this->m_DB->prepare ("SELECT COUNT(*) FROM `Columns` WHERE `Name`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return true;
        $Stmt->bind_param ("s", $strName);
        if ($this->CheckStmt ($Stmt, true) == false) return true;
        $Stmt->bind_result ($nForums);
        $Stmt->fetch ();
        $Stmt->close ();
        
        if ($nForums != 0)
        {
            new HSRedirection (16);
            return true;
        }
        
        return false;
    }//ExistsName
    
    
    //ListThreads
    private function ListThreads ($strLegend, &$Stmt, &$Layout)
    {
        if ($strLegend == "" || $Stmt == false || $Layout == false) return;
    
        $Stmt->bind_result ($nID, $strTitle, $nCreation, $bClosed, $nUserID, $strUserName, $nLastPostCreation, $nLastUserID, $strLastUserName, $nPosts);
        $Layout->OpenFieldset ($strLegend, "Forum");
        if ($Stmt->fetch ())
        {
            $Layout->OpenTable ();
            $Layout->OpenRow ();
            $Layout->Cell ("Überschrift", true);
            $Layout->Cell ("Erstellungsdatum", true);
            $Layout->Cell ("Ersteller", true);
            $Layout->Cell ("Letzter Beitrag", true);
            $Layout->Cell ("Letzter Antworter", true);
            $Layout->Cell ("Antworten", true);
            $Layout->CloseRow ();
            $Time = new HSTime ();
            do
            {
                $Layout->OpenRow ();
                $strClosed  = "";
                $strNewPre  = "";
                $strNewPost = "";
                if ($bClosed == true) $strClosed = " <strong>(geschlossen)</strong>";
                if ($nLastPostCreation > $_SESSION["LastLogin"] && $_SESSION["ID"] != GUEST_ID) $strNewPre  = "<strong>";
                if ($nLastPostCreation > $_SESSION["LastLogin"] && $_SESSION["ID"] != GUEST_ID) $strNewPost = "</strong>";
                $Layout->Cell ($strNewPre. "<a href=\"Thread.php?ID=". $nID. "\">". $strTitle. "</a>". $strNewPost. $strClosed);
                $Layout->Cell ($Time->Get ("", $nCreation));
                $Layout->Cell ("<a href=\"User.php?ID=". $nUserID. "\">". $strUserName. "</a>");
                $Layout->Cell ($Time->Get ("", $nLastPostCreation));
                $Layout->Cell ("<a href=\"User.php?ID=". $nLastUserID. "\">". $strLastUserName. "</a>");
                $Layout->Cell ("". $nPosts);
                $Layout->CloseRow ();
            } while ($Stmt->fetch ());
            $Layout->CloseTable ();
        }
        $Layout->CloseFieldset ();
    }//ListThreads
}//HSForum
