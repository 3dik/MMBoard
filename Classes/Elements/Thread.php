<?

if (defined ("HS_THREAD")) return 0;
define ("HS_THREAD", "");

//Threadklasse
//
//Public
//
//AllowControl ($strMode) Ist die angegebene Operation für den User erlaubt?
//MoveThread              Verschiebt angegebenen Thread
////

class HSThread extends HSElement
{
    //ListElements
    protected function ListElements ()
    {
        new HSRedirection (2);
        return;
    }//ListElements
    
    
    //ShowElement
    protected function ShowElement ()
    {
        $Stmt = $this->m_DB->prepare ("
SELECT `Threads`.`Title`, `Threads`.`Closed`, `Threads`.`Sticky`, `Columns`.`ID`, `Columns`.`Name`
FROM `Threads`
INNER JOIN `Columns`
ON `Threads`.`ToID` = `Columns`.`ID`
WHERE `Threads`.`ID`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("i", $_GET["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->bind_result ($strThreadTitle, $bThreadClosed, $bThreadSticky, $nForumID, $strForumName);
        $Stmt->fetch ();
        $Stmt->close ();
        $Layout = new HSLayout ("Thread: ". $strThreadTitle, TEMPLATE, $this->m_DB);
        $Layout->Paragraph ("Forum: <a href=\"Forum.php?ID=". $nForumID. "\">". $strForumName. "</a>");
        if ($bThreadSticky) $Layout->Paragraph ("Dieser Thread ist ein <strong>Sticky</strong>.");
        if ($bThreadClosed) $Layout->Paragraph ("Dieser Thread ist <strong>geschlossen</strong>.");
        
        $bEditPosts    = IsRight ($this->m_DB, "EditPost");
        $bKickPosts    = IsRight ($this->m_DB, "KickPost");
        $bEditOwnPosts = IsRight ($this->m_DB, "EditOwnPost");
        $bKickOwnPosts = IsRight ($this->m_DB, "KickOwnPost");
        
        $Stmt = $this->m_DB->prepare ("
SELECT `Posts`.`ID`, `Posts`.`Message`, `Posts`.`Creation`, `Users`.`ID`, `Users`.`Name`, `Users`.`Info`
FROM `Posts`
INNER JOIN `Users`
ON `Posts`.`FromID` = `Users`.`ID`
WHERE `Posts`.`ToID` = ?
ORDER BY `Creation` ASC;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("i", $_GET["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->bind_result ($nID, $strMessage, $nCreation, $nUserID, $strUserName, $strUserInfo);
        if ($Stmt->fetch () == false)
        {
            $Layout->Paragraph ("In diesem Thread sind keine Posts vorhanden.");
        }
        else
        {
            $Layout->OpenList ("None");
            $Time = new HSTime ();
            do
            {
                $Layout->OpenListitem ();
                $strControlCode = "";
                if ($bEditPosts || ($bEditOwnPosts && $nUserID == $_SESSION["ID"]))
                {
                    $strControlCode = " <a href=\"Post.php?Action=Edit&ID=". $nID. "\">Editieren</a>";
                }
                if ($bKickPosts || ($bKickOwnPosts && $nUserID == $_SESSION["ID"]))
                {
                    $strControlCode .= " <a href=\"Post.php?Action=Kick&ID=". $nID. "\">Löschen</a>";
                }
                $Layout->OpenFieldset ("<a href=\"User.php?ID=". $nUserID. "\">". $strUserName. "</a> ". $Time->Get ("", $nCreation). $strControlCode);
                $Layout->Textblock ($this->Convert ($strMessage), HS_LAYOUT_TEXT);
                $Layout->Line ();
                $Layout->Textblock ($this->Convert ($strUserInfo), HS_LAYOUT_TEXT);
                $Layout->CloseFieldset ();
                $Layout->CloseListitem ();
            } while ($Stmt->fetch ());
            $Layout->CloseList ();
        }
        
        if ($this->AllowControl ("Edit")) $Layout->Paragraph ("<a href=\"Thread.php?Action=Edit&ID=". $_GET["ID"]. "\">Thread editieren</a>");
        if ($this->AllowControl ("Kick")) $Layout->Paragraph ("<a href=\"Thread.php?Action=Kick&ID=". $_GET["ID"]. "\">Thread löschen</a>");
        if (IsRight ($this->m_DB, "MoveThread"))
        {
            $Layout->Paragraph ("<a href=\"Thread.php?Action=Edit&ID=". $_GET["ID"]. "&Move\">Thread verschieben</a>");
        }
        if (IsRight ($this->m_DB, "NewPost") && ($bThreadClosed == false || IsRight ($this->m_DB, "CloseThread")))
        {
            $Layout->Paragraph ("<a href=\"Post.php?Action=New&ID=". $_GET["ID"]. "\">Antworten</a>");
        }
    }//ShowElement
    
    
    //EditElement
    protected function EditElement ()
    {
        if (isset ($_GET["Move"]) || isset ($_POST["Move"]))
        {
            $this->MoveThread ();
            return;
        }
    
        if ($this->AllowControl ("Edit") == false)
        {
            new HSRedirection (2);
            return;
        }
        
        if (isset ($_GET["Execute"]) == false)
        {
            $Stmt = $this->m_DB->prepare ("SELECT `Title`, `Closed`, `Sticky` FROM `Threads` WHERE `ID`=?;");
            if ($this->CheckStmt ($Stmt, false) == false) return;
            $Stmt->bind_param ("i", $_GET["ID"]);
            if ($this->CheckStmt ($Stmt, true) == false) return;
            $Stmt->bind_result ($strTitle, $bClosed, $bSticky);
            $Stmt->fetch ();
            $Stmt->close ();
            
            $Layout = new HSLayout ("Thread editieren", TEMPLATE, $this->m_DB);
            $this->OpenElementForm ("Thread editieren", "Thread.php", $Layout);
            $Layout->Input ("Überschrift", "text", $strTitle, "Titel");
            if (IsRight ($this->m_DB, "CloseThread")) $Layout->Input ("Geschlossen", "bool", $bClosed);
            if (IsRight ($this->m_DB, "StickyThread")) $Layout->Input ("Sticky", "bool", $bSticky);
            $this->CloseElementForm ($Layout);
            return;
        }
        
        if ($this->Check ($_POST["Titel"]) == false) return;
        
        $Stmt = $this->m_DB->prepare ("UPDATE `Threads` SET `Title`=? WHERE `ID`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $_POST["Titel"] = $this->Filter ($_POST["Titel"]);
        $Stmt->bind_param ("si", $_POST["Titel"], $_GET["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->close ();
        
        if (IsRight ($this->m_DB, "CloseThread"))
        {
            if ($this->FilterBool ($_POST["Geschlossen"]) == false) return;
        
            $Stmt = $this->m_DB->prepare ("UPDATE `Threads` SET `Closed`=? WHERE `ID`=?;");
            if ($this->CheckStmt ($Stmt, false) == false) return;
            $Stmt->bind_param ("si", $_POST["Geschlossen"], $_GET["ID"]);
            if ($this->CheckStmt ($Stmt, true) == false) return;
            $Stmt->close ();
        }
        
        if (IsRight ($this->m_DB, "StickyThread"))
        {
            if ($this->FilterBool ($_POST["Sticky"]) == false) return;
        
            $Stmt = $this->m_DB->prepare ("UPDATE `Threads` SET `Sticky`=? WHERE `ID`=?;");
            if ($this->CheckStmt ($Stmt, false) == false) return;
            $Stmt->bind_param ("si", $_POST["Sticky"], $_GET["ID"]);
            if ($this->CheckStmt ($Stmt, true) == false) return;
            $Stmt->close ();
        }
        
        new HSRedirection ("Thread.php?ID=". $_GET["ID"]);
    }//EditElement
    
    
    //ExistsElement
    protected function ExistsElement ()
    {
        $Stmt = $this->m_DB->prepare ("
SELECT `Columns`.`Hidden`
FROM `Threads`
INNER JOIN `Columns`
ON `Threads`.`ToID` = `Columns`.`ID`
WHERE `Threads`.`ID`=?;");
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
        if (IsRight ($this->m_DB, "NewThread") == false)
        {
            new HSRedirection (2);
            return;
        }
        
        if ($this->Check ($_GET["ID"]) == false) return;
        
        if (isset ($_GET["Execute"]) == false)
        {
            $Layout = new HSLayout ("Thread erstellen", TEMPLATE, $this->m_DB);
            $this->OpenElementForm ("Thread erstellen", "Thread.php", $Layout);
            $Layout->Input ("Überschrift", "text", "", "Titel");
            $Layout->Input ("Beitrag", "textarea");
            $this->CloseElementForm ($Layout);
            return;
        }
        
        if ($this->Check ($_POST["Titel"]) == false) return;
        if ($this->Check ($_POST["Beitrag"], 50000) == false) return;
        
        $Time = time ();
        
        $Stmt = $this->m_DB->prepare ("INSERT INTO `Threads` (`Title`, `ToID`, `FromID`, `Creation`) VALUES (?,?,?,?);");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $_POST["Titel"] = $this->Filter ($_POST["Titel"]);
        $Stmt->bind_param ("siii", $_POST["Titel"], $_GET["ID"], $_SESSION["ID"], $Time);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->close ();
        
        $ThreadID = $this->m_DB->insert_id;
        
        $Stmt = $this->m_DB->prepare ("INSERT INTO `Posts` (`ToID`, `FromID`, `Message`, `Creation`) VALUES (?,?,?,?);");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $_POST["Beitrag"] = $this->Filter ($_POST["Beitrag"]);
        $Stmt->bind_param ("iisi", $ThreadID, $_SESSION["ID"], $_POST["Beitrag"], $Time);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->close ();
        
        new HSRedirection ("Thread.php?ID=". $ThreadID);
        return;
    }//NewElement
    
    
    //KickElement
    protected function KickElement ()
    {
        if ($this->AllowControl ("Kick") == false)
        {
            new HSRedirection (2);
            return;
        }
        
        if (isset ($_GET["Execute"]) == false)
        {
            $Layout = new HSLayout ("Thread löschen", TEMPLATE, $this->m_DB);
            $Layout->Paragraph ("Gebe in das Eingabefeld \"Löschen\" ein. Dies ist nötig, um versehentliche Löschungen zu vermeiden.");
            $this->OpenElementForm ("Thread löschen", "Thread.php", $Layout);
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
        
        $Stmt = $this->m_DB->prepare ("SELECT `ToID` FROM `Threads` WHERE `ID`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("i", $_GET["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->bind_result ($nForumID);
        $Stmt->fetch ();
        $Stmt->close ();
        
        $Stmt = $this->m_DB->prepare ("DELETE FROM `Posts` WHERE `ToID`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("i", $_GET["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->close ();
        
        $Stmt = $this->m_DB->prepare ("DELETE FROM `Threads` WHERE `ID`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("i", $_GET["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->close ();
        
        new HSRedirection ("Forum.php?ID=". $nForumID);
        return;
    }//KickElement
    
    
    //AllowControl
    private function AllowControl ($strMode)
    {
        if ($strMode != "Edit" && $strMode != "Kick") return false;
        
        $Stmt = $this->m_DB->prepare ("SELECT `FromID` FROM `Threads` WHERE `ID`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return false;
        $Stmt->bind_param ("i", $_GET["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return false;
        $Stmt->bind_result ($nUserID);
        $Stmt->fetch ();
        $Stmt->close ();
        
        return (IsRIght ($this->m_DB, $strMode. "Thread") || ($_SESSION["ID"] == $nUserID && IsRight ($this->m_DB, $strMode. "OwnThread")));
    }//AllowControl
    
    
    //MoveThread
    private function MoveThread ()
    {
        if (isset ($_GET["Move"]))
        {
            $Layout = new HSLayout ("Thread verschieben", TEMPLATE, $this->m_DB);
            $Layout->Paragraph ("Gebe den Namen des Forums an, in das du den Thread verschieben willst.");
            $this->OpenElementForm ("Thread verschieben", "Thread.php", $Layout);
            $Layout->Hidden ("Move");
            $Layout->Input ("Forum", "text");
            $this->CloseElementForm ($Layout);
            return;
        }
        
        if ($this->Check ($_POST["Forum"]) == false) return;
        
        $bViewHidden = IsRight ($this->m_DB, "ViewHiddenForum");
        
        $Stmt = $this->m_DB->prepare ("SELECT `ID`, `Hidden` FROM `Columns` WHERE `Name`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("s", $_POST["Forum"]);
        if ($this->CHeckStmt ($Stmt, true) == false) return;
        $Stmt->bind_result ($nColumnID, $bColumnHidden);
        if ($Stmt->fetch () == false || ($bHidden && $bViewHidden == false))
        {
            new HSRedirection (4);
            return;
        }
        $Stmt->close ();
        
        $Stmt = $this->m_DB->prepare ("UPDATE `Threads` SET `ToID`=? WHERE `ID`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("si", $nColumnID, $_GET["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->close ();
        
        new HSRedirection ("Thread.php?ID=". $_GET["ID"]);
    }//MoveThread
}//HSThread
