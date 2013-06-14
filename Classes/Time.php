<?

if (defined ("HS_TIME")) return 0;
define ("HS_TIME", "");

//Zeitobjekt

//Klassenaufbau
////Public
//
//__construct  ($nTimestamp=0)        Ist Timestamp nicht angegeben wird die aktuelle Zeit genutzt
//Get          ($strFormat="",        Gibt Zeit in angegebenem Format zurück, wenn keines angegeben ist wird das deutsche Format genutzt
//              $nTimestamp=0)        Wenn angegeben, ersetzt im Konstruktor angegeben Timestamp
//
//s_nDifference                       Zeitverschiebung (statisch)
//
////Private
//
//SetTimestamp ($nTimestamp)          Setzt Zeitpunkt
//
//m_nTimestamp                        Unixtimestamp
////

class HSTime
{
    //__construct
    public function __construct ($nTimestamp=0)
    {
        if (isset (HSTime::$s_nDifference) == false) HSTime::$s_nDifference = 0;
        
        $this->SetTimestamp ($nTimestamp);
    }//__construct
    
    
    //Get
    public function Get ($strFormat="", $nTimestamp=0)
    {
        if ($nTimestamp != 0) $this->SetTimestamp ($nTimestamp);
        
        if ($strFormat == "")
        {
            switch (date ("n", $this->m_nTimestamp))
            {
                case (1):
                    $strMonth = "\\J\\a\\n\\u\\a\\r";
                    break;
                case (2):
                    $strMonth = "\\F\\e\\b\\r\\u\\a\\r";
                    break;
                case (3):
                    $strMonth = "\\M\\ä\\r\\z";
                    break;
                case (4):
                    $strMonth = "\\A\\p\\r\\i\\l";
                    break;
                case (5):
                    $strMonth = "\\M\\a\\i";
                    break;
                case (6):
                    $strMonth = "\\J\\u\\n\\i";
                    break;
                case (7):
                    $strMonth = "\\J\\u\\l\\i";
                    break;
                case (8):
                    $strMonth = "\\A\u\\g\\u\\s\\t";
                    break;
                case (9):
                    $strMonth = "\\S\\e\\p\\t\\e\\m\\b\\e\\r";
                    break;
                case (10):
                    $strMonth = "\\O\\k\\t\\o\\b\\e\\r";
                    break;
                case (11):
                    $strMonth = "\\N\\o\\v\\e\\m\\b\\e\\r";
                    break;
                case (12):
                    $strMonth = "\\D\\e\\z\\e\\m\\b\\e\\r";
                    break;
                default:
                    $strMonth = "\U\\n\\b\\e\\k\\a\\n\\n\\t\e\\r \\M\o\\n\a\\t";
            }
            $strFormat = "j\. ". $strMonth. " Y H\:i\:s";
        }
        
        return date ($strFormat, $this->m_nTimestamp);
    }//Get
    
    
    //SetTimestamp
    public function SetTimestamp ($nTimestamp)
    {
        if ($nTimestamp == 0) $nTimestamp = time ();
        
        $this->m_nTimestamp = $nTimestamp;
    }//SetTimestamp
    
    private $m_nTimestamp;
    private static $s_nDifference;
}//HSTime
