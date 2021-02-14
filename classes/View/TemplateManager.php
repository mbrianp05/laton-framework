<?php

namespace Mbrianp\FuncCollection\View;

class TemplateManager
{
    public function __construct(
        protected string $templatesFile = '',
    )
    {
    }

    public function render(string $file, array $variables = []): string
    {
        foreach ($variables as $variable => $value) {
            $$variable = $value;
        }

        ob_start();
        require_once $this->templatesFile . '/' . $file;

        return ob_get_clean();
    }
}