<?php

/**
 * This class represents arbitrary data objects
 *
 * @package  Nails\Invoice\Resource
 * @category resource
 */

namespace Nails\Invoice\Resource;

use Nails\Common\Resource;

/**
 * Class ArbitraryData
 *
 * @package Nails\Invoice\Resource
 */
abstract class ArbitraryData extends Resource
{
    /**
     * Format the object to JSON
     *
     * @return string
     */
    public function __toString(): string
    {
        return json_encode($this->toSortedArray());
    }

    // --------------------------------------------------------------------------

    /**
     * Converts the object to an multi-dimensional, sorted array
     *
     * @param array|null $aArray
     *
     * @return array
     */
    public function toSortedArray(?array $aArray = null): array
    {
        foreach (($aArray ?? get_object_vars($this)) as $sProp => $mValue) {
            $aArray[$sProp] = is_object($mValue) || is_array($mValue)
                ? static::toSortedArray((array) $mValue)
                : $mValue;
        }

        ksort($aArray);

        return $aArray;
    }
}
