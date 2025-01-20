<?php

declare(strict_types=1);

namespace Flexsyscz\Localization\Translations;

use Tracy\ILogger;


final class Logger
{
	public const LogFile = 'translations';


	public function __construct(private readonly Configurator $configurator, private readonly ILogger $logger)
	{
	}


	public function log(string|\Exception $message): void
	{
		if ($this->configurator->logging) {
			$this->logger->log($message, self::LogFile);
		}
	}
}
