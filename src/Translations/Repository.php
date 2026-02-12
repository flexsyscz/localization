<?php

declare(strict_types=1);

namespace Flexsyscz\Localization\Translations;

use Flexsyscz\Localization\Exceptions\InvalidArgumentException;
use Flexsyscz\Localization\Exceptions\InvalidDictionaryException;
use Flexsyscz\Localization\Exceptions\InvalidImportFile;
use Flexsyscz\Localization\Exceptions\InvalidNamespaceException;
use Nette\Neon\Neon;
use Nette\Utils\FileSystem;


class Repository
{
	private const AppDirFlag = '%appDir%';

	/** @var array<Dictionary[]> */
	private array $map;


	public function __construct(private readonly Configurator $configurator, private readonly Logger $logger)
	{
	}


	/**
	 * @return array<Dictionary[]>
	 */
	public function findAll(): array
	{
		return $this->map;
	}


	public function getBy(string $namespace, SupportedLanguages $language): ?Dictionary
	{
		return $this->map[$namespace][$language->value] ?? null;
	}


	public function has(string $namespace): bool
	{
		return isset($this->map[$namespace]);
	}


	public function add(string $path, ?string $namespace = null): self
	{
		if (!$namespace) {
			$namespace = $this->configurator->defaultNamespace;
		}

		if (isset($this->map[$namespace])) {
			throw new InvalidNamespaceException(sprintf("Namespace '%s' does already exist.", $namespace));
		}

		if (!is_dir($path)) {
			throw new InvalidDictionaryException(sprintf("Dictionary path '%s' is invalid.", $path));
		}

		$items = scandir($path);
		if (!is_array($items)) {
			throw new InvalidDictionaryException(sprintf("Unable to read items from directory '%s'.", $path));
		}

		$this->map[$namespace] = [];
		foreach ($items as $item) {
			$language = (string) (preg_replace('#\.neon$#', '', $item));
			if ($this->configurator->defaultLanguage::tryFrom($language)) {
				$filePath = $path . DIRECTORY_SEPARATOR . $item;
				$this->logger->log(sprintf("Language '%s' accepted in '%s'.", $language, $this->normalizePath($filePath)));

				try {
					$dictionary = new Dictionary($this->configurator, $namespace, $language, $filePath);
					$content = Neon::decode(FileSystem::read($filePath));
					if (!is_array($content)) {
						$content = [];
					}

					$importMask = sprintf('#^\\%s#', Configurator::ImportSymbol);
					foreach ($content as $key => $value) {
						if (preg_match($importMask, (string) $key)) {
							if (is_string($value)) {
								$import = str_contains($value, self::AppDirFlag)
									? str_replace(self::AppDirFlag, $this->configurator->appDirectory->getAbsolutePath(), $value)
									: dirname($filePath) . DIRECTORY_SEPARATOR . $value;

								if (str_contains($import, '*')) {
									$import = str_replace('*', $item, $import);
								}

								if (file_exists($import)) {
									unset($content[$key]);

									$key = (string)(preg_replace($importMask, '', $key));
									$content[$key] = Neon::decode(FileSystem::read($import));

									$this->logger->log(sprintf("Dictionary '%s' with language '%s' imported as key '%s' in namespace '%s'.", $this->normalizePath($filePath), $language, $key, $namespace));
								} else {
									throw new InvalidImportFile(sprintf("File '%s' not found.", $import));
								}
							} else {
								throw new InvalidImportFile(sprintf("Invalid path value to import as key '%s'.", $key));
							}
						}
					}

					$this->map[$namespace][$language] = $dictionary->setData($content); // @phpstan-ignore-line
					$this->logger->log(sprintf("Dictionary '%s' with language '%s' in namespace '%s' has been added.", $this->normalizePath($filePath), $language, $namespace));

				} catch (InvalidArgumentException $e) {
					$this->logger->log($e);
				}
			}
		}

		return $this;
	}


	public function normalizePath(string $path): string
	{
		$path = FileSystem::normalizePath($path);
		return (string) (preg_replace("#^{$this->configurator->appDirectory->getAbsolutePath()}#", '', $path));
	}
}
