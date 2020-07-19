<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExceptionListener
{
	public function onKernelException(GetResponseForExceptionEvent $event)
	{
		$exception = $event->getException();
		if ($exception instanceof HttpExceptionInterface)
			$code = $exception->getStatusCode();
		else
			$code = 500;
		$response = new JsonResponse([
			'code' => $code,
			'message' => $exception->getMessage()
		], $code);	

		$event->setResponse($response);
	}
}
