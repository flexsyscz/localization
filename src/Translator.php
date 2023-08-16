<?php

declare(strict_types=1);

namespace Flexsyscz\Localization;

use Nette\Localization;
use Nette\SmartObject;
use Tracy\ILogger;


/**
 * @property-read string 	            $namespace
 * @property-read string 	            $language
 * @property-read string	            $delimiter
 * @property-read array<string|mixed> 	$debugger
 */
class Translator implements Localization\Translator
{
	use SmartObject;

	private const TranslationNotSupportedError = '[!] ERROR: Translation not supported';

	private DictionariesRepository $dictionariesRepository;
	private Dictionary $dictionary;
	private string $namespace;
	private string $language;
	private ?string $fallback;

	/** @var array<string|mixed> */
	private array $debugger;


	public function __construct(DictionariesRepository $dictionariesRepository)
	{
		$this->dictionariesRepository = $dictionariesRepository;
		$this->namespace = $this->dictionariesRepository->environment->defaultNamespace;
		$this->debugger = [];
	}


	public function setup(string $language, string $fallback = null): void
	{
		if (!$this->dictionariesRepository->environment->isSupportedLanguage($language)) {
			throw new InvalidArgumentException(sprintf("Language '%s' is not supported.", $language));
		}

		$this->language = $language;
		$this->fallback = $fallback && $this->dictionariesRepository->environment->isSupportedLanguage($fallback) ? $fallback : null;
	}


	public function getNamespace(): string
	{
		return $this->namespace;
	}


	public function setLanguage(string $language, string $fallback = null): self
	{
		$dictionary = $this->dictionariesRepository->getBy($this->namespace, $language);
		if (!$dictionary) {
			throw new InvalidArgumentException(sprintf("Dictionary with language '%s' in namespace '%s' not found.", $language, $this->namespace));
		}

		$this->fallback = $fallback && $this->dictionariesRepository->environment->isSupportedLanguage($fallback)
			? $fallback
			: $this->language;
		$this->setDictionary($dictionary);

		return $this;
	}


	public function getLanguage(): ?string
	{
		return $this->language;
	}


	public function getDelimiter(): string
	{
		return $this->dictionariesRepository->environment->delimiter;
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
			throw new InvalidStateException('Dictionary is not set.');
		}

		$translation = null;
		if (is_string($message)) {
			try {
				if ($this->dictionariesRepository->environment->delimiter === '') {
					throw new InvalidArgumentException('Delimiter must be non-empty string.');
				}

				$nodes = explode($this->dictionariesRepository->environment->delimiter, $message);
				$forcedNsMask = '#^!#';
				if (preg_match($forcedNsMask, current($nodes))) {
					$namespace = (string) (preg_replace($forcedNsMask, '', array_shift($nodes)));
					$dictionary = $this->dictionariesRepository->getBy($namespace, $this->language);
					if ($dictionary) {
						$this->setDictionary($dictionary);
						$this->dictionariesRepository->environment->log(sprintf("Dictionary '%s' has been set as primary.", $dictionary->filePath));
					} else {
						$this->dictionariesRepository->environment->log(sprintf("Dictionary with language '%s' in namespace '%s' not found.", $this->language, $namespace), ILogger::ERROR);
					}
				}
				$translation = $this->dictionary->getByNodes($this->dictionariesRepository->environment, $nodes, $parameters);
				if (!$translation && $this->fallback) {
					$dictionary = $this->dictionariesRepository->getBy($this->namespace, $this->fallback);
					if ($dictionary) {
						$translation = $dictionary->getByNodes($this->dictionariesRepository->environment, $nodes, $parameters);
						if ($translation) {
							$this->dictionariesRepository->environment->log(sprintf("Translation '%s' not found in primary dictionary '%s' but found in fallback dictionary '%s'", $message, $this->dictionary->filePath, $dictionary->filePath), ILogger::ERROR);
						}
					}
				}

				if (!$translation) {
					$this->dictionariesRepository->environment->log(sprintf("Translation '%s' not found in dictionary '%s'", $message, $this->dictionary->filePath), ILogger::ERROR);
					$translation = $message;
				}

			} catch (InvalidArgumentException $e) {
				$this->dictionariesRepository->environment->log($e->getMessage(), ILogger::ERROR);
			}
		}

		if ($this->dictionariesRepository->environment->debugMode) {
			$backtrace = [];
			foreach (debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 5) as $level) {
				if (isset($level['file'])) {
					$backtrace[] = $this->dictionariesRepository->environment->normalizePath($level['file']);
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

		return $translation ?? self::TranslationNotSupportedError;
	}


	/**
	 * @return array<string|mixed>
	 */
	public function getDebugger(): array
	{
		return $this->debugger;
	}
}
