<?php

declare(strict_types=1);

namespace Flexsyscz\Localization\Translations;


final class TranslatorNamespaceFactory
{
	public function __construct(
		private readonly Configurator $configurator,
		private readonly Translator $translator,
		private readonly Repository $repository,
	)
	{
	}


	public function getTranslationsDirName(): string
	{
		return $this->configurator->translationsDirName;
	}


	public function create(string $namespace): TranslatorNamespace
	{
		return new TranslatorNamespace($namespace, $this->configurator, $this->translator, $this->repository);
	}
}
