<?
//Loginformular
//Bei falschen Logindaten wird auf andere Adresse weitergeleitet

include ("Settings.php");
include ("Functions.php");
include ("Classes/Redirection.php");
include ("Classes/Time.php");

$ErrorPage = new HSRedirection (LOGOUT_URL, false);

session_start ();

if (isset ($_SESSION["ID"]) && $_SESSION["ID"] != GUEST_ID)
{
    session_unset ();
    $ErrorPage->Execute ();
    return;
}

if (isset ($_POST["Name"], $_POST["Password"]) == false)
{
    include ("Classes/Layout.php");
    
    $Layout = new HSLayout ("Administrator-Zugang");
    $Layout->OpenSite ();
    $Layout->LinkRel ("icon", "Templates/Bouncer/Icon.ico", "image/x-icon"); //Bei Template-Wechsel das hier ändern!
    $Layout->OpenBody ();
    $Layout->Headline ("Login-Interface", 1);
    $Layout->OpenForm ("Logindaten eingeben", "Login.php");
    $Layout->Input ("Name", "text");
    $Layout->Input ("Passwort", "password", "", "Password");
    $Layout->Input ("Senden", "submit");
    $Layout->CloseForm ();
    $Layout->CloseSite ();
    return;
}

if ($_POST["Name"] == "" || $_POST["Password"] == "")
{
    $ErrorPage->Execute ();
    return;
}

$DB = @new mysqli (SQL_HOST, SQL_USR, SQL_PW, SQL_DB);

if (mysqli_connect_errno () ) die ("Verbindung zur Datenbank fehlgeschlagen");

$Stmt = $DB->prepare ("SELECT `ID`, `LastLogin` FROM `Users` WHERE `Name`=? AND `Password`=? AND `Barred`=0;");
if ($Stmt == false)
{
    $ErrorPage->Execute ();
    return;
}
$_POST["Password"] = sha1 ($_POST["Password"]);
$Stmt->bind_param ("ss", $_POST["Name"], $_POST["Password"]);
if ($Stmt->execute () == false)
{
    $ErrorPage->Execute ();
    return;
}
$Stmt->bind_result ($nUserID, $nUserLastLogin);
if ($Stmt->fetch () == false)
{
    $ErrorPage->Execute ();
    return;
}
$Stmt->close ();

$Stmt = $DB->prepare ("UPDATE `Users` SET `LastLogin`=? WHERE `ID`=?;");
if ($Stmt == false)
{
    $ErrorPage->Execute ();
    return;
}
$nTime = time ();
$Stmt->bind_param ("ii", $nTime, $nUserID);
if ($Stmt->execute () == false)
{
    $ErrorPage->Execute ();
    return;
}
$Stmt->close ();

$_SESSION["ID"] = $nUserID;
$_SESSION["LastLogin"] = $nUserLastLogin;

new HSRedirection ("./");

?>
