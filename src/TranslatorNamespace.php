<?php
declare(strict_types=1);

namespace Flexsyscz\Localization;

use Nette\Localization;
use Nette\SmartObject;


/**
 * @property-read string					$namespace
 * @property-read Translator				$translator
 * @property-read DictionariesRepository	$dictionariesRepository
 */
final class TranslatorNamespace implements Localization\Translator
{
	use SmartObject;

	private string $namespace;
	private Translator $translator;
	private DictionariesRepository $dictionariesRepository;


	public function __construct(string $namespace, Translator $translator, DictionariesRepository $dictionariesRepository)
	{
		$this->namespace = $namespace;
		$this->translator = $translator;
		$this->dictionariesRepository = $dictionariesRepository;
	}


	public function getNamespace(): string
	{
		return $this->namespace;
	}


	public function getTranslator(): Translator
	{
		return $this->translator;
	}


	public function getDictionariesRepository(): DictionariesRepository
	{
		return $this->dictionariesRepository;
	}


	public function translate($message, ...$parameters): string
	{
		if($this->translator->namespace !== $this->namespace) {
			$dictionary = $this->dictionariesRepository->getBy($this->namespace, $this->translator->language);
			if(!$dictionary) {
				throw new InvalidStateException(sprintf("Dictionary with language '%s' in namespace '%s' not found.", $this->translator->language, $this->namespace));
			}

			$this->translator->setDictionary($dictionary);
		}

		return $this->translator->translate($message, ...$parameters);
	}
}