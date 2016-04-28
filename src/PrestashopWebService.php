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
        $toBeRemoved = array();
        $resource = $xmlSchema->children()->children();
        foreach ($resource as $key => $value) {
            if (array_key_exists($key, $data)) {
                if (property_exists($resource->$key, 'language')) {
                    $this->fillLanguageNode($resource->$key, $data[$key]);
                } else {
                    $resource->$key = $data[$key];
                }
            } else {
                $toBeRemoved[] = $key;
            }
        }
        foreach ($toBeRemoved as $key) {
            unset($resource->$key);
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
}