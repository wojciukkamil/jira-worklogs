<?php

namespace App\EventSubscriber;

use App\Entity\MyCustomException;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ExceptionSubscriber implements EventSubscriberInterface
{
    // private $urlGenerator = null;

    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {
        $this->urlGenerator = $urlGenerator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Sprawdź czy to Twój konkretny wyjątek
        if ($exception instanceof Exception) {
            // Zwróć własną odpowiedź HTTP
            // Generowanie adresu URL do strony błędu (np. routingu o nazwie 'app_error')
            $url = $this->urlGenerator->generate('app_error', ['exception' => $exception]);

        }
    }
}
