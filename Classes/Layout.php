<?

if (defined ("HS_LAYOUT")) return 0;
define ("HS_LAYOUT", "");

//Klasse für XHTML-Ausgabe
//Es wird XHTML 1.0 Strict genutzt, der passende Mimetyp wird automatisch anhand des Browsers gewählt (Internet Exploder!!)

//Nutzung
////
//Damit die meisten PHP-Fehler abgefangen werden können musss am Anfang jeder PHP-Datei, die diese Klasse nutzt,
//die Funktion ob_start aufgerufen werden, sodass "vorzeitige" Ausgaben gebuffert werden können. Daher dürfen
//auch keine Ausgaben direkt gesendet werden (zB. über "echo").
//Es werden zwei verschiedene Anzeigemodi unterstützt. Wenn im Konstruktor kein Template-Pfad angegeben wurde,
//müssen alle nötigen XHTML-Codes über die jeweiligen Methoden selbst aufgerufen werden. Wenn Templates genutzt
//werden, muss man nur einmal ein "Grundlayout" erstellen, das dann bei jeder Unterseite geladen wird.

//Templates
////
//Das grundlegende Layout lässt sich beliebig verändern. Die Template-Dateien können beliebige PHP-Seiten sein,
//die auch die übergebene Datenbank nutzen können.
//Die inkludierte Template-Datei muss "Template.php" heißen und im angegebenen Ordner liegen. Falls CSS benutzt
//werden soll muss im ersten Parameter der Methode OpenSite ($bCSS=false) true übergeben werden. Die CSS-Datei
//muss dann "Style.css" heißen und ebenfalls im selben Ordner liegen. 
//Da die Templates direkt von der Klasse aufgerufen werden, haben diese auch Zugriff auf den internen Methoden
//und Variablen. Bis auf folgende Ausnahmen empfehle ich, auf den internen Teil der Klasse nicht zuzugreifen:
//- Um den "richtigen" Inhalt anzuzeigen muss die Methode ShowContent () aufgerufen werden
//- Bei einem Fehler, der zB. aufgrund einer beschädigten Datenbank nicht über eine "normale" Fehlermeldung
//  angezeigt werden kann, empfehle ich die Funktion Exception ($strReport) aufzurufen. Beim Aufruf dieser
//  Funktion wird der Ausgabe-Buffer gelöscht und durch eine spezielle Ausnahmefehlerseite ersetzt, die auch bei
//  einer fehlerhaften Datenbank funktioniert.
////

//Klassenaufbau
////Public
//  
//__construct    ($strTitle,                   Titel und Überschrift der Seite
//                $strTemplateName="",         Ordnername des Templates, nichts angeben falls nicht erforderlich
//                &$DB=false)                  Datenbank kann übergeben werden, Fehler fall Template DB benötigt und diese ungültig ist
//__destruct     ()                            Layout wird zusammengesetzt und gesendet
//Cell           ($strText, $bHeader=false)    Erstellt Tabellenzelle
//CloseDiv       ()                            Schliesst Div-Tag
//CloseFieldset  ()                            Schliesst Fieldset-Tag
//CloseForm      ()                            Schliesst ein Formular
//CloseList      ()                            Schliesst Ul-Tag
//CloseListitem  ()                            Schliesst Li-Tag
//CloseRow       ()                            Schliesst Tr-Tag
//CloseSite      ()                            Schliesst Body- und HTML-Tag
//CloseTable     ()                            Schliesst Table-Tag
//Headline       ($strLine, $nRank)            Erstellt Überschrift, zweiter Parameter bestimmt Überschriftenebene
//Hidden         ($strVarName, $strValue="")   Erstellt unsichtbares Input
//Input          ($strLabel,                   Erstellt ein Eingabefeld,
//                $strType,                    Eingabetyp
//                $strValue="",                Standardwert
//                $strVarName=""               Variablenname
//Line           ($strClass="")                Erstellt eine horizontale Linie
//LinkRel        ($strRel, $strRef, $strType)  Erstellt Link-Rel-Tag
//Listitem       ($strText)                    Erstellt Item einer Liste
//Metatag        ($strName, $strValue)         Erstellt Metatag
//OpenBody       ()                            Öffnet Body-Tag
//OpenDiv        ($strClass)                   Öffnet Div-Tag
//OpenFieldset   ($strLegend, $strClass="")    Öffnet Fieldset-Tag
//OpenForm       ($strLegend, $strTarget)      Erstellt Formular, zweiter Parameter bestimmt den Zielpfad des Formulars
//OpenList       ($strClass="")                Öffnet Ul-Tag
//OpenListitem   ($strClass="")                Öffnet Li-Tag
//OpenRow        ()                            Öffnet Tr-Tag
//OpenSite       ($bCSS==false)                Erstellt Headerzeilen, Parameter gibt an ob CSS genutzt wird
//OpenTable      ($strClass="")                Öffnet Table-Tag
//Out            ($strCode,                    Fügt XHTML-Code in den Buffer ein
//                $bReturn=true,               Zeilenumbruch am Ende?
//                $bIndenation=true)           Übergebene Zeilenumbrüche löschen?
//Paragraph      ($strText, $strClass="")      Erstellt Textabsatz
//TextBlock       ($strText, $nMode)           Konvertiert Zeilenumbrüche und Einrückung in angegebenes Format
//Title          ($strText)                    Erstellt den Titel
//
////Private
//
//Clear           ()                          Beide Ausgaben-Buffer werden geleert
//Exception       ($strReport)                Ausnahmefehler, falls zB. Verbindung zur Datenbank fehlschlägt
//GetClassCode    ($strClass)                 Gibt XHTML-Code für die angegebene Klasse zurück
//GetTemplatePath ()                          Gibt internen Pfad zum Template zurück
//Indent          ($strText)                  Rückt übergebenen Text
//ShowContent     ()                          Gibt "richtigen" Inhalt aus (Siehe oben)
//
//m_DB;                                       Referenz auf die übergebene Datenbank
//m_bFirstBuffer                              Ersten Buffer aktiviert? (ansonsten zweiter?)
//m_bIndentContent                            Soll Inhalt der Seite auch eingerückt werden? Bei Formularen zB. ist dies nicht der Fall
//m_bNewLine                                  Letztes Zeichen ein Zeilenumbruch?
//m_bTitle                                    Wurde der Titel bereits gesendet?
//m_nTabs                                     Anzahl der momentanen Tabs
//m_strExceptionError                         Ausnahmefehlertext
//m_strOut                                    Die XHTML-Ausgabe, die später versendet wird
//m_strTab                                    Tabzeichen
//m_strTemplateName                           Name des Templates
//m_strTitle                                  Titel und Überschrift der Website
////

define ("HS_LAYOUT_LINE", 1);
define ("HS_LAYOUT_TEXT", 2);
define ("HS_LAYOUT_FORMTEXT", 3);

ob_start ();

class HSLayout
{
    //__construct
    public function __construct ($strTitle, $strTemplateName="", &$DB=false)
    {
        $this->m_DB                = &$DB;
        $this->m_bFirstBuffer      = true;
        $this->m_bIndentContent    = true;
        $this->m_bNewLine          = true;
        $this->m_bTitle            = false;
        $this->m_nTabs             = 0;
        $this->m_strExceptionError = "";
        $this->m_strOut1           = "";
        $this->m_strOut2           = "";
        $this->m_strTab            = "  ";
        $this->m_strTemplateName   = $strTemplateName;
        $this->m_strTitle          = $strTitle;
        $this->Clear ();
    }//__construct


    //__destruct
    public function __destruct ()
    {
        if ($this->m_strTemplateName != "")
        {
            $this->m_bFirstBuffer = false;
            $strTemplatePath = $this->GetTemplatePath (). "Template.php";
            if (file_exists ($strTemplatePath))
            {
                include ($strTemplatePath);
            }
            else
            {
                $this->Exception ("Die Template-Datei konnte nicht aufgerufen werden: " . PATH);
            }
            
            if ($this->m_strExceptionError != "")
            {
                $this->Clear ();
                    
                $this->m_bFirstBuffer = false;
                
                $this->OpenSite ();
                $this->OpenBody ();
                $this->Headline ("Ausnahmefehler:", 1);
                $this->Paragraph ($this->m_strExceptionError);
                $this->CloseSite ();
            }
        }
        else
        {
            $this->m_strOut2 = &$this->m_strOut1;
        }
        
        if (isset ($_SERVER["HTTP_ACCEPT"]) && stristr($_SERVER["HTTP_ACCEPT"], "application/xhtml+xml"))
        {
            header ("Content-type: application/xhtml+xml");
        }
        else
        {
            header ("Content-type: text/html");
        }
        
        echo $this->m_strOut2;

        ob_end_flush ();
    }//__destruct
    
    
    //Cell
    public function Cell ($strText, $bHeader=false)
    {
        if ($strText == "") return;
        
        if ($bHeader)
        {
            $this->Out ("<th>". $strText. "</th>");
        }
        else
        {
            $this->Out ("<td>". $strText. "</td>");
        }
    }//Cell
    
    
    //CloseDiv
    public function CloseDiv ()
    {
        $this->m_nTabs --;
        $this->Out ("</div>");
    }//CloseDiv
    
    
    //CloseFieldset
    public function CloseFieldset ()
    {
        $this->m_nTabs --;
        $this->Out ("</fieldset>");
    }//CloseFieldset
    
    
    //CloseForm
    public function CloseForm ()
    {
        $this->CloseFieldset ();
        $this->m_nTabs --;
        $this->Out ("</form>");
    }//CloseForm
    
    
    //CloseList
    public function CloseList ()
    {
        $this->m_nTabs --;
        $this->Out ("</ul>");
    }//CloseList
    
    
    //CloseListitem
    public function CloseListitem ()
    {
        $this->m_nTabs --;
        $this->Out ("</li>");
    }//CloseListitem
    
    
    //CloseRow
    public function CloseRow ()
    {
        $this->m_nTabs --;
        $this->Out ("</tr>");
    }//CloseRow
    
    
    //CloseSite
    public function CloseSite ()
    {
        $this->m_nTabs --;
        $this->Out ("</body>");
        $this->m_nTabs --;
        $this->Out ("</html>");
    }//CloseSite
    
    
    //CloseTable
    public function CloseTable ()
    {
        $this->m_nTabs --;
        $this->Out ("</table>");
    }//CloseTable
    
    
    //Headline
    public function Headline ($strLine, $nRank)
    {
        if ($strLine == "") return;
        
        $nRank = intval ($nRank);
        $this->Out ("<h". $nRank. ">". $strLine. "</h". $nRank. ">");
    }//Headline
    
    
    //Hidden
    public function Hidden ($strVarName, $strValue="")
    {
        $strCode = "<input type=\"hidden\" name=\"". $strVarName. "\" ";
        if ($strValue != "") $strCode .= "value=\"". $strValue. "\" ";
        $strCode .= "/>\n";
        $this->Textblock ($strCode, HS_LAYOUT_LINE);
    }//Hidden
    
    
    //Input
    public function Input ($strLabel, $strType, $strValue="", $strVarName="")
    {
        if ($strLabel == "" || $strType == "") return;
        
        if ($strVarName == "") $strVarName = $strLabel;
        
        $this->Out ("<p>");
        $this->m_nTabs ++;
        $this->Out ("<label for=\"". $strVarName. "\">". $strLabel. "</label>");
        
        switch ($strType)
        {
            case ("text"):
            case ("password"):
                $strCode = "<input id=\"". $strVarName. "\" type=\"". $strType. "\" name=\"". $strVarName. "\" ";
                if ($strValue != "") $strCode .= "value=\"". $strValue. "\" ";
                $strCode .= "/>";
                $this->TextBlock ($strCode, HS_LAYOUT_LINE);
                unset ($strCode);
                break;
            case ("textarea"):
                $this->TextBlock ("<textarea id=\"". $strVarName. "\" name=\"". $strVarName. "\" cols=\"40\" rows=\"10\">". $strValue. "</textarea>", HS_LAYOUT_FORMTEXT);
                if ($strValue != "") $this->m_bIndentContent = false; //Um Fehler durch zusätzliche Leerzeichen in Formularen zu verhindern
			    break;
			case ("bool"):
			    $this->Out ("<select id=\"". $strVarName. "\" name=\"". $strVarName. "\">");
			    $this->m_nTabs ++;
			    $this->Out ("<option", false);
			    if ($strValue) $this->Out (" selected=\"selected\"", false);
			    $this->Out (">Ja</option>");
			    $this->Out ("<option", false);
			    if ($strValue == false) $this->Out (" selected=\"selected\"", false);
			    $this->Out (">Nein</option>");
			    $this->m_nTabs --;
			    $this->Out ("</select>");
			    break;
            case ("submit"):
                $this->Out ("<input id=\"". $strVarName. "\" type=\"submit\" value=\"Senden\" />");
                break;
            default:
                $this->Out ("Ubekannter Eingabetyp");
        }
        
        $this->m_nTabs --;
        $this->Out ("</p>");
    }//Input
    
    
    //Line
    public function Line ($strClass="")
    {
        $this->Out ("<hr". $this->GetClassCode ($strClass). " />");
    }//Line
    
    
    //LinkRel
    public function LinkRel ($strRel, $strRef, $strType)
    {
        if ($strRel == "" || $strRef == "" || $strType == "") return;
        
        $strTemplate = $this->m_strTemplateName;
        if ($strTemplate != "") $strTemplate = "Templates/$strTemplate/";
        $this->Out ("<link rel=\"$strRel\" href=\"$strTemplate". $strRef. "\" type=\"$strType\" />");
    }//LinkRel
    
    
    //Listitem
    public function Listitem ($strText)
    {
        if ($strText == "") return;
        $this->Out ("<li>". $strText. "</li>");
    }//Listitem
    
    
    //Metatag
    public function Metatag ($strName, $strValue)
    {
        if ($strName == "" || $strValue == "") return;
        
        $this->Out ("<meta http-equiv=\"". $strName. "\" content=\"". $strValue. "\" />");
    }//Metatag
    
    
    //OpenBody
    public function OpenBody ()
    {
        $this->Title ($this->m_strTitle); //Wird nicht gesendet, wenn ein Titel bereits vorhanden ist
        $this->m_nTabs --;
        $this->Out ("</head>");
        $this->Out ("<body>");
        $this->m_nTabs ++;
    }//OpenBody
    
    
    //OpenDiv
    public function OpenDiv ($strClass)
    {
        $this->Out ("<div". $this->GetClassCode ($strClass). ">");
        $this->m_nTabs ++;
    }//OpenDiv
    
    
    //OpenFieldset
    public function OpenFieldset ($strLegend, $strClass="")
    {
        if ($strLegend == "") return;
        
        $this->Out ("<fieldset". $this->GetClassCode ($strClass). ">");
        $this->m_nTabs ++;
        $this->Out ("<legend>". $strLegend. "</legend>");
    }//OpenFieldset
    
    
    //OpenForm
    public function OpenForm ($strLegend, $strTarget)
    {
        $this->Out ("<form method=\"post\" action=\"". $strTarget. "\">");
        $this->m_nTabs ++;
        $this->OpenFieldset ($strLegend);
    }//OpenForm
    
    
    //OpenList
    public function OpenList ($strClass="")
    {
        $this->Out ("<ul". $this->GetClassCode ($strClass). ">");
        $this->m_nTabs ++;
    }//OpenList
    
    
    //OpenListitem
    public function OpenListitem ($strClass="")
    {
        $this->Out ("<li". $this->GetClassCode ($strClass). ">");
        $this->m_nTabs ++;
    }//OpenListitem
    
    
    //OpenRow
    public function OpenRow ()
    {
        $this->Out ("<tr>");
        $this->m_nTabs ++;
    }//OpenRow
    
    
    //OpenSite
    public function OpenSite ($bCSS=false)
    {
        $this->Out ("<?xml version=\"1.0\" encoding=\"UTF-8\" ?>");
        $this->Out ("<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">");
        $this->Out ("<html xmlns=\"http://www.w3.org/1999/xhtml\">");
        $this->m_nTabs ++;
        $this->Out ("<head>");
        $this->m_nTabs ++;
        if ($bCSS) $this->LinkRel ("stylesheet", "Style.css", "text/css");
        //if ($bCSS) $this->Out ("<link rel=\"stylesheet\" href=\"Templates/". $this->m_strTemplateName. "/Style.css\" type=\"text/css\" />");
    }//OpenSite
    
    
    //OpenTable
    public function OpenTable ($strClass="")
    {
        $this->Out ("<table>");
        $this->m_nTabs ++;
    }//OpenTable


    //Out
    public function Out ($strCode, $bReturn=true, $bIndentation=true)
    {
        if ($strCode == false) return;
        
        if ($this->m_bFirstBuffer)
        {
            $pstrBuffer = &$this->m_strOut1;
        }
        else
        {
            $pstrBuffer = &$this->m_strOut2;
        }

        if ($bReturn) $strCode .= "\n";
        
        $strCode = str_replace ("&", "&amp;", $strCode);
        $strCode = str_replace ("&amp;lt;", "&lt;", $strCode);
        $strCode = str_replace ("&amp;gt;", "&gt;", $strCode);
        $strCode = str_replace ("&amp;quot;", "&quot;", $strCode);
        $strCode = str_replace ("&amp;#039;", "&#039;", $strCode);
        
        
        if ($bIndentation && substr ($pstrBuffer, -1, 1) == "\n") //Text einrücken
        {
            $strCode = $this->Indent ($strCode);
        }
        
        $pstrBuffer .= $strCode;
       $this->m_bNewLine = true;
    }//Out
    
    
    //Paragraph
    public function Paragraph ($strText, $strClass="")
    {
        if ($strText == "") return;
        $this->Textblock ("<p". $this->GetClassCode ($strClass). ">". $strText. "</p>", HS_LAYOUT_LINE);
    }//Paragraph
    
    
    //Textblock
    public function TextBlock ($strText, $nMode)
    {
        if ($strText == false) return;
        if ($nMode == 0) return;
        
        $strReturn    = "";
        $bIndentation = true;
        
        if ($nMode == HS_LAYOUT_TEXT) $strReturn = "<br />\n";
        if ($nMode == HS_LAYOUT_FORMTEXT)
        {
            $strReturn = "\n";
            $bIndentation = false;
        }
        
        $ArrayText = explode ("\n", $strText);
        foreach ($ArrayText as $strLine)
        {
            $strLine = GetClearReturn ($strLine);
            $this->Out ($strLine. $strReturn, false, $bIndentation);
        }
        
        if ($nMode == HS_LAYOUT_LINE) $this->Out ("\n", false);
    }//TextBlock
    
    
    //Title
    public function Title ($strText)
    {
        if ($strText == "" || $this->m_bTitle) return;
        $this->m_bTitle = true;
        $this->Out ("<title>". $strText. "</title>");
    }//Title


    //Clear
    private function Clear ()
    {
        $this->m_nTabs   = 0;
        $this->m_strOut1 = "";
        $this->m_strOut2 = "";
    }//Clear
    

    //Exception
    private function Exception ($strReport)
    {
        if ($strReport == "" || $this->m_strExceptionError != "") return;

        $this->Clear ();
        $this->m_strExceptionError = $strReport;
    }//Exception
    
    
    //GetClassCode
    private function GetClassCode ($strClass)
    {
        if ($strClass == "") return "";
        return " class=\"". $strClass. "\"";
    }//GetClassCode
    
    
    //GetTemplatePath
    private function GetTemplatePath ()
    {
        if ($this->m_strTemplateName == "") return "";
//        return $_SERVER["DOCUMENT_ROOT"]. "/Templates/". $this->m_strTemplateName. "/";
        return PATH . "/Templates/". $this->m_strTemplateName. "/";
    }//GetTemplatePath
    
    
    //Indent
    private function Indent ($strLine)
    {
        if ($strLine == "") return;
        
        for ($i=0; $i != $this->m_nTabs; $i++) $strLine = $this->m_strTab. $strLine;
        
        return $strLine;
    }//Indent
    
    
    //ShowContent
    private function ShowContent ()
    {
        if ($this->m_bFirstBuffer) return;
        
        if ($this->m_bIndentContent)
        {
            $ArrayText = explode ("\n", $this->m_strOut1);
            $this->m_strOut1 = "";
            foreach ($ArrayText as $strLine)
            {
                if ($strLine == "") continue;
                $this->m_strOut1 .= $this->Indent ($strLine). "\n";
            }
        }
        
        $this->m_strOut2 .= $this->m_strOut1;
    }//ShowContent


    private $m_DB;
    private $m_bFirstBuffer;
    private $m_bIndentContent;
    private $m_bNewLine;
    private $m_bTitle;
    private $m_nTabs;
    private $m_strExceptionError;
    private $m_strOut1;
    private $m_strOut2;
    private $m_strTab;
    private $m_strTemplateName;
    private $m_strTitle;
}//HSLayout

?>
