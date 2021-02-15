<?php

namespace Mbrianp\FuncCollection\Logic;

use Mbrianp\FuncCollection\Http\Response;
use Mbrianp\FuncCollection\View\TemplateManager;

abstract class AbstractController
{
    public function __construct(private string $templates_dir)
    {
    }

    public function render(string $file, array $variables = [], Response $response = null): Response
    {
        $vm = new TemplateManager($this->templates_dir);

        if (null == $response) {
            $response = new Response();
        }

        $response->response = $vm->render($file, $variables);

        return $response;
    }
}