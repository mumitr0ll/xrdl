<?php

require_once "xrdlgenerator.class.php";

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

class XmlRpcServerClass {
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

$generator = new XRDLGenerator("some.url", "some.ns", "some.name");
$generator->addMethod("XmlRpcServerClass", "testMethod");
$generator->generate();


?>

