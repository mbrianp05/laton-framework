<?php

namespace App\classes\View;

use Mbrianp\FuncCollection\View\TemplateManager;

class ViewHelper
{
    public static function include(string $filename, array $variables = []): string
    {
        $templateManager = new TemplateManager(dirname(dirname(__DIR__)));

        return $templateManager->render($filename, $variables);
    }
}