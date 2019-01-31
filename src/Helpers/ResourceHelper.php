<?php

namespace Rhubarb\RestApi\Helpers;

class ResourceHelper
{
    public static function removeInvalidProperties($allowedProperties, $payload)
    {
        if (!isset($allowedProperties)) {
            return $payload;
        }

        foreach ($payload as $key => $value) {
            if (self::isNotAllowedProperty($key, $allowedProperties)) {
                unset($payload[$key]);
            }
        }

        return $payload;
    }

    private static function isNotAllowedProperty(string $property, array $allowedProperties)
    {
        return !in_array($property, $allowedProperties);
    }
}