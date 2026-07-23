<?php
// includes/currency.php - Naira Currency Helper Functions

/**
 * Format Naira currency
 */
function formatNaira($amount, $with_symbol = true) {
    $formatted = number_format((float)$amount, 2, '.', ',');
    return $with_symbol ? '₦' . $formatted : $formatted;
}

/**
 * Format Naira with short notation
 */
function formatNairaShort($amount) {
    $amount = (float)$amount;
    if ($amount >= 1000000000) {
        return '₦' . number_format($amount / 1000000000, 1) . 'B';
    }
    if ($amount >= 1000000) {
        return '₦' . number_format($amount / 1000000, 1) . 'M';
    }
    if ($amount >= 1000) {
        return '₦' . number_format($amount / 1000, 1) . 'K';
    }
    return '₦' . number_format($amount, 2);
}

/**
 * Parse Naira string to number
 */
function parseNaira($string) {
    $cleaned = preg_replace('/[₦,]/', '', $string);
    return (float)$cleaned;
}

/**
 * Convert amount to Naira words
 */
function nairaToWords($amount) {
    $amount = (int)$amount;
    if ($amount == 0) return 'Zero Naira';
    
    $words = [
        0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four',
        5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
        10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen',
        14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen',
        18 => 'Eighteen', 19 => 'Nineteen', 20 => 'Twenty', 30 => 'Thirty',
        40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty', 70 => 'Seventy',
        80 => 'Eighty', 90 => 'Ninety'
    ];
    
    $thousands = ['', 'Thousand', 'Million', 'Billion'];
    $result = '';
    $i = 0;
    
    while ($amount > 0) {
        $chunk = $amount % 1000;
        if ($chunk > 0) {
            $chunkWords = '';
            if ($chunk < 100) {
                if ($chunk <= 20) {
                    $chunkWords = $words[$chunk];
                } else {
                    $tens = floor($chunk / 10) * 10;
                    $units = $chunk % 10;
                    $chunkWords = $words[$tens] . ($units ? '-' . $words[$units] : '');
                }
            } else {
                $hundreds = floor($chunk / 100);
                $remainder = $chunk % 100;
                $chunkWords = $words[$hundreds] . ' Hundred';
                if ($remainder > 0) {
                    if ($remainder <= 20) {
                        $chunkWords .= ' and ' . $words[$remainder];
                    } else {
                        $tens = floor($remainder / 10) * 10;
                        $units = $remainder % 10;
                        $chunkWords .= ' and ' . $words[$tens] . ($units ? '-' . $words[$units] : '');
                    }
                }
            }
            $result = $chunkWords . ' ' . $thousands[$i] . ' ' . $result;
        }
        $amount = floor($amount / 1000);
        $i++;
    }
    
    return trim($result) . ' Naira';
}

/**
 * Get currency symbol
 */
function getCurrencySymbol() {
    return '₦';
}

/**
 * Get currency code
 */
function getCurrencyCode() {
    return 'NGN';
}

/**
 * Get currency name
 */
function getCurrencyName() {
    return 'Naira';
}

/**
 * Get currency locale
 */
function getCurrencyLocale() {
    return 'en_NG';
}

/**
 * Get currency exchange rate to USD (for reference)
 * You can update this with a real API call
 */
function getNairaToUsdRate() {
    // This should be fetched from a real API in production
    // For now, using a static rate
    $rate = 1550; // 1 USD = 1550 NGN (approx)
    return $rate;
}

/**
 * Convert USD to Naira
 */
function usdToNaira($usd, $rate = null) {
    if ($rate === null) {
        $rate = getNairaToUsdRate();
    }
    return $usd * $rate;
}

/**
 * Convert Naira to USD
 */
function nairaToUsd($naira, $rate = null) {
    if ($rate === null) {
        $rate = getNairaToUsdRate();
    }
    return $naira / $rate;
}
?>