<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ApiExceptionListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.exception' => 'onKernelException',
            'lexik_jwt_authentication.on_authentication_failure' => 'onAuthenticationFailure',
        ];
    }

    public function onAuthenticationFailure(AuthenticationFailureEvent $event): void
    {
        $exception = $event->getException();
        $previous = $exception->getPrevious();

        // On récupère le message de l'exception (direct ou parent)
        $message = $previous ? $previous->getMessage() : $exception->getMessage();

        // Si LexikJWT s'apprête à renvoyer notre clé technique
        if ($message === 'ERR_API_ACCESS_DENIED' || str_contains($message, 'ERR_API_ACCESS_DENIED')) {

            // On court-circuite sa réponse 401 pour imposer notre 403
            $response = new JsonResponse([
                'code' => 403,
                'message' => "Accès refusé : votre compte n'est pas autorisé à utiliser l'API."
            ], 403);

            $event->setResponse($response);
        }
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $exception = $event->getThrowable();
        $statusCode = JsonResponse::HTTP_INTERNAL_SERVER_ERROR;
        $message = $exception->getMessage();

        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
        } elseif (str_contains(get_class($exception), 'Security')) {
            $statusCode = JsonResponse::HTTP_UNAUTHORIZED;
        }

        $response = new JsonResponse(['code' => $statusCode, 'message' => $message], $statusCode);
        $event->setResponse($response);
    }
}
