<?php

declare(strict_types=1);

namespace Pristavu\Anaf\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use InvalidArgumentException;

/** @codeCoverageIgnore  */
class XmlToArray
{
    public static function convert(string $xml, bool $outputRoot = false): array
    {
        $array = self::xmlStringToArray($xml);
        if (! $outputRoot && array_key_exists('@root', $array)) {
            unset($array['@root']);
        }

        return $array;
    }

    protected static function xmlStringToArray(string $xmlString): array
    {
        $previous = libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = false;
        $loaded = $doc->loadXML(
            $xmlString,
            LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NOBLANKS
        );

        if ($loaded === false || ! $doc->documentElement instanceof DOMElement) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            $message = isset($errors[0]) ? mb_trim($errors[0]->message) : 'Invalid XML provided';
            throw new InvalidArgumentException($message);
        }

        $root = $doc->documentElement;
        $output = self::domNodeToArray($root);
        // Use localName to be namespace-agnostic
        $output['@root'] = $root->localName ?? $root->tagName;

        libxml_use_internal_errors($previous);

        return $output;
    }

    protected static function domNodeToArray(DOMNode $node): string|array
    {
        $output = [];
        switch ($node->nodeType) {
            case XML_CDATA_SECTION_NODE:
            case XML_TEXT_NODE:
                $output = mb_trim($node->textContent);
                break;
            case XML_ELEMENT_NODE:
                for ($i = 0, $m = $node->childNodes->length; $i < $m; $i++) {
                    $child = $node->childNodes->item($i);
                    $v = self::domNodeToArray($child);
                    if ($child instanceof DOMElement) {
                        // Use localName to avoid leaking namespace prefixes into keys
                        $t = $child->localName ?? $child->tagName;
                        if (! isset($output[$t])) {
                            $output[$t] = [];
                        }
                        $output[$t][] = $v;
                    } elseif ($v || $v === '0') {
                        $output = (string) $v;
                    }
                }
                if ($node->attributes->length && ! is_array($output)) { // Has attributes but isn't an array
                    $output = ['@content' => $output]; // Change output into an array.
                }
                if (is_array($output)) {
                    if ($node->attributes->length) {
                        $a = [];
                        foreach ($node->attributes as $attrName => $attrNode) {
                            $a[$attrName] = (string) $attrNode->value;
                        }
                        $output['@attributes'] = $a;
                    }
                    foreach ($output as $t => $v) {
                        if (is_array($v) && count($v) === 1 && $t !== '@attributes') {
                            $output[$t] = $v[0];
                        }
                    }
                }
                break;
        }

        return $output;
    }
}
