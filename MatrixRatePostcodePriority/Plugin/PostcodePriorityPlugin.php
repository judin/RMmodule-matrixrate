<?php
/**
 * MatrixRate Postcode Priority Extension
 *
 * Ensures more specific postcode patterns take priority over less specific ones
 * when using the WebShopApps MatrixRate module.
 */

declare(strict_types=1);

namespace WebShopApps\MatrixRatePostcodePriority\Plugin;

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
     * After plugin for getRate method to filter results by postcode specificity.
     *
     * Only rates with the most specific postcode pattern are kept.
     * All less specific matches (like *) are filtered out.
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

        // Find the highest specificity among all rates
        $highestSpecificity = 0;
        foreach ($result as $rate) {
            $specificity = $this->calculatePostcodeSpecificity($rate['dest_zip'] ?? '*');
            if ($specificity > $highestSpecificity) {
                $highestSpecificity = $specificity;
            }
        }

        // Only keep rates with the highest specificity
        $filteredRates = [];
        foreach ($result as $rate) {
            $specificity = $this->calculatePostcodeSpecificity($rate['dest_zip'] ?? '*');
            if ($specificity === $highestSpecificity) {
                $filteredRates[] = $rate;
            }
        }

        return $filteredRates;
    }

    /**
     * Calculate the specificity of a postcode pattern.
     *
     * Higher specificity = more specific pattern.
     * Specificity is based on the number of non-wildcard characters.
     *
     * Examples:
     * - "*" = 0 (matches everything)
     * - "%" = 0 (matches everything)
     * - "N%" = 1 (one specific character)
     * - "NP%" = 2 (two specific characters)
     * - "NP10%" = 4 (four specific characters)
     * - "NP10 1AA" = 108 (exact match, highest priority)
     *
     * @param string $postcodePattern
     * @return int
     */
    private function calculatePostcodeSpecificity(string $postcodePattern): int
    {
        $postcodePattern = trim($postcodePattern);

        // Wildcards that match everything = lowest specificity
        if ($postcodePattern === '*' || $postcodePattern === '%' || $postcodePattern === '') {
            return 0;
        }

        $specificChars = 0;
        $patternLength = strlen($postcodePattern);

        for ($i = 0; $i < $patternLength; $i++) {
            $char = $postcodePattern[$i];

            if ($char === '%' || $char === '_' || $char === '*') {
                break;
            }

            $specificChars++;
        }

        // If pattern is ONLY wildcards, specificity is 0
        if ($specificChars === 0) {
            return 0;
        }

        $hasWildcard = strpos($postcodePattern, '%') !== false
            || strpos($postcodePattern, '_') !== false
            || strpos($postcodePattern, '*') !== false;

        if (!$hasWildcard) {
            $specificChars += 100;
        }

        return $specificChars;
    }
}
