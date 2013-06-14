<?

if (defined ("HS_ELEMENT")) return 0;
define ("HS_ELEMENT", "");

//Grundklasse für alle Elemente, verwaltet gesamte Anzeige und Bearbeitung der Elemente
//Jede Elementenklasse muss von dieser Klasse ableiten

//Klassenaufbau
////Protected
//
//__construct      (&$DB)              Bestimmt anhand der globalen Parameter welche Aktion ausgeführt werden soll
//
//EditElement      ()                  Editiert Element
//ExistsElement    ()                  Prüft ob angegebenes Element vorhanden ist
//KickElement      ()                  Löscht Element
//ListElements     ()                  Zeigt alle Elemente an
//NewElement       ()                  Erstellt Element
//
//Check            ($strVar,
//                  $nMax=100,
//                  $nMin=1)           Prüft übergebene Variable auf Existenz und maximaler bzw. minimaler Länge
//CheckStmt        ($Stmt, $bExecute)  Prüft Statement auf gültigkeit, wenn zweiter Parameter true ist, wird das Statement ausgeführt
//CloseElementForm (&$Layout)          Schliesst Formular, das auf das Element zugeschnitten ist
//Convert          ($strVar)           Konvertiert übergebenen BB-Code in HTML-Code
//Filter           ($strVar)           Filtert aus Eingabe gefährliche Zeichen und filtert bestimmte Zeichenketten
//FilterBool       (&$strVar)          Konvertiert übergebene Variable in Boolean um, gibt false zurück, wenn ungültig
//OpenElementForm  ($strLegend,
//                  $strTarget,
//                  &$Layout);         Erstellt Formular, das auf das Element zugeschnitten ist
//ShowElement      ()                  Zeigt Element an
//
//m_DB                                 Referenz auf die Datenbank
//m_ErrorSQL                           SQL-Fehler
//m_Time                               Zeitobjekt
////

include ("Redirection.php");
include ("Time.php");

abstract class HSElement
{
    //__construct
    public function __construct (&$DB)
    {
        if ($DB == false) return;
        
        $this->m_DB         = &$DB;
        
        $this->m_ErrorSQL = new HSRedirection (5, false);
        
        $this->m_Time = new HSTime ();
        
        if (isset ($_GET["Action"]))
        {
            switch ($_GET["Action"])
            {
                case ("New"):
                    $this->NewElement ();
                    break;
                case ("Edit"):
                    if (isset ($_GET["ID"]) == false)
                    {
                        $this->m_ErrorSQL->Execute ();
                        return;
                    }
                    if ($this->ExistsElement () == false)
                    {
                        new HSRedirection (4);
                        return;
                    }
                    $this->EditElement ();
                    break;
                case ("Kick"):
                    if (isset ($_GET["ID"]) == false)
                    {
                        $this->m_ErrorSQL->Execute ();
                        return;
                    }
                    if ($this->ExistsElement () == false)
                    {
                        new HSRedirection (4);
                        return;
                    }
                    $this->KickElement ();
                    break;
                default:
                    $this->m_ErrorSQL->Execute ();
                    return;
            }
        }
        else
        {
            if (isset ($_GET["ID"]))
            {
                if ($this->ExistsElement () == false)
                {
                    new HSRedirection (4);
                    return;
                }
                $this->ShowElement ();
            }
            else
            {
                $this->ListElements ();
            }
        }
    }//__construct
    
    
    //Check
    protected function Check ($strVar, $nMax=100, $nMin=1)
    {
        $ErrorVar = new HSRedirection (8, false);
        
        if (isset ($strVar) == false)
        {
            $ErrorVar->Execute ();
            return false;
        }
        
        if (strlen ($strVar) <= $nMax && strlen ($strVar) >= $nMin) return true;
        
        $ErrorVar->Execute ();
        return false;
    }//Check
    
    
    //CheckStmt
    protected function CheckStmt ($Stmt, $bExecute)
    {
        if ($Stmt == false)
        {
            $this->m_ErrorSQL->Execute ();
            return false;
        }
        if ($bExecute && $Stmt->execute () == false)
        {
            $this->m_ErrorSQL->Execute ();
            return false;
        }
        return true;
    }//CheckStmt
    
    
    //CloseElementForm
    protected function CloseElementForm (&$Layout)
    {
        if ($Layout == false) return;
        
        $Layout->Input ("Senden", "submit");
        $Layout->CloseForm ();
    }//CloseElementForm
    
    
    //Convert
    protected function Convert ($strVar)
    {
        if ($strVar == "") return "";
	    $strVar = preg_replace ("/\[b\](.*?)\[\/b\]/", "<strong>$1</strong>", $strVar);
	    $strVar = preg_replace ('#\[url=(.*)\](.*)\[/url\]#isU', "<a href=\"$1\">$2</a>", $strVar);
	    return $strVar;
    }//Convert
    
    
    //Filter
    protected function Filter ($strVar)
    {
        if ($strVar == "") return false;
        
        $Stmt = $this->m_DB->prepare ("SELECT `Bad`, `Good` FROM `Filter`;");
        if ($this->CheckStmt ($Stmt, true) == false) return;
        $Stmt->bind_result ($strBad, $strGood);
        while ($Stmt->fetch ())
        {
            $strVar = str_replace ($strBad, $strGood, $strVar);
        }
        
        $strVar = htmlspecialchars ($strVar, ENT_QUOTES, "UTF-8");
        $strVar = stripslashes ($strVar);
        
        $strVar = str_replace ("&amp;", "&", $strVar);
        
        return $strVar;
    }//Filter
    
    
    //FilterBool
    protected function FilterBool (&$strVar)
    {
        if ($this->Check ($strVar, 4, 2) == false) return false;
        
        if ($strVar == "Ja")
        {
            $strVar = true;
        }
        else
        {
            $strVar = false;
        }
        
        return true;
    }//FilterBool
    
    
    //OpenElementForm
    protected function OpenElementForm ($strLegend, $strTarget, &$Layout)
    {
        if ($Layout == false || isset ($_GET["Action"]) == false) return;
        
        $strTarget .= "?Action=". $_GET["Action"];
        if (isset ($_GET["ID"])) $strTarget .= "&ID=". $_GET["ID"];
        $strTarget .= "&Execute";
        
        $Layout->OpenForm ($strLegend, $strTarget);
    }//OpenElementForm
    
    abstract protected function EditElement   ();
    abstract protected function ExistsElement ();
    abstract protected function ListElements  ();
    abstract protected function NewElement    ();
    abstract protected function ShowElement   ();
    
    protected $m_DB;
    protected $m_ErrorSQL;
    protected $m_Time;
}//HSElement
