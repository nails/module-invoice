<?php

/**
 * This class provides some common Invoice controller functionality
 *
 * @package     Nails
 * @subpackage  module-invoice
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Invoice\Controller;

use Nails\Common\Exception\AssetException;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Service\Asset;
use Nails\Factory;
use Nails\Invoice\Constants;

/**
 * Class Base
 *
 * @package Nails\Invoice\Controller
 */
abstract class Base extends \Nails\Common\Controller\Base
{
    /**
     * Loads Invoice styles if the supplied view does not exist
     *
     * @param string $sView The view to test
     *
     * @throws AssetException
     * @throws FactoryException
     */
    protected function loadStyles(string $sView)
    {
        //  Test if the app has provided a view
        if (!is_file($sView)) {
            /** @var Asset $oAsset */
            $oAsset = Factory::service('Asset');
            $oAsset
                ->clear()
                ->load('https://code.jquery.com/jquery-2.2.4.min.js')
                ->load('nails.min.css', \Nails\Common\Constants::MODULE_SLUG)
                ->load('invoice.pay.min.css', Constants::MODULE_SLUG);
        }
    }
}
