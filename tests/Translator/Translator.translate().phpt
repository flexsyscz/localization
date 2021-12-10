<?php

/**
 * Test: Flexsyscz\Localization\Translator::translate()
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
$properties->translationsDirectoryName = 'fixtures.translations';
$properties->logging = true;
$properties->debugMode = true;

$environment = new Localization\Environment($properties, $logger);
$dictionariesRepository = new Localization\DictionariesRepository($environment);
$translator = new Localization\Translator($dictionariesRepository);
$translator->setup(SupportedLanguages::CZECH->value, SupportedLanguages::ENGLISH->value);

$namespace = 'default';
$dictionariesRepository->add(__DIR__ . '/fixtures.translations/', $namespace);
$dictionary = $dictionariesRepository->getBy($namespace, SupportedLanguages::CZECH->value);

Assert::notNull($dictionary, sprintf('Expected %s', Localization\Dictionary::class));
$translator->setDictionary($dictionary);

test('', function () use ($translator, $logDir) {
	Assert::equal($translator->translate('messages.error.userNotFound'), 'Uživatel nenalezen.');
	Assert::equal($translator->translate('content.homepage.header'), 'Dobrý den!');
	Assert::equal($translator->translate('content.homepage.description.part3'), 'Dobrý den!');
	Assert::equal($translator->translate('content.homepage.description.part4.part2'), 'et dolores el simet 2');

	Assert::equal($translator->translate('parent.title'), 'Titulek předka');
	Assert::equal($translator->translate('!default.parent.title'), 'Titulek předka');

	Assert::equal($translator->translate('content.title', 'pátek'), 'Dnes je pátek');

	Assert::equal($translator->translate('time.ago.second', 1), 'před chvílí');
	Assert::equal($translator->translate('time.ago.second', 2), 'před chvílí');
	Assert::equal($translator->translate('time.ago.second', 5), 'před 5 sek.');

	Assert::equal($translator->translate('placeholder.userLogged', 'John Doe', 12345, '08:12'), 'Uživatel John Doe [ID: 12345] se přihlásil v 08:12.');


	Assert::equal($translator->setLanguage(SupportedLanguages::ENGLISH->value, SupportedLanguages::CZECH->value)
		->translate('content.homepage.header'), 'Hello world!');

	Assert::equal($translator->translate('content.homepage.description.part3'), 'Hello world!');
	Assert::equal($translator->translate('content.homepage.description.part4.part2'), 'et dolores el simet 2');

	Assert::equal($translator->translate('parent.title'), 'Parent title');

	Assert::equal($translator->translate('content.title', 'friday'), 'Today is friday');

	Assert::equal($translator->translate('time.ago.second', 1), 'few moments ago');
	Assert::equal($translator->translate('time.ago.second', 2), 'few moments ago');
	Assert::equal($translator->translate('time.ago.second', 5), '5 secs. ago');

	Assert::equal($translator->translate('placeholder.userLogged', 'John Doe', 12345, '08:12'), 'User John Doe [ID: 12345] has been logged in at 08:12.');

	Assert::true(file_exists($logDir . '/info.log'));
	Assert::false(file_exists($logDir . '/error.log'));


	Assert::equal($translator->translate('fallback.a'), 'Tento překlad v EN chybí');

	Assert::equal($translator->setLanguage(SupportedLanguages::CZECH->value)
		->translate('fallback.b'), 'This translation is missing in CZ');

	Assert::true(file_exists($logDir . '/error.log'));
});