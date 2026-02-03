<?php
/**
 * WebShopApps
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * WebShopApps MatrixRate - Postcode Priority Plugin
 *
 * @category WebShopApps
 * @package WebShopApps_MatrixRate
 * @copyright Copyright (c) 2014 Zowta LLC (http://www.WebShopApps.com)
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @author WebShopApps Team sales@webshopapps.com
 */

declare(strict_types=1);

namespace WebShopApps\MatrixRate\Plugin;

use WebShopApps\MatrixRate\Model\ResourceModel\Carrier\Matrixrate;
use Magento\Quote\Model\Quote\Address\RateRequest;

/**
 * Plugin to ensure more specific postcode patterns take priority over less specific ones.
 *
 * When multiple postcode patterns match (e.g., both N% and NP% match postcode "NP10 1AA"),
 * this plugin ensures the more specific pattern (NP%) is prioritized over the less specific one (N%).
 */
class PostcodePriorityPlugin
{
    /**
     * After plugin for getRate method to sort results by postcode specificity.
     *
     * @param Matrixrate $subject
     * @param array $result
     * @param RateRequest $request
     * @param bool $zipRangeSet
     * @return array
     */
    public function afterGetRate(
        Matrixrate $subject,
        array $result,
        RateRequest $request,
        bool $zipRangeSet = false
    ): array {
        if (empty($result) || count($result) <= 1) {
            return $result;
        }

        // Sort by postcode specificity (most specific first)
        usort($result, function ($a, $b) {
            $specificityA = $this->calculatePostcodeSpecificity($a['dest_zip'] ?? '*');
            $specificityB = $this->calculatePostcodeSpecificity($b['dest_zip'] ?? '*');

            // Higher specificity should come first (descending order)
            if ($specificityA !== $specificityB) {
                return $specificityB - $specificityA;
            }

            // If same specificity, maintain original ordering by condition_from_value DESC
            $conditionA = (float)($a['condition_from_value'] ?? 0);
            $conditionB = (float)($b['condition_from_value'] ?? 0);

            return $conditionB <=> $conditionA;
        });

        return $result;
    }

    /**
     * Calculate the specificity of a postcode pattern.
     *
     * Higher specificity = more specific pattern.
     * Specificity is based on the number of non-wildcard characters.
     *
     * Examples:
     * - "*" = 0 (matches everything)
     * - "N%" = 1 (one specific character)
     * - "NP%" = 2 (two specific characters)
     * - "NP10%" = 4 (four specific characters)
     * - "NP10 1AA" = 8 (exact match, highest specificity)
     *
     * @param string $postcodePattern
     * @return int
     */
    private function calculatePostcodeSpecificity(string $postcodePattern): int
    {
        // Wildcard '*' matches everything - lowest specificity
        if ($postcodePattern === '*') {
            return 0;
        }

        // Count characters before the first wildcard (% or _)
        // In SQL LIKE: % matches any sequence, _ matches single character
        $specificChars = 0;
        $patternLength = strlen($postcodePattern);

        for ($i = 0; $i < $patternLength; $i++) {
            $char = $postcodePattern[$i];

            // Stop counting at wildcard characters
            if ($char === '%' || $char === '_') {
                break;
            }

            $specificChars++;
        }

        // If no wildcards found, it's an exact match - give bonus specificity
        $hasWildcard = strpos($postcodePattern, '%') !== false || strpos($postcodePattern, '_') !== false;

        if (!$hasWildcard) {
            // Exact match gets a bonus to ensure it takes priority
            $specificChars += 100;
        }

        return $specificChars;
    }
}
