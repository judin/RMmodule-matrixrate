<?php
/**
 * MatrixRate Postcode Priority Extension
 *
 * Ensures more specific postcode patterns take priority over less specific ones
 * when using the WebShopApps MatrixRate module.
 */

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'WebShopApps_MatrixRatePostcodePriority',
    __DIR__
);
