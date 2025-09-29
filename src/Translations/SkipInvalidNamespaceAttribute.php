<?php

declare(strict_types=1);

namespace Flexsyscz\Localization\Translations;

use Attribute;


#[Attribute]
class SkipInvalidNamespaceAttribute
{
	public function __construct(public readonly bool $enabled = true)
	{
	}
}
