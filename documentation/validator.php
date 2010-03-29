<?php
$xdoc = new DomDocument;
$xmlfile = '../examples/service.xml';
$xmlschema = './xrdl.xsd';
//Load the xml document in the DOMDocument object
$xdoc->Load($xmlfile);
//Validate the XML file against the schema
if ($xdoc->schemaValidate($xmlschema)) {
print "$xmlfile is valid.\n";
} else {
print "$xmlfile is invalid.\n";
}
?>
