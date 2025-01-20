<?php

declare(strict_types=1);

namespace Flexsyscz\Localization;

use Flexsyscz\Localization\Exceptions\InvalidCharsetException;


class CharsetConverter
{
	/**
	 * @param string|array<string> $input
	 * @param string $charsetFrom
	 * @param string $charsetTo
	 * @param bool $transliteration
	 * @return false|string|array<false|string>
	 */
	public static function convert(
		string|array $input,
		string $charsetFrom = 'utf-8',
		string $charsetTo = 'windows-1250',
		bool $transliteration = true,
	): string|array|false {
		if (!in_array($charsetFrom, mb_list_encodings(), true)) {
			throw new InvalidCharsetException("Invalid charset: $charsetFrom");
		}

		if (!in_array($charsetTo, mb_list_encodings(), true)) {
			throw new InvalidCharsetException("Invalid charset: $charsetTo");
		}

		if ($transliteration) {
			$charsetTo = "{$charsetTo}//TRANSLIT";
		}

		if (is_array($input)) {
			return array_map(fn($value) => iconv($charsetFrom, $charsetTo, (string) $value) ?: $value, $input);
		}

		$converted = iconv($charsetFrom, $charsetTo, $input);
		if ($converted === false) {
			return false;
		}

		return $converted;
	}
}
