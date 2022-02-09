<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/Resources/SupportedLanguages.php';
require __DIR__ . '/Resources/TestComponent.php';

Tester\Environment::setup();
date_default_timezone_set('Europe/Prague');
