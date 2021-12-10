<?php
declare(strict_types=1);

namespace Flexsyscz\Localization;

use Attribute;


#[Attribute]
class NamespaceAttribute
{
	public string $name;


	public function __construct(string $name)
	{
		$this->name = $name;
	}
}