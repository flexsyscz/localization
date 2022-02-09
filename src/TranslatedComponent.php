<?php

declare(strict_types=1);

namespace Flexsyscz\Localization;

use ReflectionClass;


trait TranslatedComponent
{
	private TranslatorNamespace $translatorNamespace;
	private ReflectionClass $reflection;


	public function injectTranslator(TranslatorNamespaceFactory $factory): void
	{
		$this->reflection = new ReflectionClass($this);
		$dir = dirname((string) ($this->reflection->getFileName())) . DIRECTORY_SEPARATOR . $factory->getTranslationsDirectoryName();
		if (!file_exists($dir) || !is_dir($dir)) {
			throw new InvalidStateException(sprintf("Directory '%s' not found in '%s'", $factory->getTranslationsDirectoryName(), $dir));
		}

		$namespace = self::ns();
		$translatorNamespace = $factory->create($namespace);
		$translatorNamespace->dictionariesRepository->add($dir, $namespace);

		$this->translatorNamespace = $translatorNamespace;
	}


	public function ns(string $name = null): string
	{
		$ns = null;
		foreach ($this->reflection->getAttributes() as $attribute) {
			if ($attribute->getName() === NamespaceAttribute::class) {
				$args = $attribute->getArguments();
				$ns = $args[0] ?? null;
			}
		}

		$ns ??= $this->reflection->getName();
		return $name
			? sprintf('!%s%s%s', $ns, $this->translatorNamespace->translator->delimiter, $name)
			: $ns;
	}


	public function translate(string $message, bool $fullyQualifiedNamespace = false): string
	{
		if ($fullyQualifiedNamespace) {
			$message = $this->ns($message);
		}

		return $this->translatorNamespace->translate($message);
	}
}
