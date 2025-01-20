<?php

declare(strict_types=1);

namespace Tests\Resources;

use Flexsyscz;


enum SupportedLanguages: string implements Flexsyscz\Localization\Translations\SupportedLanguages {
	case Czech = 'cs_CZ';
	case English = 'en_US';
	case Slovak = 'sk_SK';
}
