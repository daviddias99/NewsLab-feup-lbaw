<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        if($exception instanceof HttpException && $exception->getStatusCode() >= 500 && $exception->getStatusCode() < 600){
            $msg = $exception->getMessage();
            $trace = $exception->getTraceAsString();
            $folder = 'logs';
            if(!file_exists($folder))
                mkdir($folder);

            $file = $folder . '/log_' . date('Y-M-d') . '.log';
            error_log($msg.PHP_EOL.$trace.PHP_EOL, 3, $file);
        }

        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        if ($exception instanceof AuthorizationException && !Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    "message" => "Authentication needed"
                ], 401);
            }
            return abort('401');
        }
        else if ($exception instanceof AuthorizationException){

            if ($request->expectsJson()) {
                return response()->json([
                    "message" => "Not allowed"
                ], 403);
            }
            return abort('403');
        }
        else if($exception instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException){
            if ($request->expectsJson()) {
                return response()->json([
                    "message" => "Not found"
                ], 404);
            }
            return abort('404');
        }
        else if ($this->isHttpException($exception) && $exception->getStatusCode() == 400) {
            if ($request->expectsJson()) {
                return response()->json([
                    "message" => $exception->getMessage()
                ], $exception->getStatusCode());
            }
            return response()->view("errors.400", ['exception' => $exception]);
        }
        else if($this->isHttpException($exception) && $exception->getStatusCode() >= 500 && $exception->getStatusCode() < 600){
            if ($request->expectsJson()) {
                return response()->json([
                    "message" => $exception->getMessage()
                ], $exception->getStatusCode());
            }
            return response()->view("errors.500", ['exception' => $exception]);
        }

        return parent::render($request, $exception);
    }
}
