<?php

declare(strict_types=1);

namespace Tests\Translator;

use Flexsyscz\Localization;
use Tester\Assert;
use Tester\TestCase;
use Tests\Resources\SupportedLanguages;
use Tests\Resources\TestComponent;
use Tracy\Logger;

require __DIR__ . '/../bootstrap.php';


/**
 * @testCase
 */
class TranslatedComponentTest extends TestCase
{
	private string $logDir;

	private Localization\DictionariesRepository $dictionariesRepository;
	private Localization\Translator $translator;
	private ?Localization\Dictionary $dictionary;


	public function setUp(): void
	{
		$this->logDir = __DIR__ . '/../log/' . getmypid();
		if(!is_dir($this->logDir)) {
			@mkdir($this->logDir);
		}

		$properties = new Localization\EnvironmentProperties();
		$properties->supportedLanguages = SupportedLanguages::cases();
		$properties->appDir = __DIR__ . '/tests/';
		$properties->translationsDirectoryName = 'fixtures.translations/injectedTestComponent';
		$properties->logging = true;
		$properties->debugMode = true;

		$logger = new Logger($this->logDir);
		$environment = new Localization\Environment($properties, $logger);
		$this->dictionariesRepository = new Localization\DictionariesRepository($environment);
		$this->translator = new Localization\Translator($this->dictionariesRepository);
		$this->translator->setup(SupportedLanguages::CZECH->value, SupportedLanguages::ENGLISH->value);

		$this->dictionariesRepository->add(__DIR__ . '/fixtures.translations/', 'default');
		$this->dictionary = $this->dictionariesRepository->getBy('default', SupportedLanguages::CZECH->value);
	}


	public function tearDown(): void
	{
		@unlink($this->logDir . '/info.log');
		@unlink($this->logDir . '/error.log');

		@rmdir($this->logDir);
	}


	public function testDefault(): void
	{
		Assert::notNull($this->dictionary, sprintf('Expected %s', Localization\Dictionary::class));
		$this->translator->setDictionary($this->dictionary);
	}


	public function testComponent(): void
	{
		$this->dictionariesRepository->add(__DIR__ . '/fixtures.translations/someComponentTranslations', 'myComponent');
		$dictionary = $this->dictionariesRepository->getBy('myComponent', SupportedLanguages::CZECH->value);
		Assert::notNull($dictionary, sprintf('Expected %s', Localization\Dictionary::class));
		if($dictionary) {
			$this->translator->setDictionary($dictionary);

			Assert::equal('ahoj', $this->translator->translate('hello'));
			Assert::equal('toto je název komponenty!', $this->translator->translate('componentName'));

			$this->translator->setLanguage(SupportedLanguages::ENGLISH->value);
			Assert::equal('hello world!', $this->translator->translate('hello'));
			Assert::equal('my component name!', $this->translator->translate('componentName'));

			$test = new TestComponent();
			$test->injectTranslator(new Localization\TranslatorNamespaceFactory($this->translator, $this->dictionariesRepository));

			Assert::equal('Test message from injected component.', $test->print());

			$this->translator->setLanguage(SupportedLanguages::CZECH->value);
			Assert::equal('Testovací zpráva z injektované komponenty.', $test->print());
		}
	}
}

(new TranslatedComponentTest)->run();
