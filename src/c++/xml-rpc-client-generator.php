<?php
/*
 * TODO:
 * - Use statically typed method results, instead of QVariant based ones
 */

ini_set("include_path", ini_get("include_path") . ":../lib/");

require_once "Args.class.php";

$a = new Args();
$file = $a->flag("file");
$outputDir = $a->flag("dir");
$prefix = $a->flag("prefix");

if ($file===FALSE) {
	printHelp();
}

if ($outputDir===FALSE) {
	printHelp();
}

if ($prefix===FALSE) {
	printHelp();
}

$document = new DomDocument();
if (!is_readable($file)) {
	print "$file can not be read\n";
	exit(1);
}

if ($document->load($file)===FALSE) {
	print "Failed to load service definition\n";
	exit(1);
}

$typeNodes = $document->getElementsByTagName("type");
$methodNodes = $document->getElementsByTagName("method");
$serviceNode = $document->documentElement;

$serviceName = $serviceNode->attributes->getNamedItem("name")->value;
$serviceUrl = $serviceNode->attributes->getNamedItem("url")->value;
$serviceNS = $serviceNode->attributes->getNamedItem("ns")->value;

$methods = "";
$methodBodies = "";

$clientHeaderCodeTemplate = <<<EOT
/* 
 * Automatically generated header file.
 * Any changes will be overridden
 */
#ifndef %UPPERCASE_PREFIX%XMLRPCCLIENT_H
#define %UPPERCASE_PREFIX%XMLRPCCLIENT_H

#define %UPPERCASE_PREFIX%_XML_RPC_URL "%URL%"
#define %UPPERCASE_PREFIX%_XML_RPC_NS "%NAMESPACE%"
#define %UPPERCASE_PREFIX%_XML_RPC_SERVICE_NAME "%SERVICE_NAME%"

#include <QObject>
#include "maiaXmlRpcClient.h"

class %PREFIX%XmlRpcClient : public QObject {
	private:
		MaiaXmlRpcClient rpcClient;
	public:
		%PREFIX%XmlRpcClient(QObject *parent=0);
%METHODS%
};

#endif

EOT;

$clientBodyCodeTemplate = <<<EOT
#include "%LOWERCASE_PREFIX%xmlrpcclient.h"

%PREFIX%XmlRpcClient::%PREFIX%XmlRpcClient(QObject *parent) : QObject(parent) {
	rpcClient.setUrl(QUrl("%URL%"));
}

%METHODBODIES%

EOT;

for ($i=0;$i<$methodNodes->length;$i++) {
	$outputMethodDeclaration = "\t\tint ";
	$outputMethodDefinition = "int " . $prefix . "XmlRpcClient::";
	$methodNode = $methodNodes->item($i);
	$rawMethodName = $methodNode->attributes->getNamedItem("name")->value;
	$methodName = str_replace(".", "_", $methodNode->attributes->getNamedItem("name")->value);
	$outputMethodDeclaration .= $methodName . "(";
	$outputMethodDefinition .= $methodName . "(";
	$paramNodes = $methodNode->childNodes;
	$addParamsCode = "";
	for ($j=0;$j<$paramNodes->length;$j++) {
		$paramNode = $paramNodes->item($j);
		if ($paramNode->nodeName=="param") {
			$paramType = $paramNode->attributes->getNamedItem("type")->value;
			$paramName = $paramNode->textContent;
			$outputMethodDeclaration .= xmlRpcTypeToMaiaType($paramType) . " " . $paramName . ", ";
			$outputMethodDefinition .= xmlRpcTypeToMaiaType($paramType) . " " . $paramName . ", ";
			$addParamsCode .= "\targs << $paramName;\n";
		} else {
			continue;
		}
	}
	$definitionTemplate = "{\n\tQVariantList args;\n\tQString method = \"$rawMethodName\";\n%ADDPARAMS%\n\treturn rpcClient.call(method, args, responseObj, responseSlot, faultObj, faultSlot);\n}\n";


EOT;
	$outputMethodDeclaration .= "QObject *responseObj, const char *responseSlot, QObject *faultObj, const char* faultSlot);";
	$outputMethodDefinition .= "QObject *responseObj, const char *responseSlot, QObject *faultObj, const char* faultSlot)";
	$outputMethodDefinition .= str_replace("%ADDPARAMS%", $addParamsCode, $definitionTemplate);
	$methods .= $outputMethodDeclaration;
	$methodBodies .= $outputMethodDefinition;
}

$clientHeaderCode = str_replace("%METHODS%", $methods, $clientHeaderCodeTemplate);
$clientHeaderCode = str_replace("%URL%", $serviceUrl, $clientHeaderCode);
$clientHeaderCode = str_replace("%NAMESPACE%", $serviceNS, $clientHeaderCode);
$clientHeaderCode = str_replace("%SERVICE_NAME%", $serviceName, $clientHeaderCode);
$clientHeaderCode = str_replace("%UPPERCASE_PREFIX%", strtoupper($prefix), $clientHeaderCode);
$clientHeaderCode = str_replace("%PREFIX%", $prefix, $clientHeaderCode);

$clientBodyCode = str_replace("%METHODBODIES%", $methodBodies, $clientBodyCodeTemplate);
$clientBodyCode = str_replace("%LOWERCASE_PREFIX%", strtolower($prefix), $clientBodyCode);
$clientBodyCode = str_replace("%PREFIX%", $prefix, $clientBodyCode);
$clientBodyCode = str_replace("%URL%", $serviceUrl, $clientBodyCode);

$outputHeaderFilePath = $outputDir . "/" . strtolower($prefix) . "xmlrpcclient.h";
$outputSourceFilePath = $outputDir . "/" . strtolower($prefix) . "xmlrpcclient.cpp";

if (file_put_contents($outputHeaderFilePath, $clientHeaderCode)!==FALSE) {
	print "Wrote class declaration to $outputHeaderFilePath\n";
} else  {
	print "Failed to write $outputHeaderFilePath\n";
}

if (file_put_contents($outputSourceFilePath, $clientBodyCode)!==FALSE) {
	print "Wrote class definition to $outputSourceFilePath\n";
} else {
	print "Failed to write $outputSourceFilePath\n";
}

function xmlRpcTypeToMaiaType($xmlRpcType) {
	switch ($xmlRpcType) {
	case "string":
		return "QString";
	case "base64":
		return "QByteArray";
	case "datetime.iso8601":
		return "QDateTime";
	case "struct":
		return "QVariantMap";
	case "array":
		return "QVariantList";
	case "int":
		return "int";
	case "double":
		return "double";
	case "bool":
		return "bool";
	default:
		// All custom types are handled as a QVariantMap
		return "QVariantMap";
	}
}

function printHelp() {
	die("Usage:  php ./xml-rpc-client-generator.php --file <path to XRDL file> --dir <path to output dir> --prefix <prefix to prepend to class and constant names\n");
}

?>
