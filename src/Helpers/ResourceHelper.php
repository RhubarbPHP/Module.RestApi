<?php

namespace Rhubarb\RestApi\Helpers;

class ResourceHelper
{
    /**
     * Used to remove properties of the payload that are not allowed
     *
     * @param $allowedProperties
     * @param $payload
     * @return mixed
     */
    public static function removeProperties($allowedProperties, $payload)
    {
        if (!isset($allowedProperties)) {
            return $payload;
        }

        foreach ($payload as $key => $value) {
            if (!in_array($key, $allowedProperties)) {
                unset($payload[$key]);
            }
        }

        return $payload;
    }
}