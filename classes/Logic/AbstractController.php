<?php

namespace Mbrianp\FuncCollection\Logic;

use Mbrianp\FuncCollection\Http\Response;
use Mbrianp\FuncCollection\View\TemplateManager;

abstract class AbstractController
{
    public function render(string $file, array $variables = [], Response $response = null): Response
    {
        $vm = new TemplateManager(dirname(dirname(__DIR__)) . '/templates');

        if (null == $response) {
            $response = new Response();
        }

        $response->response = $vm->render($file, $variables);

        return $response;
    }
}