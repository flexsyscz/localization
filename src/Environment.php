<?php
declare(strict_types=1);

namespace Flexsyscz\Localization;

use Nette\SmartObject;
use Nette\Utils\FileSystem;
use Nette\Utils\Strings;
use Tracy\ILogger;


/**
 * @property-read string[]			 	$supportedLanguages
 * @property-read string				$translationsDirectoryName
 * @property-read bool					$logging
 * @property-read bool					$debugMode
 *
 * @property-read string				$defaultNamespace
 * @property-read string				$delimiter
 * @property-read string				$placeholder
 * @property-read string				$followSymbol
 * @property-read string				$maxFollowings
 */
class Environment
{
	use SmartObject;

	public const IMPORT_SYMBOL = '+';

	private string $defaultNamespace;
	private string $delimiter;
	private string $placeholder;
	private string $followSymbol;
	private int $maxFollowings;

	/** @var string[] */
	private array $supportedLanguages;
	private string $appDir;
	private string $translationsDirectoryName;
	private ILogger $logger;
	private bool $logging;
	private bool $debugMode;


	/**
	 * @param EnvironmentProperties $properties
	 * @param ILogger $logger
	 */
	public function __construct(EnvironmentProperties $properties, ILogger $logger)
	{
		$this->supportedLanguages = [];
		foreach ($properties->supportedLanguages as $supportedLanguage) {
			if(isset($supportedLanguage->name) && isset($supportedLanguage->value)) { // @todo remove condition after enums will be fully supported in PHPStan
				$this->supportedLanguages[Strings::lower($supportedLanguage->name)] = (string) $supportedLanguage->value;
			}
		}

		$this->appDir = dirname(FileSystem::normalizePath($properties->appDir));
		$this->translationsDirectoryName = $properties->translationsDirectoryName;
		$this->logging = $properties->logging;
		$this->debugMode = $properties->debugMode;

		$this->defaultNamespace = $properties->defaultNamespace;
		$this->delimiter = $properties->delimiter;
		$this->placeholder = $properties->placeholder;
		$this->followSymbol = $properties->followSymbol;
		$this->maxFollowings = $properties->maxFollowings;

		$this->logger = $logger;
	}


	public function isSupportedLanguage(string $iso): bool
	{
		return in_array($iso, $this->supportedLanguages, true);
	}


	/**
	 * @return string[]
	 */
	public function getSupportedLanguages(): array
	{
		return $this->supportedLanguages;
	}


	public function getTranslationsDirectoryName(): string
	{
		return $this->translationsDirectoryName;
	}


	public function getLogging(): bool
	{
		return $this->logging;
	}


	public function getDebugMode(): bool
	{
		return $this->debugMode;
	}


	public function getDefaultNamespace(): string
	{
		return $this->defaultNamespace;
	}


	public function getDelimiter(): string
	{
		return $this->delimiter;
	}


	public function getPlaceholder(): string
	{
		return $this->placeholder;
	}


	public function getFollowSymbol(): string
	{
		return $this->followSymbol;
	}


	public function getMaxFollowings(): int
	{
		return $this->maxFollowings;
	}


	public function normalizePath(string $path): string
	{
		$path = FileSystem::normalizePath($path);
		$mask = "#^{$this->appDir}#";

		return $this->appDir
			? (string) (preg_replace($mask, '', $path))
			: $path;
	}


	public function log(string|object $message, string $level = ILogger::INFO): void
	{
		if($this->logging) {
			$this->logger->log($message, $level);
		}
	}
}