<?php

namespace App\Form;

use Symfony\Bridge\Twig\Form\TwigRendererEngine as BaseTwigRendererEngine;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;
use Twig\Template;

/**
 * Workaround for twig/twig 3.29, where TemplateWrapper::unwrap() requires the
 * Environment argument. symfony/twig-bridge <= 8.1.7 still calls it without
 * one, which breaks every form_* function.
 *
 * Unwrapping the theme here makes the parent skip its broken call.
 * Remove this class once twig-bridge passes the environment itself.
 */
#[AsDecorator('twig.form.engine')]
class TwigRendererEngine extends BaseTwigRendererEngine
{
    public function __construct(
        #[Autowire(param: 'twig.form.resources')]
        array $defaultThemes,
        private readonly Environment $twig,
    ) {
        parent::__construct($defaultThemes, $twig);
    }

    protected function loadResourcesFromTheme(string $cacheKey, mixed &$theme): void
    {
        if (!$theme instanceof Template) {
            $theme = $this->twig->load($theme)->unwrap($this->twig);
        }

        parent::loadResourcesFromTheme($cacheKey, $theme);
    }
}
