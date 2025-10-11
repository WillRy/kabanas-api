<?php

namespace App\Http\Middleware;

use App\Service\ResponseJSON;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FormatJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Só intercepta JSON
        if ($response instanceof JsonResponse) {
            $original = $response->getData(true);

            $withoutDefaultKeys = array_diff_key($original, array_flip(['message', 'data', 'errors', 'exception']));

            $responseClass = ResponseJSON::getInstance();

            $formatted = [
                'success' => $response->getStatusCode() >= 200 && $response->getStatusCode() < 300,
                'message' => $original['message'] ?? null,
                'data' => $original['data'] ?? $withoutDefaultKeys,
                'errors' => $original['errors'] ?? [],
                'error_code' => ! empty($response->exception) ? class_basename($response->exception) : null,
            ];

            $exceptionStatusCode = ! empty($response->exception) ? $response->exception->status ?? $response->exception->getCode() : null;

            $statusCode = $exceptionStatusCode && in_array($exceptionStatusCode, ResponseJSON::getAllowedStatus()) ? $exceptionStatusCode : $response->getStatusCode();

            $responseClass->setMessage($formatted['message']);
            $responseClass->setData($formatted['data']);
            $responseClass->setErrors($formatted['errors']);

            if (! empty($response->exception)) {
                $responseClass->setError($response->exception);
            }

            $responseClass->setStatusCode($statusCode);

            $response->setData($responseClass->preRender());

            $response->setStatusCode($responseClass->getStatusCode());

        }

        return $response;
    }
}
