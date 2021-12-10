<?php

/**
 * Test: Flexsyscz\Localization\Translator errors: startup check
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

Assert::exception(function () use ($dictionariesRepository) {
	$namespace = 'default';
	$dictionariesRepository->add(__DIR__ . '/fixtures.translations_wrong_path/', $namespace);
	$dictionary = $dictionariesRepository->getBy($namespace, SupportedLanguages::CZECH->value);
}, Localization\InvalidArgumentException::class);