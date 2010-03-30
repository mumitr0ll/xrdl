<?php

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
		$serviceXml = "<service name=\"$this->name\" ns=\"$this->ns\" url=\"$this->url\">\n%s\n%s</service>\n";
		foreach ($this->entries as $class => $methods) {
			foreach ($methods as $method) {
				$rm = new ReflectionMethod($class, $method);
				$methodComment = $rm->getDocComment();
				$xmlRpcMethod = $this->parseDocComment($methodComment);
				$xmlRpcMethod->name = $method;
				$exportedTypes = array_merge($exportedTypes, $xmlRpcMethod->paramTypes);
				$exportedTypes[] = $xmlRpcMethod->returnType;
				$xmlBuffer .= $xmlRpcMethod->generateXRDL();
			}
		}
		$methodsXml = sprintf($methodsXml, $xmlBuffer);
		$xmlBuffer = "";
		$exportedTypes = array_unique($exportedTypes);
		// All exported types are in $exportedTypes
		// Iterate over types
		foreach ($exportedTypes as $exportedType) {
			if (!$this->isNativeXmlRpcType($exportedType)) {
				$xmlBuffer .= $this->generateXRDLForType($exportedType);
			}
		}

		$typesXml = sprintf($typesXml, $xmlBuffer);
		$serviceXml = sprintf($serviceXml, $typesXml, $methodsXml);
		print $serviceXml;
	}

	private function generateXRDLForType($type) {
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
				$types[] = $type;
				$method->paramTypes[] = $type;
			} else if (strpos($line, "@return")===0) {
				list ($t, $type, $description) = explode(" ", $line, 3);
				$types[] = $type;
				$method->returnType = $type;
			} else {
				// Unknown tag in line, probably OK, so ignore
			}
		}	
		return $method;
	}
}


?>
