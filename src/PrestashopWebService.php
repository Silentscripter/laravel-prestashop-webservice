<?php

namespace Protechstudio\PrestashopWebService;

use PrestaShopWebservice as PSLibrary;
use SimpleXMLElement;

class PrestashopWebService extends PSLibrary
{

    /**
     * Retrieve the resource schema
     *
     * @param $resource , $schema
     * @return SimpleXMLElement
     * @throws \PrestaShopWebserviceException
     */
    public function getSchema($resource , $schema = 'blank')
    {
        return $this->get(['resource' => $resource . "?schema=$schema"]);
    }

    /**
     * Fill the provided schema with an associative array data, also remove the useless XML nodes if the corresponding flag is true
     *
     * @param SimpleXMLElement $xmlSchema
     * @param array $data
     * @param bool $removeUselessNodes set true if you want to remove nodes that are not present in the data array
     * @return SimpleXMLElement
     */
    public function fillSchema(SimpleXMLElement $xmlSchema, $data, $removeUselessNodes = true)
    {
        $resource = $xmlSchema->children()->children();
        foreach ($data as $key => $value) {
            $this->processNode($resource, $key, $value);
        }
        if ($removeUselessNodes) {
            $this->checkForUselessNode($resource, $data);
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
        if (property_exists($node->$dataKey, 'language')) {
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
    private function checkForUselessNode(SimpleXMLElement $resource, $data)
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
}
