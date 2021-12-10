<?php

/**
 * Test: Translated component
 */

declare(strict_types=1);


use Tester\Assert;
use Tracy\Logger;
use Flexsyscz\Localization;

require __DIR__ . '/../bootstrap.php';

enum SupportedLanguages: string {
	case CZECH = 'cs_CZ';
	case ENGLISH = 'en_US';
}

$logDir = getLogDir();
$logger = new Logger($logDir);

$properties = new Localization\EnvironmentProperties();
$properties->supportedLanguages = SupportedLanguages::cases();
$properties->appDir = __DIR__ . '/../';
$properties->translationsDirectoryName = 'fixtures.translations/injectedTestComponent';
$properties->logging = true;
$properties->debugMode = true;

$environment = new Localization\Environment($properties, $logger);
$dictionariesRepository = new Localization\DictionariesRepository($environment);
$translator = new Localization\Translator($dictionariesRepository);
$translator->setup(SupportedLanguages::CZECH->value, SupportedLanguages::ENGLISH->value);

$dictionariesRepository->add(__DIR__ . '/fixtures.translations/', 'default');
$dictionary = $dictionariesRepository->getBy('default', SupportedLanguages::CZECH->value);

Assert::notNull($dictionary, sprintf('Expected %s', Localization\Dictionary::class));
$translator->setDictionary($dictionary);

test('', function () use ($translator, $dictionariesRepository) {
	$dictionariesRepository->add(__DIR__ . '/fixtures.translations/someComponentTranslations', 'myComponent');
	$dictionary = $dictionariesRepository->getBy('myComponent', SupportedLanguages::CZECH->value);
	Assert::notNull($dictionary, sprintf('Expected %s', Localization\Dictionary::class));
	$translator->setDictionary($dictionary);

	Assert::equal($translator->translate('hello'), 'ahoj');
	Assert::equal($translator->translate('componentName'), 'toto je název komponenty!');

	$translator->setLanguage(SupportedLanguages::ENGLISH->value);
	Assert::equal($translator->translate('hello'), 'hello world!');
	Assert::equal($translator->translate('componentName'), 'my component name!');


	#[Localization\NamespaceAttribute('customNS')]
	class TestComponent {
		use Localization\TranslatedComponent;

		public function print() {
			return $this->translate('message');
		}
	}

	$test = new TestComponent;
	$test->injectTranslator(new Localization\TranslatorNamespaceFactory($translator, $dictionariesRepository));

	Assert::equal($test->print(), 'Test message from injected component.');

	$translator->setLanguage(SupportedLanguages::CZECH->value);
	Assert::equal($test->print(), 'Testovací zpráva z injektované komponenty.');
});