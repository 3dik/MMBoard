<?

if (defined ("HS_RIGHT")) return 0;
define ("HS_RIGHT", "");

//Rechteklasse

class HSRight extends HSElement
{
    //ListElements
    protected function ListElements ()
    {
        $Stmt = $this->m_DB->prepare ("SELECT `ID`, `Name`, `Description` FROM `Rights` ORDER BY `Name` ASC;");
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->bind_result ($nRightID, $strRightName, $strRightDescription);
        $Layout = new HSLayout ("Rechteliste", TEMPLATE, $this->m_DB);
        $Layout->OpenFieldset ("Rechteliste");
        if ($Stmt->fetch () == false)
        {
            $Layout->Paragraph ("Keine Rechte vorhanden.");
        }
        else
        {
            $Layout->OpenList ();
            do
            {
                $Layout->Listitem ("<a href=\"Right.php?ID=". $nRightID. "\">". $strRightDescription. " (". $strRightName. ")</a>");
            } while ($Stmt->fetch ());
            $Layout->CloseList ();
        }
        $Layout->CloseFieldset ();
        if (IsRight ($this->m_DB, "ControlRight"))
        {
            $Layout->Paragraph ("<a href=\"Right.php?Action=New\">Neues Recht erstellen</a>");
        }
    }//ListElements
    
    
    //ShowElement
    protected function ShowElement ()
    {
        $Stmt = $this->m_DB->prepare ("SELECT `ID`, `Name`, `Description` FROM `Rights` WHERE `ID`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("i", $_GET["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->bind_result ($nRightID, $strRightName, $strRightDescription);
        $Stmt->fetch ();
        $Stmt->close ();
        
        $Layout = new HSLayout ("Recht: ". $strRightName, TEMPLATE, $this->m_DB);
        $Layout->OpenFieldset ("Beschreibung");
        $Layout->Textblock ($strRightDescription, HS_LAYOUT_TEXT);
        $Layout->CloseFieldset ();
        
        if (IsRight ($this->m_DB, "ControlRight") == false) return;
        
        $bViewInvisible = IsRight ($this->m_DB, "ViewInvisibleGroup");
        $Stmt = $this->m_DB->prepare ("
        SELECT `Groups`.`ID`, `Groups`.`Name`, `Groups`.`Visible`
        FROM `Groups`
        INNER JOIN `Grouprights`
        ON `Groups`.`ID` = `Grouprights`.`GroupID`
        WHERE `Grouprights`.`RightID` = ?
        ORDER BY `Groups`.`Name` ASC;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("i", $nRightID);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->bind_result ($nGroupID, $strGroupName, $bGroupVisible);
        if ($Stmt->fetch ())
        {
            $Layout->OpenFieldset ("In folgenden Gruppen enthalten");
            $Layout->OpenList ();
            do
            {
                if ($bGroupVisible == false && $bViewInvisible == false) continue;
                $Layout->Listitem ("<a href=\"Group.php?ID=". $nGroupID. "\">". $strGroupName. "</a>");
            } while ($Stmt->fetch ());
            $Layout->CloseList ();
            $Layout->CloseFieldset ();
        }
        $Stmt->close ();
        
        $Layout->Paragraph ("<a href=\"Right.php?Action=Edit&ID=". $nRightID. "\">Recht editieren</a>");
        $Layout->Paragraph ("<a href=\"Right.php?Action=Kick&ID=". $nRightID. "\">Recht löschen</a>");
    }//ShowElement
    
    
    //EditElement
    protected function EditElement ()
    {
        if (IsRight ($this->m_DB, "ControlRight") == false)
        {
            new HSRedirection (2);
            return;
        }
        
        if (isset ($_GET["Execute"]) == false)
        {
            $Stmt = $this->m_DB->prepare ("SELECT `Description` FROM `Rights` WHERE `ID`=?;");
            if ($this->CheckStmt ($Stmt, false) == false) return;
            $Stmt->bind_param ("i", $_GET["ID"]);
            if ($this->CheckStmt ($Stmt, true) == false) return;
            $Stmt->bind_result ($strRightDescription);
            $Stmt->fetch ();
            $Stmt->close ();
            
            $Layout = new HSLayout ("Recht editieren", TEMPLATE, $this->m_DB);
            $this->OpenElementForm ("Recht editieren", "Right.php", $Layout);
            $Layout->Input ("Beschreibung", "textarea", $strRightDescription);
            $this->CloseElementForm ($Layout);
            return;
        }
        
        if ($this->Check ($_POST["Beschreibung"], 1000) == false) return;
        
        $Stmt = $this->m_DB->prepare ("UPDATE `Rights` SET `Description`=? WHERE `ID`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $_POST["Beschreibung"] = $this->Filter ($_POST["Beschreibung"]);
        $Stmt->bind_param ("si", $_POST["Beschreibung"], $_GET["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->close ();
        
        new HSRedirection ("Right.php?ID=". $_GET["ID"]);
        return;
    }//EditElement
    
    
    //ExistsElement
    protected function ExistsElement ()
    {
        $Stmt = $this->m_DB->prepare ("SELECT COUNT(*) FROM `Rights` WHERE `ID`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("i", $_GET["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->bind_result ($nRights);
        $Stmt->fetch ();
        $Stmt->close ();
        
        if ($nRights == 0) return false;
        return true;
    }//ExsitsElement
    
    
    //NewElement
    protected function NewElement ()
    {
        if (IsRight ($this->m_DB, "ControlRight") == false)
        {
            new HSRedirection (2);
            return;
        }
        
        if (isset ($_GET["Execute"]) == false)
        {
            $Layout = new HSLayout ("Neues Recht erstellen", TEMPLATE, $this->m_DB);
            $this->OpenElementForm ("Neues Recht erstellen", "Right.php", $Layout);
            $Layout->Input ("Name", "text");
            $Layout->Input ("Beschreibung", "textarea");
            $this->CloseElementForm ($Layout);
            return;
        }
        
        if ($this->Check ($_POST["Name"], 100) == false) return;
        if ($this->Check ($_POST["Beschreibung"], 1000) == false) return;
        
        $Stmt = $this->m_DB->prepare ("SELECT COUNT(*) FROM `Rights` WHERE `Name`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("s", $_POST["Name"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->bind_result ($nRights);
        $Stmt->fetch ();
        $Stmt->close ();
        
        if ($nRights != 0)
        {
            new HSRedirection (12);
            return;
        }
        
        $Stmt = $this->m_DB->prepare ("INSERT INTO `Rights` (`Name`, `Description`) VALUES (?,?);");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $_POST["Name"] = $this->Filter ($_POST["Name"]);
        $_POST["Beschreibung"] = $this->Filter ($_POST["Beschreibung"]);
        $Stmt->bind_param ("ss", $_POST["Name"], $_POST["Beschreibung"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->close ();
        
        new HSRedirection ("Right.php?ID=". $this->m_DB->insert_id);
        return;
    }//NewElement
    
    
    //KickElement
    protected function KickElement ()
    {
        if (IsRight ($this->m_DB, "ControlRight") == false)
        {
            new HSRedirection (2);
            return;
        }
        
        if (isset ($_GET["Execute"]) == false)
        {
            $Layout = new HSLayout ("Recht löschen", TEMPLATE, $this->m_DB);
            $Layout->Paragraph ("Gebe den Namen des zu löschenden Rechtes noch einmal ein, um versehentliche Löschungen zu verhindern.");
            $this->OpenElementForm ("Recht löschen", "Right.php", $Layout);
            $Layout->Input ("Name", "text");
            $this->CloseElementform ($Layout);
            return;
        }
        
        if ($this->Check ($_POST["Name"], 100) == false) return;
        
        $Stmt = $this->m_DB->prepare ("SELECT COUNT(*) FROM `Rights` WHERE `ID`=? AND `Name`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("is", $_GET["ID"], $_POST["Name"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->bind_result ($nRights);
        $Stmt->fetch ();
        $Stmt->close ();
        
        if ($nRights == 0)
        {
            new HSRedirection (10);
            return;
        }
        
        $Stmt = $this->m_DB->prepare ("DELETE FROM `Grouprights` WHERE `RightID`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("i", $_GET["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->close ();
        
        $Stmt = $this->m_DB->prepare ("DELETE FROM `Rights` WHERE `ID`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("i", $_GET["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->close ();
        
        new HSRedirection ("Right.php");
        return;
    }//KickElement
}//HSRight

?>
