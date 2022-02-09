<?php

declare(strict_types=1);

namespace Flexsyscz\Localization;

use Nette\Neon\Neon;
use Nette\SmartObject;
use Nette\Utils\FileSystem;
use Tracy\ILogger;


/**
 * @property-read Environment	$environment
 */
class DictionariesRepository
{
	use SmartObject;

	private Environment $environment;

	/** @var array<Dictionary[]> */
	private array $map;


	public function __construct(Environment $environment)
	{
		$this->environment = $environment;
	}


	public function getEnvironment(): Environment
	{
		return $this->environment;
	}


	public function add(string $path, string $namespace = null): self
	{
		if (!$namespace) {
			$namespace = $this->environment->defaultNamespace;
		}

		if (isset($this->map[$namespace])) {
			throw new InvalidStateException("Namespace '%s' does already exist.");
		}

		if (!is_dir($path)) {
			throw new InvalidArgumentException(sprintf('Dictionary path is invalid: %s', $path));
		}

		$this->map[$namespace] = [];
		$supportedLanguages = $this->environment->getSupportedLanguages();

		$items = scandir($path);
		if (!is_array($items)) {
			throw new InvalidArgumentException(sprintf('Unable to read items from directory: %s', $path));
		}

		foreach ($items as $item) {
			$language = (string) (preg_replace('#\.neon$#', '', $item));
			if (in_array($language, $supportedLanguages, true)) {
				$filePath = $path . DIRECTORY_SEPARATOR . $item;
				$this->environment->log(sprintf("Language '%s' accepted in '%s'", $language, $this->environment->normalizePath($filePath)));

				try {
					$dictionary = new Dictionary($namespace, $language, $this->environment->normalizePath($filePath));
					$content = Neon::decode(FileSystem::read($filePath));
					if (is_array($content)) {
						$importMask = sprintf('#^\\%s#', Environment::IMPORT_SYMBOL);
						foreach ($content as $key => $value) {
							if (preg_match($importMask, $key)) {
								if (is_string($value)) {
									$import = dirname($filePath) . DIRECTORY_SEPARATOR . $value;
									if (file_exists($import)) {
										unset($content[$key]);

										$key = (string) (preg_replace($importMask, '', $key));
										$content[$key] = Neon::decode(FileSystem::read($import));

										$this->environment->log(sprintf("Dictionary '%s' with language '%s' imported as key '%s' in namespace '%s'.", $this->environment->normalizePath($filePath), $language, $key, $namespace));
									} else {
										throw new InvalidArgumentException(sprintf('The file to import not found: %s.', $import));
									}
								} else {
									throw new InvalidArgumentException(sprintf('Invalid path to import under key: %s.', $key));
								}
							}
						}

						$this->map[$namespace][$language] = $dictionary->setData($content);
						$this->environment->log(sprintf("Dictionary '%s' with language '%s' in namespace '%s' has been added.", $this->environment->normalizePath($filePath), $language, $namespace));
					} else {
						$this->environment->log(sprintf("Dictionary '%s' doesn't contain any iterable data.", $this->environment->normalizePath($filePath)), ILogger::ERROR);
					}

				} catch (InvalidStateException $e) {
					$this->environment->log($e);
				}
			}
		}

		return $this;
	}


	/**
	 * @return array<Dictionary[]>
	 */
	public function findAll(): array
	{
		return $this->map;
	}


	public function getBy(string $namespace, string $language): ?Dictionary
	{
		return $this->map[$namespace][$language] ?? null;
	}
}
