<?php

declare(strict_types=1);

namespace App\Helper;

final readonly class JiraDateTimeHelper
{
    public static function intervalToSeconds(\DateInterval $interval): int
    {
        return $interval->days * 86400 + $interval->h * 3600 + $interval->i * 60 + $interval->s;
    }

    public static function stringToSeconds(string $stringTime): int
    {
        $seconds = 0;
        // Wyrażenie regularne dopasowuje liczbę i jednostkę (d, h, m, s)
        preg_match_all('/(\d+)\s*(d|h|m|s)/i', $stringTime, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $value = (int)$match[1];
            $unit = strtolower($match[2]);

            switch ($unit) {
                case 'd':
                    $seconds += $value * 86400; // 24 * 60 * 60
                    break;
                case 'h':
                    $seconds += $value * 3600;  // 60 * 60
                    break;
                case 'm':
                    $seconds += $value * 60;
                    break;
                case 's':
                    $seconds += $value;
                    break;
            }
        }
        return $seconds;
    }

    public static function secondsToString(int $seconds): string
    {
        $days = intdiv($seconds, 86400);
        $seconds %= 86400;
        $hours = intdiv($seconds, 3600);
        $seconds %= 3600;
        $minutes = intdiv($seconds, 60);
        $seconds %= 60;

        return sprintf('%dd %dh %dm %ds', $days, $hours, $minutes, $seconds);
    }

    public static function intervalToString(\DateInterval $interval): string
    {
        $intervalFormat = '';
        if ((int)$interval->d !== 0) {
            $intervalFormat .= ' %dd';
        }
        if ((int)$interval->h !== 0) {
            $intervalFormat .= ' %hh';
        }
        if ((int)$interval->i !== 0) {
            $intervalFormat .= ' %im';
        }
        if ((int)$interval->s !== 0) {
            $intervalFormat .= ' %ss';
        }

        return trim($interval->format($intervalFormat));
    }
}
