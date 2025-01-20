<?php

declare(strict_types=1);

namespace Flexsyscz\Localization\Translations;

use Flexsyscz\Localization\Exceptions\InvalidStateException;
use Nette\Localization;


final class TranslatorNamespace implements Localization\Translator
{
	public function __construct(
		public readonly string $namespace,
		public readonly Configurator $configurator,
		public readonly Translator $translator,
		public readonly Repository $repository,
	) {
	}


	/**
	 * @param string|\Stringable $message
	 * @param mixed ...$parameters
	 * @return string
	 */
	public function translate($message, ...$parameters): string
	{
		if ($this->translator->namespace !== $this->namespace) {
			$dictionary = $this->repository->getBy($this->namespace, $this->configurator->defaultLanguage::tryFrom($this->translator->language)); // @phpstan-ignore-line
			if (!$dictionary) {
				throw new InvalidStateException(sprintf("Dictionary with language '%s' in namespace '%s' not found.", $this->translator->language, $this->namespace));
			}

			$this->translator->setDictionary($dictionary);
		}

		return $this->translator->translate($message, ...$parameters);
	}
}
