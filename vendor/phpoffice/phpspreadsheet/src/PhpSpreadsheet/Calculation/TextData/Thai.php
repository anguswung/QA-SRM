<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\TextData;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class Thai
{
    use ArrayEnabled;

    private const THAI_DIGITS = [
        0 => '喔ㄠ腹喔權涪喙?,
        1 => '喔笝喔多箞喔?,
        2 => '喔腑喔?,
        3 => '喔覆喔?,
        4 => '喔傅喙?,
        5 => '喔箟喔?,
        6 => '喔竵',
        7 => '喙€喔堗箛喔?,
        8 => '喙佮笡喔?,
        9 => '喙€喔佮箟喔?,
    ];

    private const THAI_UNITS = [
        1 => '喔复喔?,
        2 => '喔｀箟喔涪',
        3 => '喔炧副喔?,
        4 => '喔浮喔粪箞喔?,
        5 => '喙佮釜喔?,
        6 => '喔ム箟喔侧笝',
    ];

    private const THAI_COMPOUND_ONE = '喙€喔箛喔?;
    private const THAI_COMPOUND_TWO = '喔⑧傅喙?;
    private const THAI_INTEGER = '喔栢箟喔о笝';
    private const THAI_MINUS = '喔ム笟';
    private const THAI_BAHT = '喔氞覆喔?;
    private const THAI_SATANG = '喔笗喔侧竾喔勦箤';

    /**
     * BAHTTEXT.
     *
     * @param mixed $number The number or array of numbers to convert
     *
     * @return array<mixed>|string If an array of values is passed as the argument, then the returned result will also be an array with the same dimensions
     */
    public static function getBahtText(mixed $number): array|string
    {
        if (is_array($number)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $number);
        }

        if (is_string($number) && preg_match('/^-?\d+$/', $number)) {
            $isNegative = str_starts_with($number, '-');
            $baht = ltrim($number, '-0') ?: '0';
            $satang = '00';
        } elseif (is_bool($number) || is_numeric($number)) {
            $number += 0;
            $isNegative = $number < 0;
            [$baht, $satang] = explode('.', number_format(abs($number), 2, '.', ''));
        } else {
            return ExcelError::VALUE();
        }

        $hasWhole = $baht !== '0';
        $hasFraction = $satang !== '00';

        if (!$hasWhole && !$hasFraction) {
            return self::THAI_DIGITS[0] . self::THAI_BAHT . self::THAI_INTEGER;
        }

        $text = $isNegative
            ? self::THAI_MINUS
            : '';

        if ($hasWhole) {
            $text .= self::convertLarge($baht) . self::THAI_BAHT;
        }

        $text .= $hasFraction
            ? self::convertBlock($satang) . self::THAI_SATANG
            : self::THAI_INTEGER;

        return $text;
    }

    private static function convertLarge(string $digits): string
    {
        $length = strlen($digits) % 6 ?: 6;

        $chunks = [
            substr($digits, 0, $length),
            ...str_split(substr($digits, $length), 6),
        ];

        $chunks = array_filter($chunks, fn (string $chunk): bool => $chunk !== '');

        return implode(
            self::THAI_UNITS[6],
            array_map(self::convertBlock(...), $chunks)
        );
    }

    private static function convertBlock(string $block): string
    {
        $out = '';
        $length = strlen($block);
        $i = 0;

        // Hundreds and higher powers
        for ($power = $length - 1; $power >= 2; --$power) {
            $digit = $block[$i++];
            if ($digit !== '0') {
                $out .= self::THAI_DIGITS[$digit] . self::THAI_UNITS[$power];
            }
        }

        // Tens
        $ten = $length > 1 ? $block[$i++] : '0';
        if ($ten !== '0') {
            $out .= match ($ten) {
                '1' => '',
                '2' => self::THAI_COMPOUND_TWO,
                default => self::THAI_DIGITS[$ten],
            } . self::THAI_UNITS[1];
        }

        // Ones
        $one = $block[$i] ?? '0';
        if ($one !== '0') {
            $out .= $ten !== '0' && $one === '1'
                ? self::THAI_COMPOUND_ONE
                : self::THAI_DIGITS[$one];
        }

        return $out;
    }
}
