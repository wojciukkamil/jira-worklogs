<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function boot(): void
    {
        parent::boot();

        // Pobiera parametr z services.yaml i ustawia domyślną strefę dla PHP
        $timezone = $this->getContainer()->getParameter('app.timezone');
        date_default_timezone_set($timezone);
    }
}
