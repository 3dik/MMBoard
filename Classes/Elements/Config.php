<?

if (defined ("HS_CONFIG")) return 0;
define ("HS_CONFIG", "");

//Configklasse

class HSConfig extends HSElement
{
    //ListElements
    protected function ListElements ()
    {
        $Layout = new HSLayout ("Configliste", TEMPLATE, $this->m_DB);
        
        $Stmt = $this->m_DB->prepare ("SELECT `ID`, `Name`, `Description` FROM `Configs` ORDER BY `Name` ASC;");
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->bind_result ($nConfigID, $strConfigName, $strConfigDescription);
        $Layout->OpenFieldset ("Configliste");
        if ($Stmt->fetch () == false)
        {
            $Layout->Paragraph ("Keine Configs vorhanden.");
        }
        else
        {
            $Layout->OpenList ();
            do
            {
                $Layout->Listitem ("<a href=\"Config.php?ID=". $nConfigID. "\">". $strConfigName. "</a> - ". $strConfigDescription);
            } while ($Stmt->fetch ());
            $Layout->CloseList ();
        }
        $Layout->CloseFieldset ();
        
        $Layout->Paragraph ("<a href=\"Config.php?Action=New\">Config erstellen</a>");
    }//ListElements
    
    
    //ShowElement
    protected function ShowElement ()
    {
        $Stmt = $this->m_DB->prepare ("SELECT `Name`, `Value`, `Description` FROM `Configs` WHERE `ID`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("i", $_GET["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->bind_result ($strConfigName, $strConfigValue, $strConfigDescription);
        $Stmt->fetch ();
        $Stmt->close ();
        
        $Layout = new HSLayout ("Config: ". $strConfigName, TEMPLATE, $this->m_DB);
        $Layout->OpenFieldset ("Infos");
        $Layout->Paragraph ("Name: ". $strConfigName);
        $Layout->Paragraph ("Beschreibung: ". $strConfigDescription);
        $Layout->OpenFieldset ("Wert");
        $Layout->Textblock ($this->Convert($strConfigValue, true), HS_LAYOUT_TEXT);
        $Layout->CloseFieldset ();
        $Layout->CloseFieldset ();
        
        $Layout->Paragraph ("<a href=\"Config.php?Action=Edit&ID=". $_GET["ID"]. "\">Config editieren</a>");
        $Layout->Paragraph ("<a href=\"Config.php?Action=Kick&ID=". $_GET["ID"]. "\">Config löschen</a>");
    }//ShowElement
    
    
    //EditElement
    protected function EditElement ()
    {
        if (isset ($_GET["Execute"]) == false)
        {
            $Stmt = $this->m_DB->prepare ("SELECT `Name`, `Value`, `Description` FROM `Configs` WHERE `ID`=?;");
            if ($this->CheckStmt ($Stmt, false) == false) return;
            $Stmt->bind_param ("i", $_GET["ID"]);
            if ($this->CheckStmt ($Stmt, true) == false) return;
            $Stmt->bind_result ($strConfigName, $strConfigValue, $strConfigDescription);
            $Stmt->fetch ();
            $Stmt->close ();
            
            $Layout = new HSLayout ("Config editieren", TEMPLATE, $this->m_DB);
            $this->OpenElementForm ("Config editieren", "Config.php", $Layout);
            $Layout->Input ("Name", "text", $strConfigName);
            $Layout->Input ("Wert", "textarea", $strConfigValue);
            $Layout->Input ("Beschreibung", "textarea", $strConfigDescription);
            $this->CloseElementForm ($Layout);
            return;
        }
        
        if ($this->Check ($_POST["Name"]) == false) return;
        if ($this->Check ($_POST["Wert"], 50000, 0) == false) return;
        if ($this->Check ($_POST["Beschreibung"], 1000) == false) return;
        
        $Stmt = $this->m_DB->prepare ("UPDATE `Configs` SET `Name`=?, `Value`=?, `Description`=? WHERE `ID`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return false;
        $_POST["Name"] = $this->Filter ($_POST["Name"]);
        $_POST["Wert"] = $this->Filter ($_POST["Wert"]);
        $_POST["Beschreibung"] = $this->Filter ($_POST["Beschreibung"]);
        $Stmt->bind_param ("sssi", $_POST["Name"], $_POST["Wert"], $_POST["Beschreibung"], $_GET["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return false;
        $Stmt->close ();
        
        new HSRedirection ("Config.php?ID=". $_GET["ID"]);
        return;
    }//EditElement
    
    
    //ExistsElement
    protected function ExistsElement ()
    {
        $Stmt = $this->m_DB->prepare ("SELECT COUNT(*) FROM `Configs` WHERE `ID`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return false;
        $Stmt->bind_param ("i", $_GET["ID"]);
        if ($this->CheckStmt ($Stmt, true) == false) return false;
        $Stmt->bind_result ($nConfigs);
        $Stmt->fetch ();
        $Stmt->close ();
        
        if ($nConfigs == 0) return false;
        return true;
    }//ExsitsElement
    
    
    //NewElement
    protected function NewElement ()
    {
        if (isset ($_GET["Execute"]) == false)
        {
            $Layout = new HSLayout ("Neue Config erstellen", TEMPLATE, $this->m_DB);
            $this->OpenElementForm ("Neue Config erstellen", "Config.php", $Layout);
            $Layout->Input ("Name", "text");
            $Layout->Input ("Wert", "textarea");
            $Layout->Input ("Beschreibung", "textarea");
            $this->CloseElementForm ($Layout);
            return;
        }
        
        if ($this->Check ($_POST["Name"]) == false) return;
        if ($this->Check ($_POST["Wert"], 50000, 0) == false) return;
        if ($this->Check ($_POST["Beschreibung"], 1000) == false) return;
        
        $Stmt = $this->m_DB->prepare ("INSERT INTO `Configs` (`Name`, `Value`, `Description`) VALUES (?,?,?);");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $_POST["Name"] = $this->Filter ($_POST["Name"]);
        $_POST["Wert"] = $this->Filter ($_POST["Wert"]);
        $_POST["Beschreibung"] = $this->Filter ($_POST["Beschreibung"]);
        $Stmt->bind_param ("sss", $_POST["Name"], $_POST["Wert"], $_POST["Beschreibung"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->close ();
        
        new HSRedirection ("Config.php?ID=". $this->m_DB->insert_id);
        return;
    }//NewElement
    
    
    //KickElement
    protected function KickElement ()
    {
        if (isset ($_GET["Execute"]) == false)
        {
            $Layout = new HSLayout ("Config löschen", TEMPLATE, $this->m_DB);
            $Layout->Paragraph ("Um versehentliche Löschungen zu vermeiden, musst du noch einmal den Namen der zu löschenden Config angeben.");
            $this->OpenElementForm ("Config löschen", "Config.php", $Layout);
            $Layout->Input ("Name", "text");
            $this->CloseElementForm ($Layout);
            return;
        }
        
        $Stmt = $this->m_DB->prepare ("SELECT COUNT(*) FROM `Configs` WHERE `ID`=? AND `Name`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("is", $_GET["ID"], $_POST["Name"]);
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->bind_result ($nConfigs);
        $Stmt->fetch ();
        $Stmt->close ();
        
        if ($nConfigs == 0)
        {
            new HSRedirection (10);
            return;
        }
        
        $Stmt = $this->m_DB->prepare ("DELETE FROM `Configs` WHERE `ID`=?;");
        if ($this->CheckStmt ($Stmt, false) == false) return;
        $Stmt->bind_param ("i", $_GET["ID"]);
        if ($this->ChecKStmt ($Stmt, true) == false) return;
        $Stmt->close ();
        
        new HSRedirection ("Config.php");
        return;
    }//KickElement
}//HSConfig
