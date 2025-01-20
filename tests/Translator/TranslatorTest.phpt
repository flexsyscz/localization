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
use Tracy\Logger;

require __DIR__ . '/../bootstrap.php';


/**
 * @testCase
 */
class TranslatorTest extends TestCase
{
	private string $logDir;

	private Localization\Translations\Configurator $configurator;
	private Localization\Translations\Logger $logger;
	private Localization\Translations\Repository $repository;


	public function setUp(): void
	{
		$this->logDir = __DIR__ . '/../log/' . getmypid();
		if(!is_dir($this->logDir)) {
			@mkdir($this->logDir);
		}

		$documentRoot = new DocumentRoot(__DIR__ . '/../../');
		$appDir = new AppDirectory( __DIR__ . '/../../', $documentRoot);
		$tracyLogger = new Logger($this->logDir);
		$this->configurator = new Configurator($appDir, SupportedLanguages::Czech, debugMode: true, logging: true, translationsDirName: 'fixtures.translations');
		$this->logger = new Localization\Translations\Logger($this->configurator, $tracyLogger);
		$this->repository = new Localization\Translations\Repository($this->configurator, $this->logger);
	}


	public function tearDown(): void
	{
		@unlink($this->logDir . '/info.log');
		@unlink($this->logDir . '/error.log');

		@rmdir($this->logDir);
	}


	public function testLoggingStartup(): void
	{
		Assert::exception(function () {
			$namespace = 'default';
			$this->repository->add(__DIR__ . '/fixtures.translations_wrong_path/', $namespace);
			$this->repository->getBy($namespace, SupportedLanguages::Czech);
		}, Localization\Exceptions\InvalidDictionaryException::class);
	}


	public function testLoggingTranslation(): void
	{
		$translator = new Localization\Translations\Translator($this->configurator, $this->repository, $this->logger);

		$namespace = 'default';
		$this->repository->add(__DIR__ . '/fixtures.translations/', $namespace);
		$dictionary = $this->repository->getBy($namespace, SupportedLanguages::Czech);

		Assert::notNull($dictionary, sprintf('Expected %s', Localization\Translations\Dictionary::class));
		if (isset($dictionary)) {
			$translator->setDictionary($dictionary);
		}

		$translator->translate('messages.error.accessDenied_');
		Assert::true(file_exists($this->logDir . '/translations.log'));
	}


	public function testLoggingMaxFollowingsExceeded(): void
	{
		$translator = new Localization\Translations\Translator($this->configurator, $this->repository, $this->logger);

		$namespace = 'default';
		$this->repository->add(__DIR__ . '/fixtures.translations/', $namespace);
		$dictionary = $this->repository->getBy($namespace, SupportedLanguages::Czech);

		Assert::notNull($dictionary, sprintf('Expected %s', Localization\Translations\Dictionary::class));
		if (isset($dictionary)) {
			$translator->setDictionary($dictionary);
		}

		Assert::exception(function () use ($translator) {
			$translator->translate('content.homepage.description.part5');
		}, Localization\Exceptions\InvalidStateException::class);

		Assert::true(file_exists($this->logDir . '/translations.log'));
	}


	public function testTranslate(): void
	{
		$translator = new Localization\Translations\Translator($this->configurator, $this->repository, $this->logger);

		$namespace = 'default';
		$this->repository->add(__DIR__ . '/fixtures.translations/', $namespace);
		$dictionary = $this->repository->getBy($namespace, SupportedLanguages::Czech);

		Assert::notNull($dictionary, sprintf('Expected %s', Localization\Translations\Dictionary::class));
		if (isset($dictionary)) {
			$translator->setDictionary($dictionary);
		}

		Assert::equal('Uživatel nenalezen.', $translator->translate('messages.error.userNotFound'));
		Assert::equal('Dobrý den!', $translator->translate('content.homepage.header'));
		Assert::equal('Dobrý den!', $translator->translate('content.homepage.description.part3'));
		Assert::equal('et dolores el simet 2', $translator->translate('content.homepage.description.part4.part2'));

		Assert::equal('Titulek předka', $translator->translate('parent.title'));
		Assert::equal('Titulek předka', $translator->translate('!default.parent.title'));

		Assert::equal('Nový titulek', $translator->translate('parent2.title'));
		Assert::equal('Nový titulek', $translator->translate('!default.parent2.title'));

		Assert::equal('Další titulek', $translator->translate('parent3.title'));
		Assert::equal('Další titulek', $translator->translate('!default.parent3.title'));

		Assert::equal('Dnes je pátek', $translator->translate('content.title', 'pátek'));

		Assert::equal('před chvílí', $translator->translate('time.ago.second', 1));
		Assert::equal('před chvílí', $translator->translate('time.ago.second', 2));
		Assert::equal('před 5 sek.', $translator->translate('time.ago.second', 5));

		Assert::equal('Uživatel John Doe [ID: 12345] se přihlásil v 08:12.', $translator->translate('placeholder.userLogged', 'John Doe', 12345, '08:12'));


		Assert::equal('Hello world!', $translator->setLanguage(SupportedLanguages::English, SupportedLanguages::Czech)
			->translate('content.homepage.header'));

		Assert::equal('Hello world!', $translator->translate('content.homepage.description.part3'));
		Assert::equal('et dolores el simet 2', $translator->translate('content.homepage.description.part4.part2'));

		Assert::equal('Parent title', $translator->translate('parent.title'));
		Assert::equal('New title', $translator->translate('parent2.title'));
		Assert::equal('Another title', $translator->translate('parent3.title'));

		Assert::equal('Today is friday', $translator->translate('content.title', 'friday'));

		Assert::equal('few moments ago', $translator->translate('time.ago.second', 1));
		Assert::equal('few moments ago', $translator->translate('time.ago.second', 2));
		Assert::equal('5 secs. ago', $translator->translate('time.ago.second', 5));

		Assert::equal('User John Doe [ID: 12345] has been logged in at 08:12.', $translator->translate('placeholder.userLogged', 'John Doe', 12345, '08:12'));

		Assert::true(file_exists($this->logDir . '/translations.log'));

		Assert::equal('Tento překlad v EN chybí', $translator->translate('fallback.a'));

		Assert::equal('This translation is missing in CZ', $translator->setLanguage(SupportedLanguages::Czech, SupportedLanguages::English)
			->translate('fallback.b'));

		Assert::true(file_exists($this->logDir . '/translations.log'));
	}
}

(new TranslatorTest)->run();
