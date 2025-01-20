<?php

declare(strict_types=1);

namespace Tests\Resources;

use Flexsyscz\Localization\Translations\NamespaceAttribute;
use Flexsyscz\Localization\Translations\TranslatedComponent;


#[NamespaceAttribute('customNS')]
class TestComponent {
	use TranslatedComponent;

	public function print(): string
	{
		return $this->translate('message');
	}
}
