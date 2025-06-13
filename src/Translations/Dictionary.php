<?php

declare(strict_types=1);

namespace Flexsyscz\Localization\Translations;

use Flexsyscz\Localization\Exceptions\InvalidConfigParameterException;
use Flexsyscz\Localization\Exceptions\InvalidNodeValueException;
use Flexsyscz\Localization\Exceptions\InvalidStateException;


class Dictionary
{
	private int $followings = 0;

	/** @var array<array<string|int|float>|string|int|float> */
	private array $data;


	public function __construct(
		private readonly Configurator $configurator,
		public readonly string $namespace,
		public readonly string $language,
		public readonly string $filePath,
	)
	{
	}


	/**
	 * @param array<array<string|int|float>|string|int|float> $data
	 * @return $this
	 */
	public function setData(array $data): self
	{
		$this->data = $data;

		return $this;
	}


	/**
	 * @param array<string> $nodes
	 * @param array<string|int|mixed> $parameters
	 * @param array<array<string|int|float>|string|int|float>|null $entryPoint
	 * @return string|null
	 */
	public function getByNodes(array $nodes, array $parameters, ?array $entryPoint = null): ?string
	{
		if ($this->followings <= $this->configurator->maxFollowings) {
			if (!$entryPoint) {
				$this->followings = 0;
				$entryPoint = $this->data;
			}

			$node = array_shift($nodes);
			if ($node) {
				if (isset($entryPoint[$node])) {
					if (is_array($entryPoint[$node])) {
						return $this->getByNodes($nodes, $parameters, $entryPoint[$node]);
					} elseif (is_string($entryPoint[$node])) {
						$message = $entryPoint[$node];
						$followMask = "#^{$this->configurator->followSymbol}#";
						if (preg_match($followMask, $message)) {
							$this->followings++;

							if ($this->configurator->delimiter === '') {
								throw new InvalidConfigParameterException('Delimiter must be non-empty string.');
							}

							$followNodes = explode($this->configurator->delimiter, (string) (preg_replace($followMask, '', $message)));
							return $this->getByNodes($nodes ? array_merge($followNodes, $nodes) : $followNodes, $parameters, $this->data);
						}

						return $parameters
							? (string) (call_user_func_array('sprintf', array_merge([$entryPoint[$node]], $parameters))) // @phpstan-ignore-line
							: (string) ($entryPoint[$node]);

					} else {
						throw new InvalidNodeValueException(sprintf("Node value '%s' is not supported.", var_export($entryPoint[$node], true)));
					}
				}
			} elseif ($parameters) {
				$node = $parameters[0];
				if (isset($entryPoint[$node])) {
					$parameters[0] = $entryPoint[$node];
					return (string) (call_user_func_array('sprintf', $parameters)); // @phpstan-ignore-line

				} elseif (isset($entryPoint[$this->configurator->placeholder])) {
					return (string) (call_user_func_array('sprintf', array_merge([$entryPoint[$this->configurator->placeholder]], $parameters))); // @phpstan-ignore-line
				}
			}
		} else {
			throw new InvalidStateException(sprintf('Max. followings limit exceeded: %s', var_export($nodes, true)));
		}

		$this->followings = 0;
		return null;
	}
}
