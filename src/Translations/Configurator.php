<?php

declare(strict_types=1);

namespace Flexsyscz\Localization\Translations;

use Flexsyscz\FileSystem\Directories\AppDirectory;
use Flexsyscz\Localization\Exceptions\InvalidConfigParameterException;


final class Configurator
{
	public const ImportSymbol = '+';


	public function __construct(
		public readonly AppDirectory $appDirectory,
		public readonly SupportedLanguages $defaultLanguage,
		public readonly bool $debugMode = false,
		public readonly bool $logging = false,
		public readonly string $translationsDirName = 'translations',
		public readonly string $defaultNamespace = 'default',
		public readonly int $maxFollowings = 5,
		public readonly string $delimiter = '.',
		public readonly string $placeholder = '?',
		public readonly string $followSymbol = '@',
	)
	{
		$enum = ['.', ':', '_', '->', '/', '-'];
		if (!in_array($delimiter, $enum, true)) {
			throw new InvalidConfigParameterException(sprintf("Delimiter '%s' is not allowed in the enumeration ['%s'].", $delimiter, implode("', '", $enum)));
		}

		$enum = ['?', '*', '%'];
		if (!in_array($placeholder, $enum, true)) {
			throw new InvalidConfigParameterException(sprintf("Placeholder '%s' is not allowed in the enumeration ['%s']", $placeholder, implode("', '", $enum)));
		}

		$enum = ['@', '$', '~'];
		if (!in_array($followSymbol, $enum, true)) {
			throw new InvalidConfigParameterException(sprintf("Follow symbol '%s' is not allowed in the enumeration ['%s']", $followSymbol, implode("', '", $enum)));
		}
	}
}
