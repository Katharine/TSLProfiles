<?php
// VERSION 1.1

// Encode a native PHP object into an LLSD representation
function llsd_encode(&$node)
{
	$doc = new DOMDocument("1.0");
	$llsd_element = $doc->createElement("llsd");
	$doc->appendChild($llsd_element);
	$llsd_element->appendChild(llsd_encode_node($doc, $node));
	return $doc->saveXML();
}


// Takes in an llsd document, returns a native PHP object
function llsd_decode($str)
{
	// error_log($str);
	// Generate the DOM tree from the text document
	$error = array();
	$dom = new DOMDocument();
	if (!$dom = DOMDocument::loadXML($str))
	{
		// Probably should generate something with errors, but just bail for now
		return NULL;
	}

	$dom_root = $dom->documentElement;
	if (!$dom_root)
	{
		return NULL;
	}

    $child = $dom_root;
	// Iterate through all of the children looking for
	// the LLSD node.
	// $child = $dom_root->firstChild;
	while ($child)
	{

		// Now fill in cur_key or cur_value depending on the node
		if ('llsd' == $child->tagName)
		{
			// We've found the root node of the LLSD tree, now have at it.
			return llsd_parse_root($child);
		}
		$child = $child->nextSibling;
	}

	// No valid LLSD node, return an empty object.
	return array();
}


//
// Implementation details below here, you shouldn't need to use any of this functionality
//


// Generates the DOM tree for a particular branch
function llsd_encode_node(&$doc, &$node)
{
	if (is_array($node))
	{
		// Figure out if it's a map or array,
		// Assume anything with sequential integer keys starting at 0 is an array
		$keys = array_keys($node);
		$is_array = true;
		$cur_key = 0;
		foreach ($keys as $key)
		{
			if (is_int($key))
			{
				if (!$cur_key == $key)
				{
					$is_array = false;
					break;
				}
				$cur_key++;
			}
			else
			{
				$is_array = false;
				break;
			}
		}
		if ($is_array)
		{
			return llsd_encode_array($doc, $node);
		}
		else
		{
			return llsd_encode_map($doc, $node);
		}
	}
	else if (is_int($node))
	{
		return llsd_encode_integer($doc, $node);
	}
	else if (is_float($node))
	{
		return llsd_encode_real($doc, $node);
	}
	else if(is_bool($node))
	{
		return llsd_encode_bool($doc, $node);
	}
	else
	{
		// Default to string for everything else
		return llsd_encode_string($doc, $node);
	}
}

function llsd_encode_array(&$doc, &$node)
{
	$map_element = $doc->createElement("array");
	
	$count = count($node);
	for ($i = 0; $i < $count; $i++)
	{
		$value_element = llsd_encode_node($doc, $node[$i]);
		$map_element->appendChild($value_element);
	}
	return $map_element;
}

function llsd_encode_map(&$doc, &$node)
{
	$map_element = $doc->createElement("map");
	
	foreach ($node as $key => $value)
	{
		$key_element = $doc->createElement("key");
		$key_text = $doc->createTextNode(utf8_encode($key));
		$key_element->appendChild($key_text);

		$value_element = llsd_encode_node($doc, $value);

		$map_element->appendChild($key_element);
		$map_element->appendChild($value_element);
	}
	return $map_element;
}

function llsd_encode_integer(&$doc, &$node)
{
	$element = $doc->createElement("integer");
	$text = $doc->createTextNode(utf8_encode($node));
	$element->appendChild($text);
	return $element;
}

function llsd_encode_real(&$doc, &$node)
{
	$element = $doc->createElement("real");
	$text = $doc->createTextNode(utf8_encode($node));
	$element->appendChild($text);
	return $element;
}

function llsd_encode_bool(&$doc, &$node)
{
	$element = $doc->createElement("boolean");
	$text = $doc->createTextNode($node?'1':'0');
	$element->appendChild($text);
	return $element;
}

function llsd_encode_string(&$doc, &$node)
{
	$element = false;
	if(strlen($node) == 36 && strlen(preg_replace('/[^a-fA-F0-9]/','',$node)) == 32 && strlen(preg_replace('/[^-]/','',$node)) == 4)
	{
		$element = $doc->createElement("uuid");
	}
	else
	{
		$element = $doc->createElement("string");
	}
	$text = $doc->createTextNode(utf8_encode($node));
	$element->appendChild($text);
	return $element;
}



function llsd_parse_root($node)
{
	// Root can have only one "value".  Skip all text fields.
	// FIXME: We ignore "extra" values in the root node if there are more than one.
	// should we be more forceful and error out here?

	// Iterate through all of the children looking for
	// an xml element node
	$child = $node->getElementsByTagName('*');
	$child = $child->item(0);
	while ($child)
	{
		switch ($child->nodeType)
		{
		case XML_TEXT_NODE:
			// Skip text
			// FIXME: Should only skip whitespace, should error out on non-whitespace?
			break;
		case XML_ELEMENT_NODE:
			// Now fill in cur_key or cur_value depending on the node
			return llsd_parse_value($child);
			break;
		default:
			break;
			}
		$child = $child->nextSibling;
	}

	// No LLSD node found, return an empty array
	return array();
}

function llsd_parse_value($node)
{
	$cur_value = "";

	switch ($node->nodeType)
	{
	case XML_ELEMENT_NODE:
		switch ($node->nodeName)
		{
		case 'map':
			$cur_value = llsd_parse_map_contents($node);
			break;
		case 'array':
			$cur_value = llsd_parse_array_contents($node);
			break;			
		default:
			// Everything else is handled via the generic handler, which will default to
			// treating it as a string
			// $cur_value = llsd_parse_contents($node->node_name);
			$cur_value = llsd_parse_contents($node, $node->nodeName);
			break;
		}
		break;
	case XML_TEXT_NODE:
		// Skip this, it's not a "value".  Should never happen, the caller should notice this.
		break;
	}
	return $cur_value;
}

//
// Parse the contents of an LLSD map from the DOM branch
//
function llsd_parse_map_contents($branch)
{
	$object = array();
	$objptr = &$object;


	$cur_key = '';
	$cur_value = '';
	// Iterate through all of the children.
	// The children need to alternate between keys and values
	$child = $branch->firstChild;
	while ($child)
	{
		switch ($child->nodeType)
		{
		case XML_TEXT_NODE:
			// FIXME: Should verify that this is whitespace?
			break;
		case XML_ELEMENT_NODE:
			// Now fill in cur_key or cur_value depending on the node
			if ('key' == $child->nodeName)
			{
				$cur_key = llsd_parse_contents($child, 'string');
			}
			else // Switch based on different LLSD types
			{
				$cur_value = llsd_parse_value($child);

				// We've got a key/value pair, add it to the map.
				$object[$cur_key] = $cur_value;
				$cur_value = '';
			}
			break;
		}
		$child = $child->nextSibling;
	}
	return $object;
}

//
// Parse an LLSD array from a DOM branch
//
function llsd_parse_array_contents($branch)
{
	$object = array();
	$objptr = &$object;


	$cur_key = '';
	$cur_value = '';
	// Iterate through all of the children.
	// The children need to alternate between keys and values
	$child = $branch->firstChild;
	while ($child)
	{
		if (true)
		{
			switch ($child->nodeType)
			{
			case XML_TEXT_NODE:
				// FIXME: Should verify that this is whitespace?
				break;
			case XML_ELEMENT_NODE:
				// We've got a key/value pair, add it to the map.
				$object[] = llsd_parse_value($child);
				$cur_value = '';
				break;
			}
		}
		$child = $child->nextSibling;
	}
	return $object;
}

//
// Parse the contents of an LLSD binary value from a DOM branch
//
function llsd_parse_binary_contents($node)
{
	$attribute = $node->attributes->getNamedItem('encoding');
	if(NULL == $attribute || 'base64' == $attribute)
	{
		return base64_decode($child->textContent);
	}
	return NULL;
}

// function llsd_parse_contents($branch, $type)
function llsd_parse_contents($branch, $type)
{
	$child = $branch->firstChild;
	while ($child)
	{
		if (true)
		{
			switch ($child->nodeType)
			{
			case XML_TEXT_NODE:
				switch ($type)
				{
				case 'integer':
					return (int)$child->textContent;
				case 'real':
					return (float)$child->textContent;
				case 'bool':
				case 'boolean':
					if ("true" == $child->textContent || 1 == $child->textContent)
					{
						return true;
					}
					else
					{
						return false;
					}
				case 'binary':
					// FIXME: Can be base16 and base128 - should check this.
					return llsd_parse_binary_contents($node);
				default:
					// Treat everything else as a string
					return $child->textContent;
				}
			}
		}
		$child = $child->nextSibling;
	}
	return false;
}
?>