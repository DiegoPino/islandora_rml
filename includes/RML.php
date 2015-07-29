<?php

/**
* Represents the RML specification.
*
* objects of this class have the following structure:
 *	{
 *		"triplesMap": [
 *			{...},
 *			(...},
 *			
 *			{...}
 *		]
 *	}
*/
class RML {
	public $triplesMap = array();
	public $triples = array();

	/**
	 * Constructs a RML object 
	 * from a json string or a json object
	 * for each element in the triplesMap array an object of the class TriplesMap is generated
	 *
	 * @param object|string $json
	 *   The object rpresenting the specification.
	 *
	 * @return void
	 *  
	 */
	function __construct($json) {
		if (is_string($json) || is_object($json)) {
			$jsonObject = is_string($json) ? json_decode($json) : $json;
			foreach ($jsonObject->triplesMap as $tm) $this->triplesMap[] = new TriplesMap($tm);
		}
	}
	
	/**
	 * Gets the triples from all the TriplesMap objects 
	 *
	 * @param object $fedoraObject
	 *   The $fedoraObject at hand
	 *
	 * @return array
	 *   An array of predicate object combinations
	 */
	function deriveTriples($fedoraObject) {
		$this->triples = array();
		foreach ($this->triplesMap as $tm) {
			foreach ($tm->deriveTriples($fedoraObject) as $newTriple) {
				$this->triples[] = $newTriple;
			}
		}
		return $this->triples;
	}
	
	
	/**
	 * Stores the triples in RELS-EXT 
	 *
	 * @param object $fedoraObject
	 *   The $fedoraObject at hand
	 *
	 * @return void
	 *
	 */
/*
{
        "predicate": {
            "value": "constituent",
            "alias": "tudrepo",
            "namespace": "http:\/\/www.library.tudelft.nl\/ns\/repo"
        },
        "object": {
            "value": "info:fedora\/uuid:eceb984c-1887-4c59-9a7e-cc9d2ebef686",
            "alias": "fedora",
            "namespace": "info:fedora\/fedora-system:def\/relations-external#"
        }
    },
*/

	//this is a still a first version!!!
	function addDerivedTriples($fedoraObject) {
		$newTriples = $this->deriveTriples($fedoraObject);
		$relationships = $fedoraObject->relationships;
		$allExisting = $fedoraObject->relationships->get();
		echo 'autocommit: <pre>'.json_encode($fedoraObject->relationships->autoCommit).'</pre>';
		echo 'count new: <pre>'.count($newTriples).'</pre>';
		echo 'count ex.: <pre>'.count($allExisting).'</pre>';
		
		foreach ($newTriples as $triple) {
			$type = 0;
			if (isset($triple['object']['literal'])) {
				$type = $triple['object']['literal'] ? $type = 1 : 0;
			}

			$existing = $fedoraObject->relationships->get($triple['predicate']['namespace'], $triple['predicate']['value']);
			echo 'existing: <pre>'.json_encode($existing,JSON_PRETTY_PRINT).'</pre>';
			foreach ($existing as $existingTriple) {
				$fedoraObject->relationships->remove($triple['predicate']['namespace'], $triple['predicate']['value'], $triple['object']['value'], $type);
			}
			$fedoraObject->relationships->registerNamespace($triple['predicate']['alias'], $triple['predicate']['namespace']);
			
//			$fedoraObject->relationships->add($triple['predicate']['namespace'], $triple['predicate']['value'], $triple['object']['value'], $type); 
		}
		
	}

}	


/**
* Represents a Triplesmap.
*
* objects of this class have the following structure:
 *	{
 *		"name": "mods_simple",
 *		"contentModel":"islandora:sp_pdf",
 *		"dataStream": "MODS",
 *		"collection": [...,...,...],
 *		"nameSpaces": [{
 *			"ns": "mods",
 *			"uri": "http://www.loc.gov/mods/v3"
 *		}],
 *		"iterator": "/mods:mods",
 *		"predicateObjectMap": [
 *			{...},
 *			{...},
 *
 *			{...}
 *		]
*/

class TriplesMap {
	public $nameSpaces = array();
	public $predicateObjectMap = array();
	public $triples = array();

	/**
	 * Constructs a TriplesMap object 
	 * by simply copying the members of a $jsonObject
	 * with the exception of the predicateObjectMap members of the $jsonObject
	 * these are generated as objects of the class PredicateObjectMap
	 *
	 * @param object $jsonObject
	 *   The object derived from the rml specification
	 *
	 * @return void
	 *  
	 */
	function __construct($jsonObject) {
		foreach ($jsonObject as $k=>$v) {
			if ($k != 'predicateObjectMap') {
				$this->{$k} = $v;
			}
		}
		foreach ($jsonObject->predicateObjectMap as $pom) $this->predicateObjectMap[] = new PredicateObjectMap($pom);
	}

	/**
	* Checks the collections specification.
	*   Returns true when no collections are specified in the triples map
	*   If collections are specified: returns true when the object belongs to one of the collections in the specification, otherwise returns false.
	*
	* @param $fedoraObject
	*
	*
	* @return boolean
	*
	*/
	function checkCollections($fedoraObject) {
		$collections = getCollections($fedoraObject);
		if (isset($this->collection)) {
			if (count($this->collection) > 0) {
				foreach ($this->collection as $col) {
					if (in_array($col,$collections)) return true;
				}
				return false;
			}
			else {
				return true;
			}
		}
		else {
			return true;
		}
	}

	/**
	* Checks the content model specification.
	*   Returns true when the content model of the fedoraObject matches with the triplesmap
	*
	* @param $fedoraObject
	*
	*
	* @return boolean
	*
	*/
	private function checkCModels($fedoraObject) {
		$cmodels = getCModels($fedoraObject);
		if (isset($this->contentModel)) {
			return in_array($this->contentModel,$cmodels);
		}
		else {
			return false;
		}
	}


	/**
	 * Gets the triples from all the PredicateObjectMap objects 
	 *
	 * @param object $fedoraObject
	 *   The $fedoraObject at hand
	 *
	 * @return array
	 *   An array of predicate object combinations
	 */
	function deriveTriples($fedoraObject) {
		$this->triples = array();
		if ($this->checkCModels($fedoraObject) && $this->checkCollections($fedoraObject)) {
			//get the datastream
			$dataStream = $fedoraObject[$this->dataStream];
			if ($dataStream) {
				$xml = new DOMDocument;
				$xml->LoadXML($dataStream->content);

				$xpath = new DOMXPath($xml);
				//namespaces
				foreach ($this->nameSpaces as $ns) {
					$xpath->registerNamespace($ns->ns, $ns->uri);
				}

				$nodeList = $xpath->query($this->iterator);

				for ($i = 0; $i < $nodeList->length; $i++) {
					$context = $nodeList->item($i);
					foreach ($this->predicateObjectMap as $rule) {
						$newTriples = $rule->deriveTriples($xml,$context,$this->nameSpaces);
						foreach ($newTriples as $new) {
							$this->triples[] = $new;
						}
					}
				}
			}
		}
		else {
			//$this->triples[] = 'checks failed';
		}
		return $this->triples;
	}
}


/**
* Represents a PredicateObjectMap.
*
* objects of this class have the following structure:
		{
			"predicateMap": {
				"ruleType":"reference",
				"xpath": "mods:relatedItem/@type",
				"nameSpace": "tudrepo",
				"nameSpaceURI": "http://www.library.tudelft.nl/ns/repo"
			},
			"objectMap": {
				"ruleType":"template",
				"xpath": "info:fedora/{mods:relatedItem/mods:identifier}",
				"literal": false,
				"nameSpace": "fedora",
				"nameSpaceURI": "info:fedora/fedora-system:def/relations-external#"
			}
		},
*/
class PredicateObjectMap {
	public $triples = array();

	/**
	 * Constructs a PredicateObjectMap object 
	 * by simply copying the members of a $jsonObject 
	 *
	 * @param object $jsonObject
	 *   The object derived from the triples map
	 *   see class TriplesMap
	 *
	 * @return void
	 *  
	 */
	function __construct($jsonObject) {
		foreach ($jsonObject as $k=>$v) {
			$this->{$k} = $v;
		}
	}
	
	
	/**
	 * Evaluates a reference 
	 *
	 * @param string $reference
	 *   The reference, an xPath 
	 *
	 * @param string $xml
	 *   The xml to extract the xpaths
	 *
	 * @param string $context
	 *   The context of $xml
	 *
	 * @param array $namespaces
	 *   The namespaces used in the xml
	 *
	 * @return array
	 *   An array of evaluated values
	 */
	private function evalReferenceToList($reference,&$xml,&$context,$namespaces) {
		$results=array();
	
		$refXPath = new DOMXPath($xml);
		foreach ($namespaces as $ns) {
			$refXPath->registerNamespace($ns->ns, $ns->uri);
		}
		$refXPathResults = $refXPath->query($reference,$context);	

		if ($refXPathResults !== false) {
			foreach ($refXPathResults as $r) {
				
/*		//debugging
			echo 'XPathResults: <pre>';
			echo "\n nodeName     :".json_encode($r->nodeName);
			echo "\n nodeValue    :".json_encode($r->nodeValue);
			echo "\n nodeType     :".json_encode($r->nodeType);
			echo "\n nameSpaceURI :".json_encode($r->nameSpaceURI);
			echo "\n prefix       :".json_encode($r->prefix);
			echo "\n localName    :".json_encode($r->localName);
			echo "\n textContent  :".json_encode($r->textContent);
			echo '</pre>';
*/				
				$results[] = $r->textContent;  //textContent or nodeValue
			}
		}
		return $results;
	}

	/**
	 * Evaluates a template. Uses evalReferenceToList for each xPath in the template 
	 *
	 * @param string $template
	 *   The template, something like aaa{xpath}bb{other xpath}cccc...
	 *
	 * @param string $xml
	 *   The xml to extract the xpaths
	 *
	 * @param string $context
	 *   The context of $xml
	 *
	 * @param array $namespaces
	 *   The namespaces used in the xml
	 *
	 * @return array
	 *   An array of evaluated values
	 */
	private function evalTemplateToList($template,&$xml,&$context,$namespaces) {
		$evaTemplate = array();
		$p = strpos($template,'{');
		$q = strpos($template,'}');
		$t = 0;
		while ($p !== false) {
			//we still have an opening {
			//note that $template changes in each loop
			if ($q === false) {
				//if no matching closing } in $template then ignore rest of string
				if ($p > 0) {
					$template = substr($template,0,$p);			
				}
				else {
					$template = '';
				}
			}
			elseif ($q < $p) {
					//mismatch: if closing } before opening { then ignore string up to and including closing }
					$template = substr($template,$q+1);
			}
			else {
				//we have an opening { and later on in the template a closing }
				
				//check prefix before {
				$pre = '';
				if ($p > 0) {
					$pre = substr($template,0,$p);
					$template = substr($template,$p);
					//renew $q
					$q = strpos($template,'}');
				}
	
				//reference between { and }
				$reference = substr($template,1,$q-1);
				$xPathResults = $this->evalReferenceToList($reference,$xml,$context,$namespaces);
				//$evaTemplate[$t][] = "evalTemplateToList: $t: $reference gives $xPathResults->count results"; 
				foreach ($xPathResults as $r) $evaTemplate[$t][] = $pre.$r; 
				
				//renew $template
				$template = substr($template,$q+1);
				$t++;
			}
			//renew $p and $q
			$p = strpos($template,'{');
			$q = strpos($template,'}');
		}
		//add suffix after last { to result
		if (strlen($template) > 0) $evaTemplate[] = array($template);
		
		$result = array();
		//$result[] = 'evalTemplateToList: '.count($evaTemplate);
		if (count($evaTemplate) > 0) {
			$result = $evaTemplate[0];
			$p = 1;
			while ($p < count($evaTemplate)) {
				$newResult = array();
				foreach($result as $r) {
					foreach ($evaTemplate[$p] as $s) {
						$newResult[] = $r.$s;
					}
				}
				$result = $newResult;
				$p++;
			}
		}
		return $result;
	}
	
	/**
	 * Gets the triples from the specified datastream 
	 *
	 * @param string $xml
	 *   The xml to extract the xpaths
	 *
	 * @param string $context
	 *   The context of $xml
	 *
	 * @param array $namespaces
	 *   The namespaces used in the xml
	 *
	 * @return array
	 *   An array of predicate object combinations
	 */
	function deriveTriples($xml,$context,$namespaces) {
		$this->triples = array();

		//predicates
		$predicatesFound = array();
		if ($this->predicateMap->ruleType == 'constant') {
			$predicatesFound[] = $this->predicateMap->xpath;
		}
		elseif ($this->predicateMap->ruleType == 'reference') {
			$list = $this->evalReferenceToList($this->predicateMap->xpath,$xml,$context,$namespaces);
			foreach ($list as $el) $predicatesFound[] = $el;
		}
		elseif ($this->predicateMap->ruleType == 'template') {
			$list = $this->evalTemplateToList($this->predicateMap->xpath,$xml,$context,$namespaces);
			foreach ($list as $el) $predicatesFound[] = $el;
		}
	
		//objects
		$objectsFound = array();
		if ($this->objectMap->ruleType == 'constant') {
			$objectsFound[] = $this->objectMap->xpath;
		}
		elseif ($this->objectMap->ruleType == 'reference') {
			$list = $this->evalReferenceToList($this->objectMap->xpath,$xml,$context,$namespaces);
			foreach ($list as $el) $objectsFound[] = $el;
		}
		elseif ($this->objectMap->ruleType == 'template') {
			$list = $this->evalTemplateToList($this->objectMap->xpath,$xml,$context,$namespaces);
			foreach ($list as $el) $objectsFound[] = $el;
		}

		//$newTriples[] = array($predicatesFound, ' --- ', $objectsFound);
		foreach ($predicatesFound as $p) {
			$predicate = array(
				'value'=>$p,
				'alias'=>$this->predicateMap->nameSpace,
				'namespace'=>$this->predicateMap->nameSpaceURI);
			foreach ($objectsFound as $o) {
				$object = array('value'=>$o);
				if ($this->objectMap->literal) {
					$object['literal'] = true;
				}
				else {
					$object['alias']=$this->objectMap->nameSpace;
					$object['namespace']=$this->objectMap->nameSpaceURI;
				}
				$this->triples[] = array('predicate'=>$predicate,'object'=>$object);
			}
		}
		return $this->triples;
		
	}
}

/**
* Gets the content models of an object from the triple store.
*
* @param object $fedoraObject
*   The object.
*
* @return array
*   An array of conten models.
*/
function getCModels($fedoraObject) {
	$cModels = array();
	if ($fedoraObject){
		$connection = islandora_get_tuque_connection();
		$query = "SELECT ?object FROM <#ri> WHERE { <info:fedora/".
		$fedoraObject->id.
		"> <info:fedora/fedora-system:def/model#hasModel> ?object }";
		$results = $connection->repository->ri->sparqlQuery($query);
		foreach ($results as $result) {
			$cModels[] = $result["object"]["value"];
		}
	}
	return $cModels;
}


/**
* Gets the collections of an object from the triple store.
*
* @param object $fedoraObject
*   The object.
*
* @return array
*   An array of collections.
*/
function getCollections($fedoraObject) {
	$collections = array();
	if ($fedoraObject) {
		$connection = islandora_get_tuque_connection();
		$query = "SELECT ?object FROM <#ri> WHERE { <info:fedora/".$fedoraObject->id."> <info:fedora/fedora-system:def/relations-external#isMemberOfCollection> ?object }";
		$results = $connection->repository->ri->sparqlQuery($query);
		foreach ($results as $result) {
			$collections[] = $result["object"]["value"]; //or "uri": if "uri" then do not add info:fedora
		}
	}
	return $collections;
}
