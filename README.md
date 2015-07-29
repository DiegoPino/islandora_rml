# Islandora RML

This islandora module gets triples from xml datastreams e.g. MODS and stores these triples in RELS-EXT. Triples consist of the URI of the fedoraObject as subject, a generated predicate and a generated object. 

###RML based derivation specification###
islandora_rml uses a triple derivation specification loosely based on [RDF Mapping Language (RML)](http://rml.io/) 
The specification is a JSON object, example:

```json
{
  "triplesMap": [
    {
      "name": "mods_simple",
      "contentModel": "islandora:sp_pdf",
      "dataStream": "MODS",
      "nameSpaces": [
        {
          "ns": "mods",
          "uri": "http://www.loc.gov/mods/v3"
        }
      ],
      "iterator": "/mods:mods",
      "predicateObjectMap": [
        {
          "description": "",
          "predicateMap": {
            "xpath": "mods:relatedItem/@type",
            "ruleType": "reference",
            "nameSpace": "tudrepo",
            "nameSpaceURI": "http://www.library.tudelft.nl/ns/repo"
          },
          "objectMap": {
            "xpath": "info:fedora/{mods:relatedItem/mods:identifier}",
            "ruleType": "template",
            "literal": false,
            "nameSpace": "fedora",
            "nameSpaceURI": "info:fedora/fedora-system:def/relations-external#"
          }
        }
      ]
    }
  ]
}
```
The object has one member `triplesMap`, consisting of an array of triple maps.
Each triple map has the following members:

key | explanation
--- | -----------
`name` | not processed
`contentModel` | this triple map is only processed on fedora objects having this content model
`collection` | (optional) an array of collections: if this array is filled with at least one collection name then the triple map is only processed on members of these collections
`dataStream` | the triple map is processed on this datastream, must be XML
`nameSpaces` | an array of objects consisting of the namespace abbreviation and the namespace URI; these namespaces are used in the XML datastream
`iterator` | an xPath expression used in the processing for the generation an array of subelements of the XML datastream
`predicateObjectMap` | an array of rules for extracting triples, each rule is applied to each subelement defined by the iterator

Each `predicateObjectMap` has two members, one for the predicate and one for the object. 

key | key | explanation
--- | --- | -----------
`description` | | not processed
`predicateMap` | | defines how to extract the predicate
 | `xpath` | a string (constant) or a "real" xPath or a string with xPath's between { and }
 | `ruleType` | is one of: `constant`, `reference` or `template`
 | `nameSpace` | the namespace abrreviation used in the triple
 | `nameSpaceURI` | the namespace abrreviation used in the triple
`objectMap` | | defines how to extract the object
 | `xpath` | a string (constant) or a "real" xPath or a string with xPath's between { and }
 | `ruleType` | is one of: `constant`, `reference` or `template`
 | `literal` | boolean, defaults to `false`, indication whether the object is a literal
 | `nameSpace` | the namespace abrreviation used in the triple
 | `nameSpaceURI` | the namespace abrreviation used in the triple

If `ruleType` is `constant` then the content of `xpath` is simply copied in the triple.
If `ruleType` is `reference` then the content of `xpath` is evaluated against each subelement generated by the iterator. The triple(s) consists of value(s) found.
If `ruleType` is `template` then the template is evaluated and each xPath found is evaluated and inserted in the template.

Example of `constant` and `template`:
```json
        {
          "description": "extracts a hasName predicate with a literal object in which first and last name are combined",
          "predicateMap": {
            "xpath": "hasName",
            "ruleType": "constant",
            "nameSpace": "some name",
            "nameSpaceURI": "some URI"
          },
          "objectMap": {
            "xpath": "{mods:namePart[@type='given']} {mods:namePart[@type='family']}",
            "ruleType": "template",
            "literal": true,
            "nameSpace": "",
            "nameSpaceURI": ""
          }
        }
```

Example of `reference` and `template`:
```json
        {
          "description": "gets related items from Mods using type and identifier",
          "predicateMap": {
            "xpath": "mods:relatedItem/@type",
            "ruleType": "reference",
            "nameSpace": "mods",
            "nameSpaceURI": "mods URI"
          },
          "objectMap": {
            "xpath": "info:fedora/{mods:relatedItem/mods:identifier}",
            "ruleType": "template",
            "literal": false,
            "nameSpace": "fedora",
            "nameSpaceURI": "info:fedora/fedora-system:def/relations-external#"
          }
        },
```

###Specification schema###
The JSON schema is provided as `includes/RMLSchema.json`. This schema can be used with the [JSON Editor](https://github.com/jdorn/json-editor). See https://github.com/fritsvanlatum/rml_form for an application of the JSON Editor for this schema. 