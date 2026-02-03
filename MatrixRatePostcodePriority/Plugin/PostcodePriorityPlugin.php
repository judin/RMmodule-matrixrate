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
     * For each shipping method, only the rate with the most specific postcode
     * pattern is kept. Less specific matches (like *) are filtered out.
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

        // Group rates by shipping method and keep only the most specific postcode match
        $ratesByMethod = [];

        foreach ($result as $rate) {
            $method = $rate['shipping_method'] ?? '';
            $specificity = $this->calculatePostcodeSpecificity($rate['dest_zip'] ?? '*');

            if (!isset($ratesByMethod[$method])) {
                $ratesByMethod[$method] = [
                    'rate' => $rate,
                    'specificity' => $specificity
                ];
            } elseif ($specificity > $ratesByMethod[$method]['specificity']) {
                // More specific postcode pattern found - replace
                $ratesByMethod[$method] = [
                    'rate' => $rate,
                    'specificity' => $specificity
                ];
            }
        }

        // Extract just the rates
        $filteredRates = [];
        foreach ($ratesByMethod as $data) {
            $filteredRates[] = $data['rate'];
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
     * - "N%" = 1 (one specific character)
     * - "NP%" = 2 (two specific characters)
     * - "NP10%" = 4 (four specific characters)
     * - "NP10 1AA" = 108 (exact match, highest specificity)
     *
     * @param string $postcodePattern
     * @return int
     */
    private function calculatePostcodeSpecificity(string $postcodePattern): int
    {
        if ($postcodePattern === '*') {
            return 0;
        }

        $specificChars = 0;
        $patternLength = strlen($postcodePattern);

        for ($i = 0; $i < $patternLength; $i++) {
            $char = $postcodePattern[$i];

            if ($char === '%' || $char === '_') {
                break;
            }

            $specificChars++;
        }

        $hasWildcard = strpos($postcodePattern, '%') !== false || strpos($postcodePattern, '_') !== false;

        if (!$hasWildcard) {
            $specificChars += 100;
        }

        return $specificChars;
    }
}
