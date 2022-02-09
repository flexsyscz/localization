<?php

declare(strict_types=1);

namespace Tests\Resources;

use Flexsyscz\Localization\NamespaceAttribute;
use Flexsyscz\Localization\TranslatedComponent;


#[NamespaceAttribute('customNS')]
class TestComponent {
	use TranslatedComponent;

	public function print(): string
	{
		return $this->translate('message');
	}
}
