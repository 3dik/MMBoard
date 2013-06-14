<?

if (defined ("HS_USER")) return 0;
define ("HS_USER", "");

//Userklasse
//Private
//
//AddGroup                    Fügt User in Gruppe ein
//CheckUserRight ($strMode)   Prüft ob User passende Rechte zur Bearbeitung hat
//DeductGroup                 Entfernt User aus Gruppe

class HSUser extends HSElement
{
    //ListElements
    protected function ListElements ()
    {
        $Layout = new HSLayout ("Userliste", TEMPLATE, $this->m_DB);
        
        $Stmt = $this->m_DB->prepare ("SELECT `ID`, `Name` FROM `Users` ORDER BY `Name` ASC;");
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->bind_result ($nUserID, $strUserName);
        $Layout->OpenFieldset ("Userliste");
        if ($Stmt->fetch () == false)
        {
            $Layout->Paragraph ("Keine User vorhanden");
        }
        else
        {
            $Layout->OpenList ();
            
            do
            {
                $Layout->Listitem ("<a href=\"User.php?ID=". $nUserID. "\">". $strUserName. "</a>");
            } while ($Stmt->fetch ());
            
            $Layout->CloseList ();
        }
        $Layout->CloseFieldset ();
        
        if (IsRight ($this->m_DB, "NewUser")) $Layout->Paragraph ("<a href=\"User.php?Action=New\">User erstellen</a>");
    }//ListElements
    
    
    //ShowElement
    protected function ShowElement ()
    {
        $Stmt = $this->m_DB->prepare ("SELECT `ID`, `Name`, `Creation`, `Info`, `Barred`, `LastLogin` FROM `Users` WHERE `ID`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("i", $_GET["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->bind_result ($nUserID, $strUserName, $UserCreation, $strUserInfo, $bUserBarred, $nLastLogin);
        $Stmt->fetch ();
        $Stmt->close ();
        
        $Layout = new HSLayout ("Profil: ". $strUserName, TEMPLATE, $this->m_DB);
        $Layout->OpenFieldset ("Daten");
        if ($bUserBarred) $Layout->Paragraph ("<strong>Dieser User ist deaktiviert.</strong>");
        $Layout->Paragraph ("Name: ". $strUserName);
        $Layout->Paragraph ("Erstellungsdatum: ". $this->m_Time->Get ("", $UserCreation));
        if (IsRight ($this->m_DB, "ViewForum"))
        {
            $Stmt = $this->m_DB->prepare ("SELECT COUNT(*) FROM `Posts` WHERE `FromID`=?;");
            if ($this->CheckStmt ($Stmt, false) == false) return;
            $Stmt->bind_param ("i", $_GET["ID"]);
            if ($this->CheckStmt ($Stmt, true) == false) return;
            $Stmt->bind_result ($nPosts);
            $Stmt->fetch ();
            $Stmt->close ();
            
            $Layout->Paragraph ("Beiträge: ". $nPosts);
        }
        if (IsRight ($this->m_DB, "ViewLastLogin")) $Layout->Paragraph ("Letzter Login: ". $this->m_Time->Get ("", $nLastLogin));
        if ($strUserInfo != "")
        {
            $Layout->OpenFieldset ("Info");
            $Layout->TextBlock ($this->Convert ($strUserInfo, true), HS_LAYOUT_TEXT);
            $Layout->CloseFieldset ();
        }
        
        $bViewInvisible = IsRight ($this->m_DB, "ViewInvisibleGroup");
        
        $Stmt = $this->m_DB->prepare ("
        SELECT `Groups`.`ID`, `Groups`.`Name`, `Groups`.`Visible`
        FROM `Groups`
        INNER JOIN `Groupjoins`
        ON `Groupjoins`.`UserID` = ? AND `Groupjoins`.`GroupID` = `Groups`.`ID`
        ORDER BY `Groups`.`Name` ASC;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("i", $nUserID);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->bind_result ($nGroupID, $strGroupName, $bGroupVisible);
        if ($Stmt->fetch ())
        {
            $Layout->OpenFieldset ("Gruppenzugehörigkeiten");
            $Layout->OpenList ();
            do
            {
                if ($bGroupVisible == false && $bViewInvisible == false) continue;
                $Layout->Listitem ("<a href=\"Group.php?ID=". $nGroupID. "\">". $strGroupName. "</a>");
            }
            while ($Stmt->fetch ());
            $Layout->CloseList ();
            $Layout->CloseFieldset ();
        }
        $Stmt->close ();
        
        $Layout->CloseFieldset ();
        
        if ($_SESSION["ID"] != GUEST_ID && $_SESSION["ID"] != $nUserID)
        {
            $Layout->Paragraph ("<a href=\"Letter.php?Action=New&UserID=". $nUserID. "\">Nachricht schicken</a>");
        }
        if (IsRight ($this->m_DB, "MoveUser"))
        {
            $Layout->Paragraph ("<a href=\"User.php?Action=Edit&Add&ID=". $nUserID. "\">User in Gruppe einfügen</a>");
            $Layout->Paragraph ("<a href=\"User.php?Action=Edit&Deduct&ID=". $nUserID. "\">User aus Gruppe entfernen</a>");
        }
        if ($this->CheckUserRight ("Edit")) $Layout->Paragraph ("<a href=\"User.php?Action=Edit&ID=". $nUserID. "\">User bearbeiten</a>");
        if ($this->CheckUserRight ("Kick")) $Layout->Paragraph ("<a href=\"User.php?Action=Kick&ID=". $nUserID. "\">User löschen</a>");
    }//ShowElement
    
    
    //EditElement
    protected function EditElement ()
    {
        if (isset ($_GET["Execute"]) == false)
        {
            if (isset ($_GET["Add"]))
            {
                $this->AddGroup ();
                return;
            }
            if (isset ($_GET["Deduct"]))
            {
                $this->DeductGroup ();
                return;
            }
            
            if ($this->CheckUserRight ("Edit") == false)
            {
                new HSRedirection (2);
                return;
            }
            
            $Stmt = $this->m_DB->prepare ("SELECT `ID`, `Info`, `Barred` FROM `Users` WHERE `ID`=?;");
            if ($this->CheckStmt ($Stmt, false) == false) return;
            $Stmt->bind_param ("i", $_GET["ID"]);
            if ($this->CheckStmt ($Stmt, true) == false) return;
            $Stmt->bind_result ($nUserID, $strUserInfo, $bUserBarred);
            $Stmt->fetch ();
            $Stmt->close ();
            
            $Layout = new HSLayout ("User editieren", TEMPLATE, $this->m_DB);
            $Layout->Paragraph ("Die Passwortfelder müssen nur angegeben werden, wenn ein neues Passwort verwendet werden soll. Wenn du das Passwort änderst, wirst du aus Sicherheitsgründen automatisch ausgeloggt.");
            $this->OpenElementForm ("User editieren", "User.php", $Layout);
            $Layout->Input ("Info", "textarea", $strUserInfo);
            $Layout->Input ("Altes Passwort", "password", "", "OldPass");
            $Layout->Input ("Neues Passwort", "password", "", "NewPass1");
            $Layout->Input ("Neues Passwort wiederholen", "password", "", "NewPass2");
            if (IsRight ($this->m_DB, "LockUser")) $Layout->Input ("Gesperrt", "bool", $bUserBarred, "Barred");
            $this->CloseElementForm ($Layout);
            
            return;
        }
        
        if (isset ($_POST["Add"]))
        {
            $this->AddGroup ();
            return;
        }
        if (isset ($_POST["Deduct"]))
        {
            $this->DeductGroup ();
            return;
        }
        
        if ($this->Check ($_POST["Info"], 1000, 0) == false) return;
        
        if ($this->CheckUserRight ("Edit") == false)
        {
            new HSRedirection (2);
            return;
        }
        
        $Stmt = $this->m_DB->prepare ("SELECT `ID`, `Password` FROM `Users` WHERE `ID`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("i", $_GET["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->bind_result ($nUserID, $strUserPasswordhash);
        $Stmt->fetch ();
        $Stmt->close ();
        
        $Stmt = $this->m_DB->prepare ("UPDATE `Users` SET `Info`=? WHERE `ID`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $_POST["Info"] = $this->Filter ($_POST["Info"]);
        $Stmt->bind_param ("si", $_POST["Info"], $_GET["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->close ();
        
        if (IsRight ($this->m_DB, "LockUser")) //Später durch Rechteabfrage ersetzen!!!
        {
            if (IsRight ($this->m_DB, "LockUser") == false)
            {
                new HSRedirection (2);
                return;
            }
            
            if ($this->FilterBool ($_POST["Barred"]) == false) return;
            
            $Stmt = $this->m_DB->prepare ("UPDATE `Users` SET `Barred`=? WHERE `ID`=?;");
            if ($this->CheckStmt ($Stmt, false) == false) return;
            $Stmt->bind_param ("ii", $_POST["Barred"], $nUserID);
            if ($this->CheckStmt ($Stmt, true) == false) return;
            $Stmt->close ();
        }
        
        if ($_POST["OldPass"] != "")
        {
            if ($this->Check ($_POST["NewPass1"], 100, 3) == false) return;
            
            if ($_POST["NewPass1"] != $_POST["NewPass2"])
            {
                new HSRedirection (6);
                return;
            }
            
            if (sha1 ($_POST["OldPass"]) != $strUserPasswordhash)
            {
                new HSRedirection (7);
                return;
            }
            
            $Stmt = $this->m_DB->prepare ("UPDATE `Users` SET `Password`=? WHERE `ID`=?;");
            if ($this->CheckStmt ($Stmt, false) == false) return;
            $Stmt->bind_param ("si", sha1 ($_POST["NewPass1"]), $nUserID);
            if ($this->CheckStmt ($Stmt, true) == false) return;
            $Stmt->close ();
            
            session_unset ();
            new HSRedirection ("Login.php");
            return;
        }
        
        new HSRedirection ("User.php?ID=". $nUserID);
    }//EditElement
    
    
    //ExistsElement
    protected function ExistsElement ()
    {
        $Stmt = $this->m_DB->prepare ("SELECT COUNT(*) FROM `Users` WHERE `ID`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("i", $_GET["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->bind_result ($nUsers);
        $Stmt->fetch ();
        $Stmt->close ();
        
        if ($nUsers != 0) return true;
        return false;
    }//ExsitsElement
    
    
    //NewElement
    protected function NewElement ()
    {
        if (IsRight ($this->m_DB, "NewUser") == false)
        {
            new HSRedirection (2);
            return;
        }
        
        if (isset ($_GET["Execute"]) == false)
        {
            $Layout = new HSLayout ("User erstellen", TEMPLATE, $this->m_DB);
            $this->OpenElementForm ("User erstellen", "User.php", $Layout);
            $Layout->Input ("Name", "text");
            $Layout->Input ("Passwort", "password", "", "PW1");
            $Layout->Input ("Passwort wiederholen", "password", "", "PW2");
            $this->CloseElementForm ($Layout);
            return;
        }
        
        if ($this->Check ($_POST["Name"], 20, 3) == false) return;
        if ($this->Check ($_POST["PW1"], 500, 3) == false) return;
        if ($this->Check ($_POST["PW2"], 500, 3) == false) return;
        
        if ($_POST["PW1"] != $_POST["PW2"])
        {
            new HSRedirection (6);
            return;
        }
        
        $Stmt = $this->m_DB->prepare ("SELECT COUNT(*) FROM `Users` WHERE `Name`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("s", $_POST["Name"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->bind_result ($nUsers);
        $Stmt->fetch ();
        $Stmt->close ();
        
        if ($nUsers != 0)
        {
            new HSRedirection (9);
            return;
        }
        
        $Stmt = $this->m_DB->prepare ("INSERT INTO `Users` (`Name`, `Password`, `Creation`) VALUES (?,?,?);");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $nTime = time ();
        $_POST["PW1"] = sha1 ($_POST["PW1"]);
        $_POST["Name"] = $this->Filter ($_POST["Name"]);
        $Stmt->bind_param ("ssi", $_POST["Name"], $_POST["PW1"], $nTime);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->close ();
        
        new HSRedirection ("User.php?ID=". $this->m_DB->insert_id);
    }//NewElement
    
    
    //KickElement
    protected function KickElement ()
    {
        if ($this->CheckUserRight ("Kick") == false)
        {
            new HSRedirection (2);
            return;
        }
        
        if (isset ($_GET["Execute"]) == false)
        {
            $Layout = new HSLayout ("User löschen", TEMPLATE, $this->m_DB);
            $Layout->Paragraph ("Bitte gebe den Namen des zu löschenden Users ein, um versehentliche User zu löschen.");
            $this->OpenElementForm ("User löschen", "User.php", $Layout);
            $Layout->Input ("Name", "text");
            $this->CloseElementForm ($Layout);
            return;
        }
        
        if ($this->Check ($_POST["Name"]) == false) return;
        
        $Stmt = $this->m_DB->prepare ("SELECT COUNT(*) FROM `Users` WHERE `ID`=? AND `Name`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("is", $_GET["ID"], $_POST["Name"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->bind_result ($nUsers);
        $Stmt->fetch ();
        $Stmt->close ();
        
        if ($nUsers != 1)
        {
            new HSRedirection (10);
            return;
        }
        
        $Stmt = $this->m_DB->prepare ("DELETE FROM `Groupjoins` WHERE `UserID`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("i", $_GET["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->close ();
        
        $Stmt = $this->m_DB->prepare ("DELETE FROM `Users` WHERE `ID`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("i", $_GET["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->close ();
        
        new HSRedirection ("User.php");
    }//KickElement
    
    
    //AddGroup
    private function AddGroup ()
    {
        if (isset ($_GET["Add"]))
        {
            $Layout = new HSLayout ("User in Gruppe einfügen", TEMPLATE, $this->m_DB);
            $this->OpenElementForm ("User in Gruppe einfügen", "User.php", $Layout);
            $Layout->Hidden ("Add");
            $Layout->Input ("Gruppe", "text");
            $this->CloseElementForm ($Layout);
            return;
        }
        
        if ($this->Check ($_POST["Gruppe"]) == false) return;
        
        $Stmt = $this->m_DB->prepare ("SELECT `ID` FROM `Groups` WHERE `Name`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("s", $_POST["Gruppe"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->bind_result ($nGroupID);
        if ($Stmt->fetch () == false)
        {
            new HSRedirection (13);
            return;
        }
        $Stmt->close ();
        
        $Stmt = $this->m_DB->prepare ("SELECT COUNT(*) FROM `Groupjoins` WHERE `GroupID`=? AND `UserID`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("ii", $nGroupID, $_GET["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->bind_result ($nGroupJoins);
        $Stmt->fetch ();
        $Stmt->close ();
        
        if ($nGroupJoins == 0) //Wenn der User in dieser Gruppe noch nicht ist, in Gruppe einfügen
        {
            $Stmt = $this->m_DB->prepare ("INSERT INTO `Groupjoins` (`GroupID`,`UserID`) VALUES (?,?);");
            if ($this->CheckStmt ($Stmt, false) == false) return;
            $Stmt->bind_param ("ii", $nGroupID, $_GET["ID"]);
            if ($this->CheckStmt ($Stmt, true) == false) return;
            $Stmt->close ();
        }
        
        new HSRedirection ("User.php?ID=". $_GET["ID"]);
        return;
    }//AddGroup
    
    
    //CheckUserRight
    private function CheckUserRight ($strMode)
    {
        if ($strMode == "") return false;
        
        $bControlUser    = IsRight ($this->m_DB, $strMode. "User");
        $bControlOwnUser = IsRight ($this->m_DB, $strMode. "OwnUser");
        
        if ($bControlUser == false)
        {
            if ($_SESSION["ID"] == $_GET["ID"] && $bControlOwnUser) return true; //Darf sich der User selbst bearbeiten?
            return false;
        }
        return true;
    }//CheckUserRight
    
    
    //DeductGroup
    private function DeductGroup ()
    {
        if (isset ($_GET["Deduct"]))
        {
            $Layout = new HSLayout ("User aus Gruppe entfernen", TEMPLATE, $this->m_DB);
            $this->OpenElementForm ("User aus Gruppe entfernen", "User.php", $Layout);
            $Layout->Hidden ("Deduct");
            $Layout->Input ("Gruppe", "text");
            $this->CloseElementForm ($Layout);
            return;
        }
        
        if ($this->Check ($_POST["Gruppe"]) == false) return;
        
        $Stmt = $this->m_DB->prepare ("SELECT `ID` FROM `Groups` WHERE `Name`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("s", $_POST["Gruppe"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->bind_result ($nGroupID);
        if ($Stmt->fetch () == false)
        {
            new HSRedirection (13);
            return;
        }
        $Stmt->close ();
        
        $Stmt = $this->m_DB->prepare ("DELETE FROM `Groupjoins` WHERE `GroupID`=? AND `UserID`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("ii", $nGroupID, $_GET["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->close ();
        
        new HSRedirection ("User.php?ID=". $_GET["ID"]);
        return;
    }//DeductGroup
}//HSUser
