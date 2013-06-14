<?
//Unterer Bereich des Layouts

$this->OpenDiv ("Footer");
$this->Textblock (GetConfig ($this->m_DB, "FooterText"), HS_LAYOUT_TEXT);
$this->CloseDiv ();

return true;

?>
