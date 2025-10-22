<?php

namespace App\Service;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class ResponseJSON
{
    protected bool $success = true;

    protected mixed $data = [];

    protected ?string $message = null;

    /** @var array<mixed> */
    protected ?array $errors = [];

    protected bool $hideNumericIndex = false;

    protected int $statusCode = 200;

    protected ?string $errorCode = null;

    public static function getInstance(): ResponseJSON
    {
        return new ResponseJSON;
    }

    public function getSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @return array<int>
     */
    public static function getAllowedStatus(): array
    {
        return [100, 101, 200, 201, 202, 203, 204, 205, 206, 300, 301, 302, 303, 304, 305, 306, 307, 400, 401, 402, 403, 404, 405, 406, 407, 408, 409, 410, 411, 412, 413, 414, 415, 416, 417, 419, 422, 429, 500, 501, 502, 503, 504, 505];
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function setData(mixed $data): ResponseJSON
    {
        $this->data = $data;

        return $this;
    }

    public function setPaginatedData(LengthAwarePaginator $paginator, mixed $data = []): ResponseJSON
    {
        $this->data = self::fromPaginate($paginator, $data);

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string|\Exception|null $message = null): ResponseJSON
    {
        if ($message instanceof \Exception) {
            $this->message = $message->getMessage();
        } else {
            $this->message = $message;
        }

        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setStatusCode(int $statusCode): ResponseJSON
    {
        if (in_array($statusCode, self::getAllowedStatus())) {
            $this->statusCode = $statusCode;
        } else {
            $this->statusCode = 500;
        }

        return $this;
    }

    public function setError(string|\Exception|null $error = null, ?int $forceStatusCode = null): ResponseJSON
    {
        $this->success = false;
        $this->statusCode = 500;

        // @phpstan-ignore-next-line
        $isException = $error instanceof \Exception || $error instanceof \Throwable;

        if ($error instanceof \Exception) {
            $this->message = $error->getMessage();
            $this->statusCode = in_array($error->getCode(), self::getAllowedStatus()) ? $error->getCode() : 500;
            $this->errorCode = $isException ? class_basename($error) : $this->statusCode;
        } else {
            $this->message = $error;
        }

        if ($forceStatusCode) {
            $this->statusCode = $forceStatusCode;
        }

        Log::error($this->message, [
            'error' => $error,
            'code' => $this->statusCode,
            'trace' => $isException ? $error->getTrace() : null,
        ]);

        /**
         * Ocultar exceptions de erro 500, não lançadas por nós, para ocultar dados sensíveis de SQL
         */
        $trace = $isException ? $error->getTrace() : null;
        if (! empty($trace)) {
            $origem = $trace[0];

            $incluiNamespaceApp = str_contains($origem['class'] ?? '', 'App\\');
            if (isset($origem['class']) && $incluiNamespaceApp) {
                $this->message = $error->getMessage();
            } elseif ($this->statusCode == 500 && ! config('app.debug')) {
                $this->message = 'Server Error';
            }
        }

        return $this;
    }

    public function setErrors($errors = []): ResponseJSON
    {
        if (! empty($errors)) {
            $this->success = false;
        }

        $this->errors = $errors ?? [];

        return $this;
    }

    public function getErrors(): ?array
    {
        return $this->errors;
    }

    public static function removeNumericKeys(&$array): mixed
    {
        if (! is_array($array)) {
            return $array;
        }

        if (empty($array)) {
            return $array;
        }

        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                self::removeNumericKeys($value);
                $array[$key] = $value;
            }
        }

        $keys = array_keys($array);

        if (is_numeric($keys[0])) {
            $array = array_values($array);
        }

        return $array;
    }



    /**
     * Esconde indices númericos dos arrays, principalmente
     * em agrupamentos de dados
     */
    public function sethideNumericIndex(bool $hideNumericIndex): ResponseJSON
    {
        $this->hideNumericIndex = $hideNumericIndex;

        return $this;
    }

    public function getHideNumericIndex(): bool
    {
        return $this->hideNumericIndex;
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => $this->success,
            'message' => $this->message,
            'errors' => $this->errors,
            'error_code' => $this->errorCode,
            'data' => $this->data,
        ], $this->statusCode);
    }

    public function preRender(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'errors' => $this->errors,
            'error_code' => $this->errorCode,
            'data' => $this->data,
        ];
    }

    public static function fromPaginate(LengthAwarePaginator $paginatedQuery, mixed $data = []): array
    {
        $dados = [];

        if ($data instanceof JsonResource) {
            $dados = $data->toArray(request());
        }

        if ($data instanceof LengthAwarePaginator) {
            $dados = $data->items();
        }

        return [
            'current_page' => $paginatedQuery->currentPage(),
            'last_page' => $paginatedQuery->lastPage(),
            'per_page' => $paginatedQuery->perPage(),
            'total' => $paginatedQuery->total(),
            'total_pages' => $paginatedQuery->lastPage(),
            'data' => $dados,
        ];
    }

    public function renderWithoutData(): JsonResponse
    {
        return response()->json([
            'success' => $this->success,
            'message' => $this->message,
            'errors' => $this->errors,
            'error_code' => $this->errorCode,
        ], $this->statusCode);
    }
}
