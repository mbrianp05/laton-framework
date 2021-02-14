<?php

namespace Mbrianp\FuncCollection\ORM;

class Utils
{
    public const VOWELS = ['a', 'e', 'i', 'o', 'u'];

    public const ES = ['x', 'sh', 'ch', 'o', 's'];

    public static function resolveValidIdentifier(string $str): string
    {
        return \strtolower($str);
    }

    public static function resolveTableName(string $str): string
    {
        return static::makePlural(static::resolveValidIdentifier($str));
    }

    /**
     * Converts a word into plural (only english words)
     */
    public static function makePlural(string $str): string
    {
        $word = $str;
        $lastLetter = \substr($str, -1);

        if (\in_array(\strtolower($lastLetter), static::ES)) {
            return $word = $str . 'es';
        }

        if ('y' == $lastLetter) {
            if (in_array(\strtolower(\substr($str, -2, 1)), static::VOWELS)) {
                return $word = $str . 's';
            }

            return $word = \substr_replace($word, -1, 'ies');
        }

        return $word . 's';
    }
}