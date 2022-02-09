<?php

declare(strict_types=1);

namespace Flexsyscz\Localization;

use BackedEnum;
use Nette\SmartObject;

/**
 * @property string		$delimiter
 * @property string		$placeholder
 * @property string		$followSymbol
 */
class EnvironmentProperties
{
	use SmartObject;

	/** @var BackedEnum[] */
	public array $supportedLanguages;
	public string $appDir;
	public string $translationsDirectoryName = 'translations';
	public bool $logging = false;
	public bool $debugMode = false;

	public string $defaultNamespace = 'default';
	public int $maxFollowings = 5;
	private string $delimiter = '.';
	private string $placeholder = '?';
	private string $followSymbol = '@';


	public function setDelimiter(string $delimiter): void
	{
		$enum = ['.', ':', '_', '->', '/', '-'];
		if (!in_array($delimiter, $enum, true)) {
			throw new InvalidArgumentException(sprintf("Delimiter '%s' is not allowed in the enumeration ['%s']", $delimiter, implode("', '", $enum)));
		}

		$this->delimiter = $delimiter;
	}


	public function getDelimiter(): string
	{
		return $this->delimiter;
	}


	public function setPlaceholder(string $placeholder): void
	{
		$enum = ['?', '*', '%'];
		if (!in_array($placeholder, $enum, true)) {
			throw new InvalidArgumentException(sprintf("Placeholder '%s' is not allowed in the enumeration ['%s']", $placeholder, implode("', '", $enum)));
		}

		$this->placeholder = $placeholder;
	}


	public function getPlaceholder(): string
	{
		return $this->placeholder;
	}


	public function setFollowSymbol(string $followSymbol): void
	{
		$enum = ['@', '$', '~'];
		if (!in_array($followSymbol, $enum, true)) {
			throw new InvalidArgumentException(sprintf("Follow symbol '%s' is not allowed in the enumeration ['%s']", $followSymbol, implode("', '", $enum)));
		}

		$this->followSymbol = $followSymbol;
	}


	public function getFollowSymbol(): string
	{
		return $this->followSymbol;
	}
}
