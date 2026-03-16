<?php

declare(strict_types=1);

namespace Tests\Resources;

use Flexsyscz;


enum SupportedLanguages: string implements Flexsyscz\Localization\Translations\SupportedLanguages {
	case Czech = 'cs_CZ';
	case English = 'en_US';
	case Slovak = 'sk_SK';


	public function getShortCode(): string
	{
		$shortCodes = [
			self::Czech->value => 'cs',
			self::English->value => 'en',
			self::Slovak->value => 'sk',
		];

		return $shortCodes[$this->value] ?? '';
	}


	public function getDescription(): string
	{
		$descriptions = [
			self::Czech->value => 'Čeština',
			self::English->value => 'English',
			self::Slovak->value => 'Slovenština',
		];

		return $descriptions[$this->value] ?? '';
	}
}
