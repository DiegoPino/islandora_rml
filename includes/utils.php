<?php

function test_xpath($xpath,$namespaces) {
	try {
		$doc = new DOMDocument;
		$xp = new DOMXPath($doc);
		foreach ($namespaces as $n) {
			$xp->registerNamespace($n->ns,$n->uri);
		}
		$entries = $xp->evaluate($xpath);
		if ($entries === false) {
			throw new Exception('Error evaluating xPath.');
		}
		else return true;
	}
	catch(DOMException $domError) {
		return 'DOMException on: "'.$xpath.'" : '.$domError->getMessage();
	}
	catch(Exception $e) {
		return 'Exception on: "'.$xpath.'" : '.$e->getMessage();
	}
}

function recursiveEqual($a, $b) {
	if (is_object($a)) {
		if (!is_object($b)) {
			return FALSE;
		}
		foreach ($a as $key => $value) {
			if (!isset($b->$key)) {
				return FALSE;
			}
			if (!recursiveEqual($value, $b->$key)) {
				return FALSE;
			}
		}
		foreach ($b as $key => $value) {
			if (!isset($a->$key)) {
				return FALSE;
			}
		}
		return TRUE;
	}
	if (is_array($a)) {
		if (!is_array($b)) {
			return FALSE;
		}
		foreach ($a as $key => $value) {
			if (!isset($b[$key])) {
				return FALSE;
			}
			if (!recursiveEqual($value, $b[$key])) {
				return FALSE;
			}
		}
		foreach ($b as $key => $value) {
			if (!isset($a[$key])) {
				return FALSE;
			}
		}
		return TRUE;
	}
	return $a === $b;
}
?>