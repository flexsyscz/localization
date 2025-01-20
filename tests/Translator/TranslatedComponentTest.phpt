<?php

declare(strict_types=1);

namespace Tests\Translator;

use Flexsyscz\FileSystem\Directories\AppDirectory;
use Flexsyscz\FileSystem\Directories\DocumentRoot;
use Flexsyscz\Localization;
use Flexsyscz\Localization\Translations\Configurator;
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

	private Localization\Translations\Configurator $configurator;
	private Localization\Translations\Repository $repository;
	private Localization\Translations\Translator $translator;
	private ?Localization\Translations\Dictionary $dictionary;


	public function setUp(): void
	{
		$this->logDir = __DIR__ . '/../log/' . getmypid();
		if(!is_dir($this->logDir)) {
			@mkdir($this->logDir);
		}

		$documentRoot = new DocumentRoot(__DIR__ . '/../../');
		$appDir = new AppDirectory( __DIR__ . '/../../', $documentRoot);
		$tracyLogger = new Logger($this->logDir);
		$this->configurator = new Configurator($appDir, SupportedLanguages::Czech, debugMode: true, logging: true, translationsDirName: 'fixtures.translations/injectedTestComponent');
		$logger = new Localization\Translations\Logger($this->configurator, $tracyLogger);
		$this->repository = new Localization\Translations\Repository($this->configurator, $logger);
		$this->translator = new Localization\Translations\Translator($this->configurator, $this->repository, $logger);

		$this->repository->add(__DIR__ . '/fixtures.translations/', 'default');
		$this->dictionary = $this->repository->getBy('default', SupportedLanguages::Czech);
	}


	public function tearDown(): void
	{
		@unlink($this->logDir . '/info.log');
		@unlink($this->logDir . '/error.log');

		@rmdir($this->logDir);
	}


	public function testDefault(): void
	{
		Assert::notNull($this->dictionary, sprintf('Expected %s', Localization\Translations\Dictionary::class));
		if (isset($this->dictionary)) {
			$this->translator->setDictionary($this->dictionary);
		}
	}


	public function testComponent(): void
	{
		$this->repository->add(__DIR__ . '/fixtures.translations/someComponentTranslations', 'myComponent');
		$dictionary = $this->repository->getBy('myComponent', SupportedLanguages::Czech);
		Assert::notNull($dictionary, sprintf('Expected %s', Localization\Translations\Dictionary::class));
		if($dictionary) {
			$this->translator->setDictionary($dictionary);

			Assert::equal('ahoj', $this->translator->translate('hello'));
			Assert::equal('toto je název komponenty!', $this->translator->translate('componentName'));

			$this->translator->setLanguage(SupportedLanguages::English);
			Assert::equal('hello world!', $this->translator->translate('hello'));
			Assert::equal('my component name!', $this->translator->translate('componentName'));

			$test = new TestComponent();
			$test->injectTranslator(new Localization\Translations\TranslatorNamespaceFactory($this->configurator, $this->translator, $this->repository));

			Assert::equal('Test message from injected component.', $test->print());

			$this->translator->setLanguage(SupportedLanguages::Czech);
			Assert::equal('Testovací zpráva z injektované komponenty.', $test->print());
		}
	}
}

(new TranslatedComponentTest)->run();
