<?

if (defined ("HS_ERROR")) return 0;
define ("HS_ERROR", "");

include ("Layout.php");

//Fehlerklasse
//Zeigt den Fehler an, der über eine ID angegeben wurde
//Fehler wird direkt im Layout angezeigt, daher darf das Layout selbst keine Fehler senden (Ausnahmefehler)

//Klassenaufbau
////Public
//
//__construct (&$HSLayout, &$DB, $nErrorID))  Konstruktor
//
////Private
//
//Exception ($strReport)                      Sonderfehlermeldung, wenn eigentlicher Fehler fehlschlägt
//
//m_Layout                                    Referenz auf die Layoutklasse
////

class HSError
{
    //__construct
    public function __construct (&$Layout, &$DB, $nErrorID)
    {
        if ($Layout == false || $DB == false)
        {
            $this->Exception ("Ungültige Parameter");
            return;
        }
                
        $this->m_Layout = &$Layout;
        
        $Stmt = $DB->prepare ("SELECT `Report`, `HTTP` FROM `Errors` WHERE `ID`=?;");
        if ($Stmt == false)
        {
            $this->Exception ("SQL-Fehler");
            return;
        }
        $Stmt->bind_param ("i", $nErrorID);
        if ($Stmt->execute () == false)
        {
            $this->Exception ("SQL-Fehler");
            return;
        }
        $Stmt->bind_result ($strErrorReport, $strErrorHTTP);
        if ($Stmt->fetch () == false)
        {
            $this->Exception ("Angegebener Fehler existiert gar nicht");
            return;
        }
        $Stmt->close ();
        
        if ($strErrorHTTP != "") header ("HTTP/1.1 ". $strErrorHTTP);
        
        $this->m_Layout->Paragraph ("Fehlermeldung: ". $strErrorReport);
    }//__construct
    
    
    //Exception
    private function Exception ($strReport)
    {
        if ($strReport == "") return;
        
        $this->m_Layout->Paragraph ("<strong>Fehler im Fehler</strong> (komisch ist aber so): ". $strReport);
    }//Exception
    
    private $m_Layout;
}//HSError
