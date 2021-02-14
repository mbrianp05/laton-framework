<?php

namespace Mbrianp\FuncCollection\DIC;

class DIC
{
    /**
     * @var Service[]
     */
    private array $services;

    private array $instantiatedServices = [];

    /**
     * @param array<int, Service> $services
     */
    public function __construct(array $services = [])
    {
        foreach ($services as $service) {
            $this->addService($service);
        }
    }

    public function addService(Service $service): void
    {
        $this->services[$service->id] = $service;
    }

    public function getService(string $id): object
    {
        if (!isset($this->instantiatedServices[$id])) {
            $service['class'] = $this->services[$id]->class;
            $service['params'] = $this->services[$id]->params;

            $service = new $service['class'](...$service['params']);
            $this->instantiatedServices[$id] = $service;

            return $service;
        }

        return $this->instantiatedServices[$id];
    }
}