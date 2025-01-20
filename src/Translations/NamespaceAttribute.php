<?php

declare(strict_types=1);

namespace Flexsyscz\Localization\Translations;

use Attribute;


#[Attribute]
class NamespaceAttribute
{
	public function __construct(public readonly string $name)
	{
	}
}
