<?php

declare(strict_types=1);

namespace Tests\Translator;

use Flexsyscz\Localization;
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

	private Localization\DictionariesRepository $dictionariesRepository;


	public function setUp(): void
	{
		$this->logDir = __DIR__ . '/../log/' . getmypid();
		if(!is_dir($this->logDir)) {
			@mkdir($this->logDir);
		}

		$properties = new Localization\EnvironmentProperties();
		$properties->supportedLanguages = SupportedLanguages::cases();
		$properties->appDir = __DIR__ . '/../';
		$properties->translationsDirectoryName = 'fixtures.translations';
		$properties->logging = true;
		$properties->debugMode = true;

		$logger = new Logger($this->logDir);
		$environment = new Localization\Environment($properties, $logger);
		$this->dictionariesRepository = new Localization\DictionariesRepository($environment);
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
			$this->dictionariesRepository->add(__DIR__ . '/fixtures.translations_wrong_path/', $namespace);
			$this->dictionariesRepository->getBy($namespace, SupportedLanguages::CZECH->value);
		}, Localization\InvalidArgumentException::class);
	}


	public function testLoggingTranslation(): void
	{
		$translator = new Localization\Translator($this->dictionariesRepository);
		$translator->setup(SupportedLanguages::CZECH->value, SupportedLanguages::ENGLISH->value);

		$namespace = 'default';
		$this->dictionariesRepository->add(__DIR__ . '/fixtures.translations/', $namespace);
		$dictionary = $this->dictionariesRepository->getBy($namespace, SupportedLanguages::CZECH->value);

		Assert::notNull($dictionary, sprintf('Expected %s', Localization\Dictionary::class));
		$translator->setDictionary($dictionary);

		$translator->translate('messages.error.accessDenied_');
		Assert::true(file_exists($this->logDir . '/error.log'));
	}


	public function testLoggingMaxFollowingsExceeded(): void
	{
		$translator = new Localization\Translator($this->dictionariesRepository);
		$translator->setup(SupportedLanguages::CZECH->value, SupportedLanguages::ENGLISH->value);

		$namespace = 'default';
		$this->dictionariesRepository->add(__DIR__ . '/fixtures.translations/', $namespace);
		$dictionary = $this->dictionariesRepository->getBy($namespace, SupportedLanguages::CZECH->value);

		Assert::notNull($dictionary, sprintf('Expected %s', Localization\Dictionary::class));
		$translator->setDictionary($dictionary);

		$translator->translate('content.homepage.description.part5');
		Assert::true(file_exists($this->logDir . '/error.log'));
	}


	public function testTranslate(): void
	{
		$translator = new Localization\Translator($this->dictionariesRepository);
		$translator->setup(SupportedLanguages::CZECH->value, SupportedLanguages::ENGLISH->value);

		$namespace = 'default';
		$this->dictionariesRepository->add(__DIR__ . '/fixtures.translations/', $namespace);
		$dictionary = $this->dictionariesRepository->getBy($namespace, SupportedLanguages::CZECH->value);

		Assert::notNull($dictionary, sprintf('Expected %s', Localization\Dictionary::class));
		$translator->setDictionary($dictionary);

		Assert::equal('Uživatel nenalezen.', $translator->translate('messages.error.userNotFound'));
		Assert::equal('Dobrý den!', $translator->translate('content.homepage.header'));
		Assert::equal('Dobrý den!', $translator->translate('content.homepage.description.part3'));
		Assert::equal('et dolores el simet 2', $translator->translate('content.homepage.description.part4.part2'));

		Assert::equal('Titulek předka', $translator->translate('parent.title'));
		Assert::equal('Titulek předka', $translator->translate('!default.parent.title'));

		Assert::equal('Dnes je pátek', $translator->translate('content.title', 'pátek'));

		Assert::equal('před chvílí', $translator->translate('time.ago.second', 1));
		Assert::equal('před chvílí', $translator->translate('time.ago.second', 2));
		Assert::equal('před 5 sek.', $translator->translate('time.ago.second', 5));

		Assert::equal('Uživatel John Doe [ID: 12345] se přihlásil v 08:12.', $translator->translate('placeholder.userLogged', 'John Doe', 12345, '08:12'));


		Assert::equal('Hello world!', $translator->setLanguage(SupportedLanguages::ENGLISH->value, SupportedLanguages::CZECH->value)
			->translate('content.homepage.header'));

		Assert::equal('Hello world!', $translator->translate('content.homepage.description.part3'));
		Assert::equal('et dolores el simet 2', $translator->translate('content.homepage.description.part4.part2'));

		Assert::equal('Parent title', $translator->translate('parent.title'));

		Assert::equal('Today is friday', $translator->translate('content.title', 'friday'));

		Assert::equal('few moments ago', $translator->translate('time.ago.second', 1));
		Assert::equal('few moments ago', $translator->translate('time.ago.second', 2));
		Assert::equal('5 secs. ago', $translator->translate('time.ago.second', 5));

		Assert::equal('User John Doe [ID: 12345] has been logged in at 08:12.', $translator->translate('placeholder.userLogged', 'John Doe', 12345, '08:12'));

		Assert::true(file_exists($this->logDir . '/info.log'));
		Assert::false(file_exists($this->logDir . '/error.log'));


		Assert::equal('Tento překlad v EN chybí', $translator->translate('fallback.a'));

		Assert::equal('This translation is missing in CZ', $translator->setLanguage(SupportedLanguages::CZECH->value)
			->translate('fallback.b'));

		Assert::true(file_exists($this->logDir . '/error.log'));
	}
}

(new TranslatorTest)->run();
