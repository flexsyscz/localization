<?php

declare(strict_types=1);

namespace Flexsyscz\Localization\Translations;


interface SupportedLanguages extends \BackedEnum
{
	public function getShortCode(): string;

	public function getDescription(): string;
}
