<?php

declare(strict_types=1);

namespace Flexsyscz\Localization\Bridges\LocalizationTracy;

use Flexsyscz\Localization\Translations\Repository;
use Flexsyscz\Localization\Translations\Translator;
use Latte\Engine;
use Tracy\IBarPanel;


class TranslationPanel implements IBarPanel
{
	public function __construct(
		private readonly Translator $translator,
		private readonly Repository $repository,
	)
	{
	}


	public function getTab(): string
	{
		$template = new Engine;
		return $template->renderToString(__DIR__ . '/templates/tab.latte');
	}


	public function getPanel(): string
	{
		$template = new Engine;
		return $template->renderToString(__DIR__ . '/templates/panel.latte', [
			'translator' => $this->translator,
			'repository' => $this->repository,
		]);
	}
}
