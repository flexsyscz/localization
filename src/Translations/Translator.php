<?php

declare(strict_types=1);

namespace Flexsyscz\Localization\Translations;

use Flexsyscz\Localization\Exceptions\InvalidArgumentException;
use Flexsyscz\Localization\Exceptions\InvalidConfigParameterException;
use Flexsyscz\Localization\Exceptions\InvalidDictionaryException;
use Nette\Localization;
use Nette\SmartObject;


/**
 * @property-read string 	            $namespace
 * @property-read string 	            $language
 * @property-read string	            $delimiter
 * @property-read array<string|mixed> 	$debugger
 */
class Translator implements Localization\Translator
{
	use SmartObject;

	private string $namespace;
	private string $language;
	private Dictionary $dictionary;
	private ?string $fallback = null;

	/** @var array<string|mixed> */
	private array $debugger;


	public function __construct(
		private readonly Configurator $configurator,
		private readonly Repository $repository,
		private readonly Logger $logger,
	)
	{
		$this->namespace = $this->configurator->defaultNamespace;
		$this->language = $this->configurator->defaultLanguage->value;

		$this->debugger = [];
	}


	public function setLanguage(SupportedLanguages $language, SupportedLanguages $fallback = null): self
	{
		$this->language = (string) $language->value;
		$dictionary = $this->repository->getBy($this->namespace, $language);
		if (!$dictionary) {
			throw new InvalidDictionaryException(sprintf("Dictionary with language '%s' in namespace '%s' not found.", $language->value, $this->namespace));
		}

		$this->fallback = $fallback ? (string) $fallback->value : $this->language;
		$this->setDictionary($dictionary);

		return $this;
	}


	public function setDictionary(Dictionary $dictionary): self
	{
		$this->dictionary = $dictionary;
		$this->namespace = $dictionary->namespace;
		$this->language = $dictionary->language;

		return $this;
	}


	/**
	 * @param string|\Stringable $message
	 * @param mixed ...$parameters
	 * @return string
	 */
	public function translate($message, ...$parameters): string
	{
		if (!isset($this->dictionary)) {
			throw new InvalidDictionaryException('Dictionary is not set.');
		}

		$translation = null;
		if (is_string($message)) {
			try {
				if ($this->configurator->delimiter === '') {
					throw new InvalidConfigParameterException('Delimiter must be non-empty string.');
				}

				$nodes = explode($this->configurator->delimiter, $message);
				$forcedNsMask = '#^!#';
				if (preg_match($forcedNsMask, current($nodes))) {
					$namespace = (string) (preg_replace($forcedNsMask, '', array_shift($nodes)));
					$dictionary = $this->repository->getBy($namespace, $this->configurator->defaultLanguage::tryFrom($this->language)); // @phpstan-ignore-line
					if ($dictionary) {
						$this->setDictionary($dictionary);
						$this->logger->log(sprintf("Dictionary '%s' has been set as primary.", $dictionary->filePath));
					} else {
						$this->logger->log(sprintf("Dictionary with language '%s' in namespace '%s' not found.", $this->language, $namespace));
					}
				}
				$translation = $this->dictionary->getByNodes($nodes, $parameters);
				if (!$translation && $this->fallback) {
					$dictionary = $this->repository->getBy($this->namespace, $this->configurator->defaultLanguage::tryFrom($this->fallback)); // @phpstan-ignore-line
					if ($dictionary) {
						$translation = $dictionary->getByNodes($nodes, $parameters);
						if ($translation) {
							$this->logger->log(sprintf("Translation '%s' not found in primary dictionary '%s' but found in fallback dictionary '%s'", $message, $this->dictionary->filePath, $dictionary->filePath));
						}
					}
				}

				if (!$translation) {
					$this->logger->log(sprintf("Translation '%s' not found in dictionary '%s'", $message, $this->dictionary->filePath));
					$translation = $message;
				}

			} catch (InvalidArgumentException $e) {
				$this->logger->log($e->getMessage());
			}
		}

		if ($this->configurator->debugMode) {
			$backtrace = [];
			foreach (debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 5) as $level) {
				if (isset($level['file'])) {
					$backtrace[] = $this->repository->normalizePath($level['file']);
				}
			}

			$this->debugger[] = [
				'message' => $message,
				'translation' => $translation,
				'backtrace' => array_reverse($backtrace),
				'dictionary' => [
					'namespace' => $this->dictionary->namespace,
					'language' => $this->dictionary->language,
					'filePath' => $this->dictionary->filePath,
				],
			];
		}

		return $translation ?? (string) $message;
	}


	public function getNamespace(): string
	{
		return $this->namespace;
	}


	public function getLanguage(): string
	{
		return $this->language;
	}


	public function getDelimiter(): string
	{
		return $this->delimiter;
	}


	/**
	 * @return array<string|mixed>
	 */
	public function getDebugger(): array
	{
		return $this->debugger;
	}
}
