<?php
/*
 * TODO:
 * - Use statically typed method results, instead of QVariant based ones
 */

$document = new DomDocument();
if (!is_readable("../../examples/service.xml")) {
	print "No such file\n";
	exit(1);
}

if ($document->load("../../examples/service.xml")===FALSE) {
	print "Failed to load service definition\n";
	exit(1);
}

$typeNodes = $document->getElementsByTagName("type");
$methodNodes = $document->getElementsByTagName("method");
$serviceNode = $document->documentElement;

$serviceName = $serviceNode->attributes->getNamedItem("name")->value;
$serviceUrl = $serviceNode->attributes->getNamedItem("url")->value;
$serviceNS = $serviceNode->attributes->getNamedItem("ns")->value;

print "/*\n";
print "Service name: " . $serviceName . "\n";
print "Service URL: " . $serviceUrl . "\n";
print "Service NS: " . $serviceNS . "\n";
print "Found " . $typeNodes->length . " type definitions\n";
print "Found " . $methodNodes->length . " method definitions\n";
print "*/\n\n";

$methods = "";
$methodBodies = "";

$clientHeaderCodeTemplate = <<<EOT
#ifndef XMLRPCCLIENT_H
#define XMLRPCCLIENT_H

#define XML_RPC_URL %URL%
#define XML_RPC_NS %NAMESPACE%
#define XML_RPC_SERVICE_NAME %SERVICE_NAME%

#include <QObject>
#include "maiaXmlRpcClient.h"

class XmlRpcClient : public QObject {
	private:
		MaiaXmlRpcClient rpcClient;
	public:
%METHODS%
};

#endif

EOT;

$clientBodyCodeTemplate = <<<EOT
#include "xmlrpcclient.h"

%METHODBODIES%

EOT;

for ($i=0;$i<$methodNodes->length;$i++) {
	$outputMethodDeclaration = "\t\tint ";
	$outputMethodDefinition = "int XmlRpcClient::";
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
	$outputMethodDeclaration .= "const char *responseSlot, QObject *faultObj, const char* faultSlot);";
	$outputMethodDefinition .= "const char *responseSlot, QObject *faultObj, const char* faultSlot)";
	$outputMethodDefinition .= str_replace("%ADDPARAMS%", $addParamsCode, $definitionTemplate);
	$methods .= $outputMethodDeclaration;
	$methodBodies .= $outputMethodDefinition;
}

$clientHeaderCode = str_replace("%METHODS%", $methods, $clientHeaderCodeTemplate);
$clientHeaderCode = str_replace("%URL%", $serviceUrl, $clientHeaderCode);
$clientHeaderCode = str_replace("%NAMESPACE%", $serviceNS, $clientHeaderCode);
$clientHeaderCode = str_replace("%SERVICE_NAME%", $serviceName, $clientHeaderCode);

$clientBodyCode = str_replace("%METHODBODIES%", $methodBodies, $clientBodyCodeTemplate);

print $clientHeaderCode;
print "\n";
print $clientBodyCode;
print "\n";

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

?>
