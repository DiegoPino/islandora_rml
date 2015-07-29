<?php

require_once 'RML.php';

//$test = new RML('{"triplesMap": [{"name": "name","contentModel": "content model","dataStream": "datastream","nameSpace": [{"ns":"a","uri":"namespace 1"},{"ns":"b","uri":"namespace 2"}],"collection": ["collection"],"iterator": "iterator","predicateObjectMap": [{"predicateMap": {"xpath": "predicate xpath 1","ruleType": "reference"},"objectMap": {"xpath": "object xpath 1","ruleType": "reference"}},{"predicateMap": {"xpath": "predicate xpath 2","ruleType": "reference"},"objectMap": {"xpath": "object xpath 2","ruleType": "reference"}}]},{"name": "x","contentModel": "x","dataStream": "x","nameSpace": [{"ns":"x","uri":"y"}],"collection": ["x"],"iterator": "x","predicateObjectMap": [{"predicateMap": {"xpath": "x","ruleType": "reference"},"objectMap": {"xpath": "x","ruleType": "reference"}},{"predicateMap": {"xpath": "y","ruleType": "reference"},"objectMap": {"xpath": "y","ruleType": "reference"}}]}]}');
//echo json_encode($test,JSON_PRETTY_PRINT);

$jsonString = file_get_contents('test.json');
$test = new RML($jsonString);
echo json_encode($test,JSON_PRETTY_PRINT);
