<?php

namespace Protechstudio\PrestashopWebService;

use PrestaShopWebservice as PSLibrary;
use SimpleXMLElement;

class PrestashopWebService extends PSLibrary
{

    /**
     * Retrieve the resource schema
     *
     * @param $resource
     * @return SimpleXMLElement
     * @throws \PrestaShopWebserviceException
     */
    public function getSchema($resource)
    {
        return $this->get(['resource' => $resource . '?schema=blank']);
    }

    /**
     * Fill the provided schema with an associative array data
     *
     * @param SimpleXMLElement $xmlSchema
     * @param array $data
     * @return SimpleXMLElement
     */
    public function fillSchema(SimpleXMLElement $xmlSchema, $data)
    {
        $resource = $xmlSchema->children()->children();
        foreach ($data as $key => $value) {
            $this->processNode($resource, $key, $value);
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
}