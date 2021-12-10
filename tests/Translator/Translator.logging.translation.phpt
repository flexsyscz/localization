<?php

/**
 * Test: Flexsyscz\Localization\Translator errors: translation
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
	$translator->translate('messages.error.accessDenied_');
	Assert::true(file_exists($logDir . '/error.log'));
});