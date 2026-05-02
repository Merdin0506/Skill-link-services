<?php

declare(strict_types=1);

namespace App\Libraries;

use CodeIgniter\Debug\ExceptionHandler;
use CodeIgniter\Debug\ExceptionHandlerInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Exceptions;
use Throwable;

final class BadRequestRedirectHandler implements ExceptionHandlerInterface
{
    private Exceptions $config;

    public function __construct(Exceptions $config)
    {
        $this->config = $config;
    }

    public function handle(
        Throwable $exception,
        RequestInterface $request,
        ResponseInterface $response,
        int $statusCode,
        int $exitCode,
    ): void {
        if ($request instanceof IncomingRequest) {
            $accept = $request->getHeaderLine('accept');

            if (str_contains($accept, 'text/html')) {
                $target = session()->has('user_id') ? site_url('dashboard') : site_url('auth/login');

                $response->setStatusCode(302);
                $response->setHeader('Location', $target);
                $response->setBody('');
                $response->send();

                return;
            }
        }

        (new ExceptionHandler($this->config))->handle($exception, $request, $response, $statusCode, $exitCode);
    }
}
