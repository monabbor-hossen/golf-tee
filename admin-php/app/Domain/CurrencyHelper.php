<?php
declare(strict_types=1);

namespace App\Domain;

/**
 * World Currency Helper: provides symbols, formatting, and international currencies.
 */
class CurrencyHelper
{
    /**
     * Complete catalogue of world currencies (ISO 4217).
     *
     * @var array<string, array{symbol: string, name: string}>
     */
    private static array $currencies = [
        'USD' => ['symbol' => '$',     'name' => 'US Dollar'],
        'EUR' => ['symbol' => '€',     'name' => 'Euro'],
        'GBP' => ['symbol' => '£',     'name' => 'British Pound'],
        'CAD' => ['symbol' => 'CA$',   'name' => 'Canadian Dollar'],
        'AUD' => ['symbol' => 'A$',    'name' => 'Australian Dollar'],
        'JPY' => ['symbol' => '¥',     'name' => 'Japanese Yen'],
        'CHF' => ['symbol' => 'CHF',   'name' => 'Swiss Franc'],
        'CNY' => ['symbol' => '¥',     'name' => 'Chinese Yuan'],
        'INR' => ['symbol' => '₹',     'name' => 'Indian Rupee'],
        'BDT' => ['symbol' => '৳',     'name' => 'Bangladeshi Taka'],
        'PKR' => ['symbol' => '₨',     'name' => 'Pakistani Rupee'],
        'LKR' => ['symbol' => 'Rs',    'name' => 'Sri Lankan Rupee'],
        'NPR' => ['symbol' => 'Rs',    'name' => 'Nepalese Rupee'],
        'AED' => ['symbol' => 'AED',   'name' => 'UAE Dirham'],
        'SAR' => ['symbol' => 'SAR',   'name' => 'Saudi Riyal'],
        'QAR' => ['symbol' => 'QAR',   'name' => 'Qatari Riyal'],
        'KWD' => ['symbol' => 'KWD',   'name' => 'Kuwaiti Dinar'],
        'BHD' => ['symbol' => 'BHD',   'name' => 'Bahraini Dinar'],
        'OMR' => ['symbol' => 'OMR',   'name' => 'Omani Rial'],
        'JOD' => ['symbol' => 'JD',    'name' => 'Jordanian Dinar'],
        'SGD' => ['symbol' => 'S$',    'name' => 'Singapore Dollar'],
        'HKD' => ['symbol' => 'HK$',   'name' => 'Hong Kong Dollar'],
        'NZD' => ['symbol' => 'NZ$',   'name' => 'New Zealand Dollar'],
        'KRW' => ['symbol' => '₩',     'name' => 'South Korean Won'],
        'TWD' => ['symbol' => 'NT$',   'name' => 'New Taiwan Dollar'],
        'THB' => ['symbol' => '฿',     'name' => 'Thai Baht'],
        'MYR' => ['symbol' => 'RM',    'name' => 'Malaysian Ringgit'],
        'IDR' => ['symbol' => 'Rp',    'name' => 'Indonesian Rupiah'],
        'PHP' => ['symbol' => '₱',     'name' => 'Philippine Peso'],
        'VND' => ['symbol' => '₫',     'name' => 'Vietnamese Dong'],
        'BRL' => ['symbol' => 'R$',    'name' => 'Brazilian Real'],
        'MXN' => ['symbol' => 'MX$',   'name' => 'Mexican Peso'],
        'ARS' => ['symbol' => 'AR$',   'name' => 'Argentine Peso'],
        'CLP' => ['symbol' => 'CLP$',  'name' => 'Chilean Peso'],
        'COP' => ['symbol' => 'COL$',  'name' => 'Colombian Peso'],
        'PEN' => ['symbol' => 'S/',    'name' => 'Peruvian Sol'],
        'UYU' => ['symbol' => '$U',    'name' => 'Uruguayan Peso'],
        'CRC' => ['symbol' => '₡',     'name' => 'Costa Rican Colon'],
        'DOP' => ['symbol' => 'RD$',   'name' => 'Dominican Peso'],
        'JMD' => ['symbol' => 'J$',    'name' => 'Jamaican Dollar'],
        'TTD' => ['symbol' => 'TT$',   'name' => 'Trinidad & Tobago Dollar'],
        'ZAR' => ['symbol' => 'R',     'name' => 'South African Rand'],
        'EGP' => ['symbol' => 'E£',    'name' => 'Egyptian Pound'],
        'NGN' => ['symbol' => '₦',     'name' => 'Nigerian Naira'],
        'KES' => ['symbol' => 'KSh',   'name' => 'Kenyan Shilling'],
        'GHS' => ['symbol' => 'GH₵',   'name' => 'Ghanaian Cedi'],
        'UGX' => ['symbol' => 'USh',   'name' => 'Ugandan Shilling'],
        'TZS' => ['symbol' => 'TSh',   'name' => 'Tanzanian Shilling'],
        'MAD' => ['symbol' => 'MAD',   'name' => 'Moroccan Dirham'],
        'DZD' => ['symbol' => 'DA',    'name' => 'Algerian Dinar'],
        'TND' => ['symbol' => 'DT',    'name' => 'Tunisian Dinar'],
        'SEK' => ['symbol' => 'kr',    'name' => 'Swedish Krona'],
        'NOK' => ['symbol' => 'kr',    'name' => 'Norwegian Krone'],
        'DKK' => ['symbol' => 'kr',    'name' => 'Danish Krone'],
        'ISK' => ['symbol' => 'kr',    'name' => 'Icelandic Krona'],
        'PLN' => ['symbol' => 'zł',    'name' => 'Polish Zloty'],
        'CZK' => ['symbol' => 'Kč',    'name' => 'Czech Koruna'],
        'HUF' => ['symbol' => 'Ft',    'name' => 'Hungarian Forint'],
        'RON' => ['symbol' => 'lei',   'name' => 'Romanian Leu'],
        'BGN' => ['symbol' => 'лв',    'name' => 'Bulgarian Lev'],
        'TRY' => ['symbol' => '₺',     'name' => 'Turkish Lira'],
        'RUB' => ['symbol' => '₽',     'name' => 'Russian Ruble'],
        'UAH' => ['symbol' => '₴',     'name' => 'Ukrainian Hryvnia'],
        'RSD' => ['symbol' => 'din',   'name' => 'Serbian Dinar'],
        'BAM' => ['symbol' => 'KM',    'name' => 'Bosnia-Herzegovina Mark'],
        'GEL' => ['symbol' => '₾',     'name' => 'Georgian Lari'],
        'KZT' => ['symbol' => '₸',     'name' => 'Kazakhstani Tenge'],
        'AZN' => ['symbol' => '₼',     'name' => 'Azerbaijani Manat'],
        'MOP' => ['symbol' => 'MOP$',  'name' => 'Macanese Pataca'],
        'BND' => ['symbol' => 'B$',    'name' => 'Brunei Dollar'],
        'FJD' => ['symbol' => 'FJ$',   'name' => 'Fijian Dollar'],
        'XAF' => ['symbol' => 'FCFA',  'name' => 'Central African CFA Franc'],
        'XOF' => ['symbol' => 'CFA',   'name' => 'West African CFA Franc'],
    ];

    /**
     * Get all currencies.
     *
     * @return array<string, array{symbol: string, name: string}>
     */
    public static function getAll(): array
    {
        return self::$currencies;
    }

    /**
     * Get current active currency code from settings.
     */
    public static function getCode(): string
    {
        if (function_exists('getSetting')) {
            $code = strtoupper(trim(getSetting('currency', 'USD')));
            if (isset(self::$currencies[$code])) {
                return $code;
            }
        }
        return 'USD';
    }

    /**
     * Get the active currency symbol (or for a specific currency code).
     */
    public static function getSymbol(?string $code = null): string
    {
        $c = strtoupper(trim((string)($code ?? self::getCode())));
        return self::$currencies[$c]['symbol'] ?? ($c !== '' ? $c : '$');
    }

    /**
     * Format a numerical amount with the active currency symbol.
     */
    public static function format(float|int|string $amount, int $decimals = 0, ?string $code = null): string
    {
        $num = (float)$amount;
        $symbol = self::getSymbol($code);
        return $symbol . number_format($num, $decimals);
    }
}
