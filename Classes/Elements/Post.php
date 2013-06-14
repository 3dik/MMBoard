<?

if (defined ("HS_POST")) return 0;
define ("HS_POST", "");

//Postklasse
//IsThreadClosed () Ist Thread gesperrt?, wenn ja: Darf User trotzdem Posts verwalten

class HSPost extends HSElement
{
    //ListElements
    protected function ListElements ()
    {
        new HSRedirection (2);
    }//ListElements
    
    
    //ShowElement
    protected function ShowElement ()
    {
        new HSRedirection (2);
    }//ShowElement
    
    
    //EditElement
    protected function EditElement ()
    {
        if ($this->IsThreadClosed ()) return false;
    
        $Stmt = $this->m_DB->prepare ("
SELECT `Posts`.`FromID`, `Posts`.`Message`, `Threads`.`ID`
FROM `Posts`
INNER JOIN `Threads`
ON `Posts`.`ToID` = `Threads`.`ID`
WHERE `Posts`.`ID`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("i", $_GET["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->bind_result ($nUserID, $strMessage, $nThreadID);
        $Stmt->fetch ();
        $Stmt->close ();
        
        if (IsRight ($this->m_DB, "EditPost") == false && ($nUserID != $_SESSION["ID"] || IsRight ($this->m_DB, "EditOwnPost") == false))
        {
            new HSRedirection (2);
            return;
        }
        
        if (isset ($_GET["Execute"]) == false)
        {
            $Layout = new HSLayout ("Beitrag editieren", TEMPLATE, $this->m_DB);
            $this->OpenElementForm ("Beitrag editieren", "Post.php", $Layout);
            $Layout->Input ("Nachricht", "textarea", $strMessage);
            $this->CloseElementForm ($Layout);
            return;
        }
        
        if ($this->Check ($_POST["Nachricht"], 50000) == false) return;
        
        $Stmt = $this->m_DB->prepare ("UPDATE `Posts` SET `Message`=? WHERE `ID`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return false;
        $_POST["Nachricht"] = $this->Filter ($_POST["Nachricht"]);
        $Stmt->bind_param ("si", $_POST["Nachricht"], $_GET["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return false;
        $Stmt->close ();
        
        new HSRedirection ("Thread.php?ID=". $nThreadID);
        return;
    }//EditElement
    
    
    //ExistsElement
    protected function ExistsElement ()
    {
        $Stmt = $this->m_DB->prepare ("
SELECT `Columns`.`Hidden`
FROM `Posts`
INNER JOIN `Threads`
ON `Posts`.`ToID` = `Threads`.`ID`
INNER JOIN `Columns`
ON `Threads`.`ToID` = `Columns`.`ID`
WHERE `Posts`.`ID`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return false;
        $Stmt->bind_param ("i", $_GET["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return false;
        $Stmt->bind_result ($bHidden);
        if ($Stmt->fetch () == false) return false;
        $Stmt->close ();
        
        if ($bHidden && IsRight ($this->m_DB, "ViewHiddenForum") == false) return false;
        return true;
    }//ExsitsElement
    
    
    //NewElement
    protected function NewElement ()
    {
        if ($this->Check ($_GET["ID"]) == false) return;
        
        if (IsRight ($this->m_DB, "NewPost") == false)
        {
            new HSRedirection (2);
            return;
        }
        
        $Stmt = $this->m_DB->prepare ("
SELECT `Threads`.`Closed`, `Columns`.`Hidden`
FROM `Threads`
INNER JOIN `Columns`
ON `Threads`.`ToID` = `Columns`.`ID`
WHERE `Threads`.`ID`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("i", $_GET["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->bind_result ($bThreadClosed, $bColumnHidden);
        if ($Stmt->fetch () == false)
        {
            new HSRedirection (4);
            return;
        }
        $Stmt->close ();
        
        if ($bColumnHidden && IsRight ($this->m_DB, "ViewHiddenForum") == false)
        {
            new HSRedirection (4);
            return;
        }
        if ($bThreadClosed && IsRight ($this->m_DB, "CloseThread") == false)
        {
            new HSRedirection (15);
            return;
        }
        
        if (isset ($_GET["Execute"]) == false)
        {
            $Layout = new HSLayout ("Beitrag erstellen", TEMPLATE, $this->m_DB);
            $this->OpenElementForm ("Beitrag erstellen", "Post.php", $Layout);
            $Layout->Input ("Nachricht", "textarea");
            $this->CloseElementForm ($Layout);
            return;
        }
        
        if ($this->Check ($_POST["Nachricht"], 50000) == false) return;
        
        $Stmt = $this->m_DB->prepare ("INSERT INTO `Posts` (`ToID`, `FromID`, `Message`, `Creation`) VALUES (?,?,?,?);");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $_POST["Nachricht"] = $this->Filter ($_POST["Nachricht"]);
        $Stmt->bind_param ("iisi", $_GET["ID"], $_SESSION["ID"], $_POST["Nachricht"], time ());
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->close ();
        
        new HSRedirection ("Thread.php?ID=". $_GET["ID"]);
        return;
    }//NewElement
    
    
    //KickElement
    protected function KickElement ()
    {
        if ($this->IsThreadClosed ()) return false;
    
        $Stmt = $this->m_DB->prepare ("
SELECT `Posts`.`FromID`, `Posts`.`Message`, `Threads`.`ID`
FROM `Posts`
INNER JOIN `Threads`
ON `Posts`.`ToID` = `Threads`.`ID`
WHERE `Posts`.`ID`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("i", $_GET["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->bind_result ($nUserID, $strMessage, $nThreadID);
        $Stmt->fetch ();
        $Stmt->close ();
        
        if (IsRight ($this->m_DB, "KickPost") == false && ($nUserID != $_SESSION["ID"] || IsRight ($this->m_DB, "KickOwnPost") == false))
        {
            new HSRedirection (2);
            return;
        }
        
        if (isset ($_GET["Execute"]) == false)
        {
            $Layout = new HSLayout ("Beitrag löschen", TEMPLATE, $this->m_DB);
            $Layout->Paragraph ("Gebe in das Eingabefeld \"Löschen\" ein. Dies ist nötig um versehentliche Löschungen zu vermeiden.");
            $this->OpenElementForm ("Beitrag löschen", "Post.php", $Layout);
            $Layout->Input ("Löschen", "text");
            $this->CloseElementForm ($Layout);
            return;
        }
        
        if ($this->Check ($_POST["Löschen"]) == false) return;
        
        if ($_POST["Löschen"] != "Löschen")
        {
            new HSRedirection (10);
            return;
        }
        
        $Stmt = $this->m_DB->prepare ("DELETE FROM `Posts` WHERE `ID`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("i", $_GET["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->close ();
        
        new HSRedirection ("Thread.php?ID=". $nThreadID);
        return;
    }//KickElement
    
    
    //IsThreadClosed
    private function IsThreadClosed ()
    {
        $Stmt = $this->m_DB->prepare ("
SELECT `Threads`.`Closed`
FROM `Posts`
INNER JOIN `Threads`
ON `Posts`.`ToID` = `Threads`.`ID`
WHERE `Posts`.`ID`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return true;
        $Stmt->bind_param ("i", $_GET["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return true;
        $Stmt->bind_result ($bClosed);
        $Stmt->fetch ();
        $Stmt->close ();
        
        if ($bClosed && IsRight ($this->m_DB, "CloseThread") == false)
        {
            new HSRedirection (15);
            return true;
        }
        
        return false;
    }//IsThreadClosed
}//HSPost
