<?php

/**
 * Class to parse DOM-based content.
 */

class DOMParser
{

    /**
     * Parses DOM-based content as XPath by certain locator.
     * 
     * @param string $content DOM-based content as string
     * @param string $encoding string for content encoding value
     * @param string $xpathLocator xpath-syntax based locator string
     * 
     * @return DOMNodeList nodes parsed from DOM
     */
    public static function parseDOMAsXPath($content, $encoding, $xpathLocator)
    {
        if (!$content) {
            return false;
        }

        $DOMDocument = new DOMDocument();
        $DOMDocument->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', $encoding));
        $DOMXPath = new DOMXPath($DOMDocument);
        $DOMNodeList = $DOMXPath->query($xpathLocator);

        return($DOMNodeList);
    }
    
    /**
     * Removes DOM-based node from content as XPath by certain locator.
     * 
     * @param string $content DOM-based content as string
     * @param string $encoding string for content encoding value
     * @param string $xpathLocator xpath-syntax based locator string
     * 
     * @return string HTML-code with changed content
     */
    public static function removeElementByXPath($content, $encoding, $xpathLocator)
    {
        if (!$content) {
            return false;
        }
        $DOMDocument = new DOMDocument();
        $DOMDocument->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', $encoding));
        $DOMXPath = new DOMXpath($DOMDocument);
        $DOMNodeList = $DOMXPath->query($xpathLocator);
        foreach ($DOMNodeList as $DOMNodeListElement) {
            $DOMNodeListElement->parentNode->removeChild($DOMNodeListElement);
        }

        return html_entity_decode($DOMDocument->saveHTML());
    }
    
    /**
     * Gets inner HTML for given DOM node.
     * 
     * @param DOMNode $node DOM node
     * @return string HTML-code for given DOM node
     */
    public static function getNodeInnerHTML($node)
    {
        $innerHTML = '';
        if (!empty($node->childNodes)) {
            foreach ($node->childNodes as $child) {
                $innerHTML .= $child->ownerDocument->saveXML($child);
            }
        }
        return $innerHTML;        
    }

}

?>
