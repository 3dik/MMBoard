<?

if (defined ("HS_GROUP")) return 0;
define ("HS_GROUP", "");

//Gruppenklasse

class HSGroup extends HSElement
{
    //ListElements
    protected function ListElements ()
    {
        $bViewInvisible = IsRight ($this->m_DB, "ViewInvisibleGroup");
        
        $Stmt = $this->m_DB->prepare ("SELECT `ID`, `Name`, `Visible` FROM `Groups` ORDER BY `Name` ASC;");
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->bind_result ($nGroupID, $strGroupName, $bGroupVisible);
        
        $Layout = new HSLayout ("Gruppenliste", TEMPLATE, $this->m_DB);
        $Layout->OpenFieldset ("Gruppenliste");
        
        if ($Stmt->fetch () == false)
        {
            $Layout->Paragraph ("Keine Gruppen vorhanden.");
        }
        else
        {
            $Layout->OpenList ();
            do
            {
                if ($bGroupVisible == false && $bViewInvisible == false) continue;
                $Layout->Listitem ("<a href=\"Group.php?ID=". $nGroupID. "\">". $strGroupName. "</a>");
            } while ($Stmt->fetch ());
            $Layout->CloseList ();
        }
        $Layout->CloseFieldset ();
        
        if (IsRight ($this->m_DB, "NewGroup")) $Layout->Paragraph ("<a href=\"Group.php?Action=New\">Neue Gruppe erstellen</a>");
    }//ListElements
    
    
    //ShowElement
    protected function ShowElement ()
    {
        $Stmt = $this->m_DB->prepare ("SELECT `ID`, `Name` FROM `Groups` WHERE `ID`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("i", $_GET["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->bind_result ($nGroupID, $strGroupName);
        $Stmt->fetch ();
        $Stmt->close ();
        
        $Layout = new HSLayout ("Gruppe: ". $strGroupName, TEMPLATE, $this->m_DB);
        $Layout->Paragraph ("In einer Gruppe sind bestimmte Rechte gespeichert, die jeder besitzt, der Mitglied in dieser Gruppe ist.");
        
        $Stmt = $this->m_DB->prepare ("
        SELECT `Users`.`ID`, `Users`.`Name`
        FROM `Users`
        INNER JOIN `Groupjoins`
        ON `Users`.`ID` = `Groupjoins`.`UserID` AND `Groupjoins`.`GroupID` = ?
        ORDER BY `Users`.`Name` ASC;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("i", $nGroupID);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->bind_result ($nUserID, $strUserName);
        if ($Stmt->fetch ())
        {
            $Layout->OpenFieldset ("Mitgliederliste");
            $Layout->OpenList ();
            do
            {
                $Layout->Listitem ("<a href=\"User.php?ID=". $nUserID. "\">". $strUserName. "</a>");
            } while ($Stmt->fetch ());
            $Layout->CloseList ();
            $Layout->CloseFieldset ();
        }
        $Stmt->close ();
        
        $Layout->OpenFieldset ("Rechteliste");
        
        $Stmt = $this->m_DB->prepare ("
SELECT `Rights`.`ID`, `Rights`.`Description`
FROM `Rights`
INNER JOIN `Grouprights`
ON `Grouprights`.`GroupID` = ? AND `Grouprights`.`RightID` = `Rights`.`ID`
ORDER BY `Rights`.`Name`;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("i", $nGroupID);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->bind_result ($nRightID, $strRightDescription);
        if ($Stmt->fetch () == false)
        {
            $Layout->Paragraph ("In dieser Gruppe sind keine Rechte gespeichert.");
        }
        else
        {
            $Layout->OpenList ();
            do
            {
                $Layout->Listitem ("<a href=\"Right.php?ID=". $nRightID. "\">". $strRightDescription. "</a>");
            } while ($Stmt->fetch ());
            $Layout->CloseList ();
        }
        $Layout->CloseFieldset ();
        
        if (IsRight ($this->m_DB, "EditGroup")) $Layout->Paragraph ("<a href=\"Group.php?Action=Edit&ID=". $nGroupID. "\">Gruppe editieren</a>");
        if (IsRight ($this->m_DB, "KickGroup")) $Layout->Paragraph ("<a href=\"Group.php?Action=Kick&ID=". $nGroupID. "\">Gruppe löschen</a>");
        if (IsRight ($this->m_DB, "ControlRight"))
        {
            $Layout->Paragraph ("<a href=\"Group.php?Action=Edit&Add&ID=". $nGroupID. "\">Recht zur Gruppe hinzufügen</a>");
            $Layout->Paragraph ("<a href=\"Group.php?Action=Edit&Deduct&ID=". $nGroupID. "\">Recht der Gruppe entziehen</a>");
        }
    }//ShowElement
    
    
    //EditElement
    protected function EditElement ()
    {
        $strUserRight = "EditGroup";
        if (isset ($_GET["Add"]) || isset ($_GET["Deduct"]) || isset ($_POST["Add"]) || isset ($_POST["Deduct"])) //Rechteprüfung aktualisieren
        {
            $strUserRight = "ControlRight";
        }
        if (IsRight ($this->m_DB, $strUserRight) == false)
        {
            new HSRedirection (2);
            return;
        }
        unset ($strUserRight);
        
        if (isset ($_GET["Execute"]) == false)
        {
        
            if (isset ($_GET["Add"])) //Gruppe nicht editieren, sondern Recht zur Gruppe hinzufügen
            {
                $Layout = new HSLayout ("Recht zur Gruppe hinzufügen", TEMPLATE, $this->m_DB);
                $this->OpenElementForm ("Recht zur Gruppe hinzufügen", "Group.php", $Layout);
                $Layout->Hidden ("Add");
                $Layout->Input ("Recht", "text");
                $this->CloseElementForm ($Layout);
                return;
            }
            else if (isset ($_GET["Deduct"])) //Gruppe nicht editieren, sondern Recht von Gruppe entfernen
            {
                $Layout = new HSLayout ("Recht der Gruppe entziehen", TEMPLATE, $this->m_DB);
                $this->OpenElementForm ("Recht der Gruppe entziehen", "Group.php", $Layout);
                $Layout->Hidden ("Deduct");
                $Layout->Input ("Recht", "text");
                $this->CloseElementForm ($Layout);
                return;
            }
            
            $Stmt = $this->m_DB->prepare ("SELECT `Name` FROM `Groups` WHERE `ID`=?;");
            if ($this->CheckStmt ($Stmt, false) == false) return;
            $Stmt->bind_param ("i", $_GET["ID"]);
            if ($this->CheckStmt ($Stmt, true) == false) return;
            $Stmt->bind_result ($strGroupName);
            $Stmt->fetch ();
            $Stmt->close ();
            
            $Layout = new HSLayout ("Gruppe editieren", TEMPLATE, $this->m_DB);
            $this->OpenElementForm ("Gruppe editieren", "Group.php", $Layout);
            $Layout->Input ("Name", "text", $strGroupName);
            $this->CloseElementForm ($Layout);
            return;
        }
        
        if (isset ($_POST["Add"])) //Recht zur Gruppe hinzufügen
        {
            if ($this->Check ($_POST["Recht"], 100) == false) return;
            
            $Stmt = $this->m_DB->prepare ("SELECT `ID` FROM `Rights` WHERE `Name`=?;");
            if ($this->CheckStmt ($Stmt, false) == false) return;
            $Stmt->bind_param ("s", $_POST["Recht"]);
            if ($this->CheckStmt ($Stmt, true) == false) return;
            $Stmt->bind_result ($nRightID);
            if ($Stmt->fetch () == false)
            {
                new HSRedirection (11);
                return;
            }
            $Stmt->close ();
            
            $Stmt = $this->m_DB->prepare ("SELECT COUNT(*) FROM `Grouprights` WHERE `GroupID`=? AND `RightID`=?;");
            if ($this->CheckStmt ($Stmt, false) == false) return;
            $Stmt->bind_param ("ii", $_GET["ID"], $nRightID);
            if ($this->CheckStmt ($Stmt, true) == false) return;
            $Stmt->bind_result ($nRights);
            $Stmt->fetch ();
            $Stmt->close ();
            
            if ($nRights == 0) //Wenn die Gruppe dieses Recht nicht bereits besitzt, hinzufügen
            {
                $Stmt = $this->m_DB->prepare ("INSERT INTO `Grouprights` (`GroupID`, `RightID`) VALUES (?,?)");
                if ($this->CheckStmt ($Stmt, false) == false) return;
                $Stmt->bind_param ("ii", $_GET["ID"], $nRightID);
                if ($this->CheckStmt ($Stmt, true) == false) return;
                $Stmt->close ();
            }
        }
        else if (isset ($_POST["Deduct"])) //Recht der Gruppe entziehen
        {
            if ($this->Check ($_POST["Recht"]) == false) return;
            
            $Stmt = $this->m_DB->prepare ("SELECT `ID` FROM `Rights` WHERE `Name`=?;");
            if ($this->CheckStmt ($Stmt, false) == false) return;
            $Stmt->bind_param ("s", $_POST["Recht"]);
            if ($this->CheckStmt ($Stmt, true) == false) return;
            $Stmt->bind_result ($nRightID);
            if ($Stmt->fetch () == false)
            {
                new HSRedirection (11);
                return;
            }
            $Stmt->close ();
            
            $Stmt = $this->m_DB->prepare ("DELETE FROM `Grouprights` WHERE `GroupID`=? AND `RightID`=?;");
            if ($this->CheckStmt ($Stmt, false) == false) return;
            $Stmt->bind_param ("ii", $_GET["ID"], $nRightID);
            if ($this->CheckStmt ($Stmt, true) == false) return;
            $Stmt->close ();
        }
        else
        {
            if ($this->Check ($_POST["Name"]) == false) return;
            
            $Stmt = $this->m_DB->prepare ("UPDATE `Groups` SET `Name`=? WHERE `ID`=?;");
            if ($this->CheckStmt ($Stmt, false) == false) return;
            $_POST["Name"] = $this->Filter ($_POST["Name"]);
            $Stmt->bind_param ("si", $_POST["Name"], $_GET["ID"]);
            if ($this->CheckStmt ($Stmt, true) == false) return;
            $Stmt->close ();
        }
            
        new HSRedirection ("Group.php?ID=". $_GET["ID"]);
        return;
    }//EditElement
    
    
    //ExistsElement
    protected function ExistsElement ()
    {
        $Stmt = $this->m_DB->prepare ("SELECT `Visible` FROM `Groups` WHERE `ID`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("i", $_GET["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->bind_result ($bGroupVisible);
        if ($Stmt->fetch () == false) return false;
        $Stmt->close ();
        
        if ($bGroupVisible == false) return IsRight ($this->m_DB, "ViewInvisibleGroup");
        return true;
    }//ExsitsElement
    
    
    //NewElement
    protected function NewElement ()
    {
        if (IsRight ($this->m_DB, "NewGroup") == false)
        {
            new HSRedirection (2);
            return;
        }
    
        if (isset ($_GET["Execute"]) == false)
        {
            $Layout = new HSLayout ("Neue Gruppe erstellen", TEMPLATE, $this->m_DB);
            $this->OpenElementForm ("Neue Gruppe erstellen", "Group.php", $Layout);
            $Layout->Input ("Name", "text");
            $Layout->Input ("Sichtbar", "bool");
            $this->CloseElementForm ($Layout);
            return;
        }
        
        if ($this->Check ($_POST["Name"]) == false) return;
        if ($this->FilterBool ($_POST["Sichtbar"]) == false) return;
        $_POST["Name"] = $this->Filter ($_POST["Name"]);
        
        $Stmt = $this->m_DB->prepare ("SELECT COUNT(*) FROM `Groups` WHERE `Name`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("s", $_POST["Name"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->bind_result ($nGroups);
        $Stmt->fetch ();
        $Stmt->close ();
        
        if ($nGroups != 0)
        {
            new HSRedirection ("Group.php");
            return;
        }
        
        $Stmt = $this->m_DB->prepare ("INSERT INTO `Groups` (`Name`, `Visible`) VALUES (?,?);");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("si", $_POST["Name"], $_POST["Sichtbar"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->close ();
        
        new HSRedirection ("Group.php?ID=". $this->m_DB->insert_id);
        return;
    }//NewElement
    
    
    //KickElement
    protected function KickElement ()
    {
        if (IsRight ($this->m_DB, "KickGroup") == false)
        {
            new HSRedirection (2);
            return;
        }
        
        if (isset ($_GET["Execute"]) == false)
        {
            $Layout = new HSLayout ("Gruppe löschen", TEMPLATE, $this->m_DB);
            $Layout->Paragraph ("Gebe den Namen der zu löschenden Gruppe nochmals ein, um versehentliche Löschungen zu verhindern.");
            $this->OpenElementForm ("Gruppe löschen", "Group.php", $Layout);
            $Layout->Input ("Name", "text");
            $this->CloseElementForm ($Layout);
            return;
        }
        
        if ($this->Check ($_POST["Name"]) == false) return;
        
        $Stmt = $this->m_DB->prepare ("SELECT COUNT(*) FROM `Groups` WHERE `ID`=? AND `Name`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("is", $_GET["ID"], $_POST["Name"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->bind_result ($nGroups);
        $Stmt->fetch ();
        $Stmt->close ();
        
        if ($nGroups == 0)
        {
            new HSRedirection (10);
            return;
        }
        
        $Stmt = $this->m_DB->prepare ("DELETE FROM `Groupjoins` WHERE `GroupID`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("i", $_GET["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->close ();
        
        $Stmt = $this->m_DB->prepare ("DELETE FROM `Groups` WHERE `ID`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("i", $_GET["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->close ();
        
        new HSRedirection ("Group.php");
        return;
    }//KickElement
}//HSGroup
