<?php
declare(strict_types=1);

namespace Flexsyscz\Localization;

use Nette\Neon\Neon;
use Nette\SmartObject;
use Nette\Utils\FileSystem;


/**
 * @property-read string 	$namespace
 * @property-read string	$language
 * @property-read string	$filePath
 */
class Dictionary
{
	use SmartObject;

	private string $namespace;
	private string $language;
	private string $filePath;
	private int $followings = 0;

	/** @var array<array<string|int|float>|string|int|float> */
	private array $data;


	public function __construct(string $namespace, string $language, string $filePath)
	{
		$this->namespace = $namespace;
		$this->language = $language;
		$this->filePath = $filePath;
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


	public function getNamespace(): string
	{
		return $this->namespace;
	}


	public function getLanguage(): string
	{
		return $this->language;
	}


	public function getFilePath(): string
	{
		return $this->filePath;
	}


	/**
	 * @param Environment $environment
	 * @param array<string> $nodes
	 * @param array<string|int|mixed> $parameters
	 * @param array<array<string|int|float>|string|int|float>|null $entry
	 * @return string|null
	 */
	public function getByNodes(Environment $environment, array $nodes, array $parameters, array $entry = null): ?string
	{
		if($this->followings <= $environment->maxFollowings) {
			if (!$entry) {
				$this->followings = 0;
				$entry = $this->data;
			}

			$node = array_shift($nodes);
			if ($node) {
				if (isset($entry[$node])) {
					if (is_array($entry[$node])) {
						return $this->getByNodes($environment, $nodes, $parameters, $entry[$node]);
					} else if (is_string($entry[$node])) {
						$message = $entry[$node];
						$followMask = "#^{$environment->followSymbol}#";
						if (preg_match($followMask, $message)) {
							$this->followings++;

							if($environment->delimiter === '') {
								throw new InvalidArgumentException('Delimiter must be non-empty string.');
							}

							$followNodes = explode($environment->delimiter, strval(preg_replace($followMask, '', $message)));
							return $this->getByNodes($environment, $nodes ? array_merge($followNodes, $nodes) : $followNodes, $parameters, $this->data);
						}

						return empty($parameters) ? strval($entry[$node]) : strval(call_user_func_array('sprintf', array_merge([$entry[$node]], $parameters)));

					} else {
						throw new InvalidArgumentException(sprintf('Node value is not supported: %s', var_export($entry[$node], true)));
					}
				}
			} else if(!empty($parameters)) {
				$node = $parameters[0];
				if(isset($entry[$node])) {
					$parameters[0] = $entry[$node];
					return strval(call_user_func_array('sprintf', $parameters));

				} else if(isset($entry[$environment->placeholder])) {
					return strval(call_user_func_array('sprintf', array_merge([$entry[$environment->placeholder]], $parameters)));
				}
			}
		} else {
			throw new InvalidArgumentException(sprintf('Max. followings limit exceeded at nodes: %s', var_export($nodes, true)));
		}

		$this->followings = 0;
		return null;
	}
}