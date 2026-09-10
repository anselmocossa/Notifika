<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Por omissão, um pedido não autenticado sem Accept: application/json
        // é redireccionado para a rota nomeada 'login' — que não existe
        // nesta aplicação (o Filament regista 'filament.admin.auth.login').
        // Isso rebentava com uma RouteNotFoundException (500) em vez de 401
        // em qualquer pedido à API sem token (CA-19). A API nunca redirecciona.
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('api/*') ? null : route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Força JSON em qualquer excepção da API, com ou sem Accept
        // (CA-19): sem isto, um pedido sem esse cabeçalho recebia um 401
        // vazio (noContent) em vez de um corpo JSON.
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            return $request->is('api/*') || $request->expectsJson();
        });
    })->create();
