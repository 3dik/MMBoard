<?
//Funktionen, Klassen und Einstellungen sind hier gespeichert

session_start ();

include ("Settings.php");

include ("Functions.php");

include ("Classes/Element.php");
include ("Classes/Error.php");
include ("Classes/Layout.php");
include ("Classes/Redirection.php");
include ("Classes/Time.php");

foreach ($_REQUEST as &$Value) $Value = (string) $Value;

$DB = @new mysqli (SQL_HOST, SQL_USR, SQL_PW, SQL_DB);

if (mysqli_connect_errno () ) die ("Verbindung zur Datenbank fehlgeschlagen");

$Stmt = $DB->prepare ("SET NAMES utf8;");
if ($Stmt == false) die ("SQL-Fehler - Zeichenkodierung");
if ($Stmt->execute () == false) die ("SQL-Fehler - Zeichenkodierung");
$Stmt->close ();

if (isset ($_SESSION["ID"]) == false) $_SESSION["ID"] = GUEST_ID;

if (isset ($_SESSION["IP"]) == false)
{
    $_SESSION["IP"] = $_SERVER["REMOTE_ADDR"];
}
else if ($_SESSION["IP"] != $_SERVER["REMOTE_ADDR"])
{
    foreach ($_SESSION as &$Value) $Value = "";
    $_SESSION["ID"] = GUEST_ID;
    $_SESSION["IP"] = $_SERVER["REMOTE_ADDR"];
}

if (IsRight ($DB, "ViewSite") == false)
{
    new HSRedirection (LOGOUT_URL);
    return;
}

$strLockStatement = GetConfig ($DB, "Locked");
if ($strLockStatement != "")
{
    if (IsRight ($DB, "VisitLockedSite") == false)
    {
        $Layout = new HSLayout ("Sperrmodus");
        $Layout->OpenSite ();
        $Layout->OpenBody ();
        $Layout->Paragraph ("Die Website wurde vorübergehend gesperrt.");
        $Layout->OpenFieldset ("Statement");
        $Layout->Textblock ($strLockStatement, HS_LAYOUT_TEXT);
        $Layout->CloseFieldset ();
        $Layout->CloseSite ();
        exit ();
    }
}
unset ($strLockStatement);

?>
