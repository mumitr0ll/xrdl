<?php


class ReturnType {
	var $member1;
	var $member2;
}

class ParamType {
	var $member1;
	var $member2;
}

class ReflectionTest {
	/**
	 * @return ReturnType result of computation
	 * @param string Some parameter
	 * @param ParamType Some other parameter
	 */
	public function testMethod() {
		return "flaf";
	}
}

class XmlRpcMethod {
	var $returnType;
	var $name;
	var $paramTypes;

	function __construct() {
		$this->paramTypes = array();
	}
}

$xmlRpcTypes = array("i4", "string", "base64", "int", "bool", "float");

$r = new ReflectionMethod("ReflectionTest", "testMethod");
var_dump($r->getDocComment());
parseDocComment($r->getDocComment(), "testMethod");
var_dump($types);
var_dump($methods);

foreach ($types as $type) {
	if (!in_array($type, $xmlRpcTypes)) {
		print "$type is not native to XML-RPC. We need to build a struct\n";
		if (!class_exists($type)) {
			print "Unknown type $type used in XML-RPC method\n";
			continue;
		} else {
			// Enumerate members and look up their types
			// recursively. This of course requires that 
			// all complex types have their members appropriately documented
		}
	}
}

$types = array();
$methods = array();

function parseDocComment($comment, $methodName) {
	global $types;
	global $methods;
	$method = new XmlRpcMethod();
	$method->name = $methodName;
	$lines = explode("\n", $comment);
	foreach ($lines as $line) {
		$line = trim($line);
		if (strpos($line, "/**")===0) continue;
		if (strpos($line, "*/")===0) continue;
		$line = ($line[0] == "*" ? trim(substr($line, 1)) : $line);
		if (strpos($line, "@param")===0) {
			list ($t, $type, $description) = explode(" ", $line, 3);
			print "Param type $type\n";
			$types[] = $type;
			$method->paramTypes[] = $type;
		} else if (strpos($line, "@return")===0) {
			list ($t, $type, $description) = explode(" ", $line, 3);
			$types[] = $type;
			print "Return type $type\n";
			$method->returnType = $type;
		} else {
			print "Unknown tag in line $line\n";
		}
	}	
	$methods[] = $method;
}


?>

