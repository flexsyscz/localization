<?php
declare(strict_types=1);

namespace Flexsyscz\Localization;

use Nette\SmartObject;


final class TranslatorNamespaceFactory
{
	private Translator $translator;
	private DictionariesRepository $dictionariesRepository;


	public function __construct(Translator $translator, DictionariesRepository $dictionariesRepository)
	{
		$this->translator = $translator;
		$this->dictionariesRepository = $dictionariesRepository;
	}

	public function getTranslationsDirectoryName(): string
	{
		return $this->dictionariesRepository->environment->translationsDirectoryName;
	}


	public function create(string $namespace): TranslatorNamespace
	{
		return new TranslatorNamespace($namespace, $this->translator, $this->dictionariesRepository);
	}
}