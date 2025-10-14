<?php
namespace App;

/**
 * PON Calculator
 * Based on pemudanet.com PON calculator
 */
class PONCalculator {

    // Splitter ratio losses (in dB)
    private static $splitterLosses = [
        '1:2' => 3.5,
        '1:4' => 7.0,
        '1:8' => 10.5,
        '1:16' => 14.0,
        '1:32' => 17.5,
        '1:64' => 21.0,
    ];

    // Custom ratio losses (based on pemudanet.com calculator)
    // Note: These values represent the loss for the SMALLER port percentage
    private static $customRatioLosses = [
        '20:80' => 16.8,  // 20% output = -16.8 dB
        '30:70' => 13.5,  // 30% output = -13.5 dB
        '50:50' => 10.0,  // 50% output = -10.0 dB
    ];

    // Custom ratio losses for each port (calculated from percentage)
    // Formula: Loss (dB) = 10 × log10(1 / percentage)
    private static $customRatioPortLosses = [
        '20:80' => [
            '20' => 6.99,  // 10 × log10(1/0.20) ≈ 7.0 dB
            '80' => 0.97,  // 10 × log10(1/0.80) ≈ 1.0 dB
        ],
        '30:70' => [
            '30' => 5.23,  // 10 × log10(1/0.30) ≈ 5.2 dB
            '70' => 1.55,  // 10 × log10(1/0.70) ≈ 1.5 dB
        ],
        '50:50' => [
            '50' => 3.01,  // 10 × log10(1/0.50) = 3.0 dB
            '50' => 3.01,  // Same for both ports
        ],
    ];

    /**
     * Calculate optical power budget
     *
     * @param float $inputPower Initial laser power (dBm)
     * @param string $splitterRatio Splitter ratio (e.g., '1:8')
     * @param float $fiberLoss Fiber loss (dB/km)
     * @param float $distance Distance in km
     * @param array $customRatio Custom ratio like ['20:80', '30:70', '50:50']
     * @return array Calculation results
     */
    public static function calculate($inputPower, $splitterRatio = '1:8', $fiberLoss = 0.5, $distance = 0, $customRatio = null) {
        $result = [
            'input_power' => $inputPower,
            'splitter_ratio' => $splitterRatio,
            'fiber_loss' => $fiberLoss,
            'distance' => $distance,
        ];

        // Get splitter loss
        $splitterLoss = self::$splitterLosses[$splitterRatio] ?? 10.5;
        $result['splitter_loss'] = $splitterLoss;

        // Calculate custom ratio loss if provided
        $customRatioLoss = 0;
        if ($customRatio && isset(self::$customRatioLosses[$customRatio])) {
            $customRatioLoss = self::$customRatioLosses[$customRatio];
        }
        $result['custom_ratio_loss'] = $customRatioLoss;

        // Calculate total fiber loss
        $totalFiberLoss = $fiberLoss * $distance;
        $result['total_fiber_loss'] = $totalFiberLoss;

        // Calculate total loss
        $totalLoss = $splitterLoss + $customRatioLoss + $totalFiberLoss;
        $result['total_loss'] = $totalLoss;

        // Calculate output power
        $outputPower = $inputPower - $totalLoss;
        $result['output_power'] = $outputPower;

        // Calculate next ODP power (without custom ratio loss)
        $nextOdpPower = $inputPower - $customRatioLoss - $totalFiberLoss;
        $result['next_odp_power'] = $nextOdpPower;

        // Determine signal quality
        if ($outputPower >= -20) {
            $result['signal_quality'] = 'Excellent';
            $result['signal_color'] = 'success';
        } elseif ($outputPower >= -25) {
            $result['signal_quality'] = 'Good';
            $result['signal_color'] = 'info';
        } elseif ($outputPower >= -27) {
            $result['signal_quality'] = 'Fair';
            $result['signal_color'] = 'warning';
        } else {
            $result['signal_quality'] = 'Poor';
            $result['signal_color'] = 'danger';
        }

        return $result;
    }

    /**
     * Calculate for OLT
     */
    public static function calculateOLT($attenuationDb) {
        return [
            'attenuation_db' => $attenuationDb,
            'output_power' => -$attenuationDb,
        ];
    }

    /**
     * Calculate for ODC
     */
    public static function calculateODC($parentPower, $portCount, $distance = 0, $fiberLoss = 0.5) {
        $totalFiberLoss = $fiberLoss * $distance;
        $outputPower = $parentPower - $totalFiberLoss;

        return [
            'parent_power' => $parentPower,
            'port_count' => $portCount,
            'fiber_loss' => $totalFiberLoss,
            'output_power' => $outputPower,
            'power_per_port' => $outputPower, // Same for all ports in ODC
        ];
    }

    /**
     * Calculate for ODP
     */
    public static function calculateODP($parentPower, $portCount, $useSplitter = false, $splitterRatio = '1:8', $distance = 0, $fiberLoss = 0.5) {
        $totalFiberLoss = $fiberLoss * $distance;
        $powerAfterFiber = $parentPower - $totalFiberLoss;

        $splitterLoss = 0;
        if ($useSplitter) {
            $splitterLoss = self::$splitterLosses[$splitterRatio] ?? 10.5;
        }

        $outputPower = $powerAfterFiber - $splitterLoss;

        return [
            'parent_power' => $parentPower,
            'port_count' => $portCount,
            'use_splitter' => $useSplitter,
            'splitter_ratio' => $splitterRatio,
            'splitter_loss' => $splitterLoss,
            'fiber_loss' => $totalFiberLoss,
            'output_power' => $outputPower,
            'power_per_port' => $outputPower / $portCount,
        ];
    }

    /**
     * Get available splitter ratios
     */
    public static function getSplitterRatios() {
        return array_keys(self::$splitterLosses);
    }

    /**
     * Get available custom ratios
     */
    public static function getCustomRatios() {
        return array_keys(self::$customRatioLosses);
    }

    /**
     * Calculate ODC Power (simplified for map)
     * ODC power based on OLT output power with fixed loss
     * Formula from pemudanet.com PON calculator
     *
     * Typical losses from OLT to ODC:
     * - Connector losses: 2x 0.5dB = 1dB
     * - Fiber loss: ~0.3dB/km × distance
     * - Splitter 1:4 at OLT: ~7dB (distributed to 4 ODCs)
     * - Additional margin: ~1dB
     *
     * Total typical loss: ~5.8dB (matches pemudanet.com)
     * Example: 9 dBm - 5.8 dB = 3.2 dBm
     */
    public function calculateODCPower($parentAttenuation, $oltOutputPower = 2.0) {
        // Fixed loss from OLT to ODC (based on pemudanet.com calculation)
        // This includes connector loss, fiber loss, and OLT-side splitter
        $fixedLoss = 5.8; // dB

        $calculatedPower = $oltOutputPower - $fixedLoss;

        return $calculatedPower;
    }

    /**
     * Calculate ODP Power (simplified for map)
     *
     * NOTE: Custom ratios (20:80, 30:70, 50:50) loss values INCLUDE internal splitter distribution.
     * These values are from pemudanet.com PON calculator and represent:
     * - Total loss from input to final output (already includes splitting to multiple ports)
     * - NOT just the ratio split, but ratio split + internal distribution
     */
    public function calculateODPPower($parentPower, $splitterRatio = null) {
        $splitterLoss = 0;

        if ($splitterRatio) {
            // Check both standard and custom ratios
            if (isset(self::$splitterLosses[$splitterRatio])) {
                $splitterLoss = self::$splitterLosses[$splitterRatio];
            } elseif (isset(self::$customRatioLosses[$splitterRatio])) {
                $splitterLoss = self::$customRatioLosses[$splitterRatio];
            }
        }

        $calculatedPower = $parentPower - $splitterLoss;

        return $calculatedPower;
    }

    /**
     * Calculate power from specific custom ratio port
     *
     * @param float $basePower Base power from parent ODP (before splitter)
     * @param string $ratio Custom ratio (e.g., "20:80")
     * @param string $selectedPort Selected port (e.g., "20%" or "80%")
     * @return float Calculated power for the selected port
     */
    public function calculateCustomRatioPort($basePower, $ratio, $selectedPort) {
        // Remove percentage sign from selected port if present
        $selectedPort = str_replace('%', '', $selectedPort);

        // Get port loss from lookup table
        if (isset(self::$customRatioPortLosses[$ratio][$selectedPort])) {
            $portLoss = self::$customRatioPortLosses[$ratio][$selectedPort];
            return $basePower - $portLoss;
        }

        // Fallback: if ratio not found, return base power
        return $basePower;
    }
}
