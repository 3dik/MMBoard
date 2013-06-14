<?

if (defined ("HS_REDIRECTION")) return 0;
define ("HS_REDIRECTION", "");

//Weiterleitungsklasse, zwei verschiedene Weiterleitungstypen
//- Allgemeine Weiterleitung an eine angegebene URL bzw. Pfad (Target ist ein String)
//- Fehlerweiterleitung an mit ID angegebenen Fehler (Target ist eine Zahl)

//KLassenaufbau
////Public
//
//__construct ($Target, $bInstant=true)   Target gibt Ziel an, ist bInstant true wird die Weiterleitung sofort ausgeführt, andernfalls-> Execute ()
//Execute ()                              Führt Weiterleitung aus, nur ein Aufruf möglich
//Private
//
//m_strPath                               Weiterleitungsziel
////

class HSRedirection
{
    //__construct
    public function __construct ($Target, $bInstant=true)
    {
        if (is_numeric ($Target))
        {
            $this->m_strPath = "Error.php?ID=". $Target;
        }
        else
        {
            $this->m_strPath = $Target;
        }
        
        if ($bInstant) $this->Execute ();
    }//__construct
    
    
    //Execute
    public function Execute ()
    {
        if ($this->m_strPath == "") return;
        
        header("Status: 301 Moved Permanently");
        header("Location: ". $this->m_strPath);
        
        $this->m_strPath = "";
    }//Execute
    
    private $m_strPath;
}//HSRedirection
