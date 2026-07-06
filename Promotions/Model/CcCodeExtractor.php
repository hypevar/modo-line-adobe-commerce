<?php
/**
 * Line_Promotions
 *
 * @author Line https://line.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types=1);

namespace Line\Promotions\Model;

class CcCodeExtractor
{
    /**
     * Extract and flatten all unique ccCode parts from the Line API response
     *
     * Iterates brands and their options, splits each ccCode value by "-",
     * discards null entries, eliminates duplicates, and returns a flat array.
     *
     * @param array<string, mixed> $response
     * @return string[]
     */
    public function extract(array $response): array
    {
        $uniqueCodes = [];

        foreach ($response['brands'] ?? [] as $brand) {
            foreach ($brand['options'] ?? [] as $option) {
                if (empty($option['ccCode'])) {
                    continue;
                }

                foreach (explode('-', $option['ccCode']) as $code) {
                    $uniqueCodes[$code] = true;
                }
            }
        }

        return array_keys($uniqueCodes);
    }
}
