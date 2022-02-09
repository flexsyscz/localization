<?php

declare(strict_types=1);

namespace Flexsyscz\Localization\Bridges\LocalizationTracy;

use Flexsyscz\Localization\DictionariesRepository;
use Flexsyscz\Localization\Translator;
use Latte\Engine;
use Tracy\IBarPanel;


class TranslationPanel implements IBarPanel
{
	private Translator $translator;
	private DictionariesRepository $dictionariesRepository;


	public function __construct(Translator $translator, DictionariesRepository $dictionariesRepository)
	{
		$this->translator = $translator;
		$this->dictionariesRepository = $dictionariesRepository;
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
			'dictionariesRepository' => $this->dictionariesRepository,
		]);
	}
}
