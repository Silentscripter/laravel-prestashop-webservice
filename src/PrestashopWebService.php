<?php

namespace Protechstudio\PrestashopWebService;

use PrestaShopWebservice as PSLibrary;
use SimpleXMLElement;

class PrestashopWebService extends PrestashopWebServiceLibrary
{

    /**
     * Retrieve the resource schema
     *
     * @param $resource , $schema
     * @return SimpleXMLElement
     * @throws PrestaShopWebserviceException
     */
    public function getSchema($resource, $schema = 'blank')
    {
        return $this->get(['resource' => $resource . "?schema=$schema"]);
    }

    /**
     * Fill the provided schema with an associative array data, also remove the useless XML nodes if
     * the corresponding flag is true
     *
     * @param SimpleXMLElement $xmlSchema
     * @param array $data
     * @param bool $removeUselessNodes set true if you want to remove nodes that are not present in the data array
     * @param array $removeSpecificNodes If $removeUselessNodes is false you may add here the first level nodes that
     *                                   you want to remove
     * @return SimpleXMLElement
     */
    public function fillSchema(
        SimpleXMLElement $xmlSchema,
        $data,
        $removeUselessNodes = true,
        $removeSpecificNodes = array()
    ) {
        $resource = $xmlSchema->children()->children();
        foreach ($data as $key => $value) {
            $this->processNode($resource, $key, $value);
        }
        if ($removeUselessNodes) {
            $this->checkForUselessNodes($resource, $data);
        } else {
            $this->removeSpecificNodes($resource, $removeSpecificNodes);
        }
        return $xmlSchema;
    }

    /**
     * @param string|array $data
     * @param $languageId
     * @return string
     */
    private function getLanguageValue($data, $languageId)
    {
        if (is_string($data)) {
            return $data;
        }

        if (array_key_exists($languageId, $data)) {
            return $data[$languageId];
        } else {
            return $data[1];
        }
    }

    /**
     * @param $node
     * @param $data
     */
    private function fillLanguageNode($node, $data)
    {
        for ($i = 0; $i < count($node->language); $i++) {
            $node->language[$i] = $this->getLanguageValue($data, (int)$node->language[$i]['id']->__toString());
        }
    }

    /**
     * @param SimpleXMLElement $node
     * @param $dataKey
     * @param $dataValue
     */
    private function processNode(SimpleXMLElement $node, $dataKey, $dataValue)
    {
        if (is_int($dataKey)) {
            if ($dataKey===0) {
                $this->emptyNode($node);
            }
            $this->createNode($node, $dataValue);
        } elseif (property_exists($node->$dataKey, 'language')) {
            $this->fillLanguageNode($node->$dataKey, $dataValue);
        } elseif (is_array($dataValue)) {
            foreach ($dataValue as $key => $value) {
                $this->processNode($node->$dataKey, $key, $value);
            }
        } else {
            $node->$dataKey = $dataValue;
        }
    }

    /**
     * Remove XML first level nodes that are not present int the data array
     * @param SimpleXMLElement $resource
     * @param $data
     */
    private function checkForUselessNodes(SimpleXMLElement $resource, $data)
    {
        $uselessNodes = [];
        foreach ($resource as $key => $value) {
            if (!array_key_exists($key, $data)) {
                $uselessNodes[] = $key;
            }
        }
        foreach ($uselessNodes as $key) {
            unset($resource->$key);
        }
    }

    /**
     * Remove the given nodes from the resource
     * @param $resource
     * @param $removeSpecificNodes
     */
    private function removeSpecificNodes($resource, $removeSpecificNodes)
    {
        foreach ($removeSpecificNodes as $node) {
            unset($resource->$node);
        }
    }

    /**
     * @param SimpleXMLElement $node
     * @param array $dataValue
     */
    private function createNode(SimpleXMLElement $node, $dataValue)
    {
        foreach ($dataValue as $key => $value) {
            if (is_array($value)) {
                if (is_int($key)) {
                    $this->createNode($node, $value);
                } else {
                    $childNode=$node->addChild($key);
                    $this->createNode($childNode, $value);
                }
            } else {
                $node->addChild($key, $value);
            }
        }
    }

    /**
     * @param SimpleXMLElement $node
     */
    private function emptyNode(SimpleXMLElement $node)
    {
        $nodeNames = array();
        foreach ($node->children() as $key => $value) {
            $nodeNames[]=$key;
        }
        foreach ($nodeNames as $nodeName) {
            unset($node->$nodeName);
        }
    }
}
