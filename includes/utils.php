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



function jsonCheck() {
	switch (json_last_error()) {
		case JSON_ERROR_NONE:
			$error = 'OK';
		break;
		case JSON_ERROR_DEPTH:
			$error = 'Maximum stack depth exceeded';
		break;
		case JSON_ERROR_STATE_MISMATCH:
			$error = 'State mismatch';
		break;
		case JSON_ERROR_CTRL_CHAR:
			$error = 'Unexpected control character found';
		break;
		case JSON_ERROR_SYNTAX:
			$error = 'Syntax error, malformed JSON';
		break;
		case JSON_ERROR_UTF8:
			$error = 'Malformed UTF-8 characters, possibly incorrectly encoded';
		break;
		default:
			$error = 'Unknown error';
		break;
	}
	return $error;
}

?>