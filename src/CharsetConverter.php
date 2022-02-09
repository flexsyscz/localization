<?php

declare(strict_types=1);

namespace Flexsyscz\Localization;


class CharsetConverter
{
	/**
	 * @param string|array<string> $input
	 * @param string $charsetFrom
	 * @param string $charsetTo
	 * @param bool $transliteration
	 * @return string|array<false|string>
	 */
	public static function convert(
		string|array $input,
		string $charsetFrom = 'utf-8',
		string $charsetTo = 'windows-1250',
		bool $transliteration = true,
	): string|array|false {
		if ($transliteration) {
			$charsetTo = "{$charsetTo}//TRANSLIT";
		}

		if (is_array($input)) {
			foreach ($input as $key => $value) {
				$input[$key] = iconv($charsetFrom, $charsetTo, is_string($value) ? $value : '');
			}

			return $input;
		}

		return iconv($charsetFrom, $charsetTo, $input);
	}
}
