<?php

require_once "XRDLGenerator.class.php";
require_once "Args.class.php";
require_once "exports.php";

$a = new Args();
$ns = $a->flag("ns");
$name = $a->flag("name");
$url = $a->flag("url");

if ($ns==FALSE || $name==FALSE || $url==FALSE) {
	printHelp();
}


$generator = new XRDLGenerator($url, $ns, $name);
foreach ($exports as $class => $methods) {
	foreach ($methods as $method) {
		$generator->addMethod($class, $method);
	}
}
$generator->generate();

function printHelp() {
	die("Usage: php ./xrdl-generator.php --url <service URL> --ns <service namespace> --name <service name>\n");
}

?>

