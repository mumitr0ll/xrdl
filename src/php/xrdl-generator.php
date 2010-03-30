<?php


class ReturnType {
	/**
	 * @type int
	 */
	var $member1;
	/**
	 * @type float
	 */
	var $member2;
}

class ParamType {
	/**
	 * @type string
	 */
	var $member1;
	/**
	 * @type ParamType
	 */
	var $member2;
}

class ReflectionTest {
	/**
	 * @return ReturnType result of computation
	 * @param string Some parameter
	 * @param ParamType Some other parameter
	 * @param ParamType Some duplicate parameter
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

	function generateXRDL() {
		$result = "<method name=\"$this->name\" result=\"$this->returnType\">\n";
		$index = 0;
		foreach ($this->paramTypes as $paramType) {
			$result .= "<param type=\"$paramType\">arg$index</param>\n";
			$index++;
		}
		$result .= "</method>\n";
		return $result;
	}
}

class XRDLGenerator {
	private $entries; // map from class name to list of methods in class
	private $url;
	private $ns;
	private $name;

	public function __construct($url, $ns, $name) {
		$this->entries = array();
		$this->url = $url;
		$this->ns = $ns;
		$this->name = $name;
	}
	/**
	 * @param string
	 * @param string
	 */
	public function addMethod($class, $method) {
		if (!array_key_exists($class, $this->entries)) {
			$this->entries[$class] = array();
		} 
		$this->entries[$class][] = $method;
	}

	public function generate() {
		// Iterate over exported methods
		$exportedTypes = array();
		$xmlBuffer = "";
		$methodsXml = "<methods>\n%s</methods>\n";
		$typesXml = "<types>\n%s</types>\n";
		$serviceXml = "<service name=\"\" ns=\"\" url=\"\">\n%s\n%s</service>\n";
		foreach ($this->entries as $class => $methods) {
			foreach ($methods as $method) {
				$rm = new ReflectionMethod($class, $method);
				$methodComment = $rm->getDocComment();
				$xmlRpcMethod = $this->parseDocComment($methodComment);
				$xmlRpcMethod->name = $method;
				var_dump($xmlRpcMethod);
				$exportedTypes = array_merge($exportedTypes, $xmlRpcMethod->paramTypes);
				$exportedTypes[] = $xmlRpcMethod->returnType;
				$xmlBuffer .= $xmlRpcMethod->generateXRDL();
			}
		}
		print $xmlBuffer;
		$xmlBuffer = "";
		$exportedTypes = array_unique($exportedTypes);
		var_dump($exportedTypes);
		// All exported types are in $exportedTypes
		// Iterate over types
		foreach ($exportedTypes as $exportedType) {
			if (!$this->isNativeXmlRpcType($exportedType)) {
				$xmlBuffer .= $this->generateXRDLForType($exportedType);
			}
		}
		print $xmlBuffer;
	}

	private function generateXRDLForType($type) {
		print "Generating XRDL for type $type\n";
		$result = "<type name=\"$type\">\n";
		if (!class_exists($type)) {
			print "Unable to generate XRDL definition for unknown type $type";
			return;
		} else {
			$rc = new ReflectionClass($type);
			foreach ($rc->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
				$name = $property->getName();
				$comment = $property->getDocComment();
				if (!$comment) {
					print "No documentation associated with member $name in type $type. Skipping member.\n";
					continue;
				}
				$type = $this->parseMemberDocComment($comment);
				if ($type!==FALSE) {
					$result .= "<member type=\"$type\">$name</member>\n";
				} else {
					print "No @type tag in documentation associated with member $name in type $type. Skipping member.\n";
					continue;
				}

			}
		}

		$result .= "</type>\n";
		return $result;
	}

	private function isNativeXmlRpcType($type) {
		return in_array(
			$type, 
			array("i4", "string", "base64", "bool", "struct", "array", "float"));
	}

	private function parseMemberDocComment($comment) {
		$lines = explode("\n", $comment);
		var_dump($lines);
		foreach ($lines as $line) {
			if (strpos($line, "@type")!==FALSE) {
				list($unused, $type) = explode("@type", $line, 2);
				$type = trim($type);
				return $type;
			}
		}
		return FALSE;
	}


	/**
	 * @param string comment
	 * @return XmlRpcMethod representation of method associated with comment
	 */
	private function parseDocComment($comment) {
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
		return $method;
	}
}

$generator = new XRDLGenerator("some.url", "some.ns", "some.name");
$generator->addMethod("ReflectionTest", "testMethod");
$generator->generate();


/*
$xmlRpcTypes = array("i4", "string", "base64", "int", "bool", "float");

$r = new ReflectionMethod("ReflectionTest", "testMethod");
var_dump($r->getDocComment());
parseDocComment($r->getDocComment(), "testMethod");
var_dump($types);
var_dump($methods);
$xrdlTypes = array(); // Map from type name to XRDL definition

foreach ($types as $type) {
	if (!in_array($type, $xmlRpcTypes)) {
		print "$type is not native to XML-RPC. We need to build a struct\n";
		if (!class_exists($type)) {
			print "Unknown type $type used in XML-RPC method\n";
			continue;
		} else {
			typeToXrdl($type);
			// Enumerate members and look up their types
			// recursively. This of course requires that 
			// all complex types have their members appropriately documented
		}
	}
}

$types = array();
$methods = array();

function typeToXrdl($typeName) {
	print "Generating XRDL for type $typeName\n";
	if (!class_exists($typeName)) {
		print "Unable to generate XRDL definition for unknown type $typeName";
		return;
	} else {
		$rc = new ReflectionClass($typeName);
		foreach ($rc->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
			$name = $property->getName();
			$comment = $property->getDocComment();
			if (!$comment) {
				print "No documentation associated with member $name in type $typeName. Skipping property.\n";
				continue;
			}
		}
	}
}

function parseDocComment($comment, $methodName) {
	global $types;
	global $methods;
	$method = new XmlRpcMethod();
	$method->name = $methodName;
	$lines = explode("\n", $comment);
	foreach ($lines as $line) {
		$line = trim($line);
		if (strpos($line, "/**")===0) continue;
		if (strpos($line, "")===0) continue;
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
*/

?>

