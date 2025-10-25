<?php

namespace Tests\Unit\Service;

use App\Service\ResponseJSON;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;

class ResponseJSONTest extends TestCase
{
    protected array $statusCode = [100, 101, 200, 201, 202, 203, 204, 205, 206, 300, 301, 302, 303, 304, 305, 306, 307, 400, 401, 402, 403, 404, 405, 406, 407, 408, 409, 410, 411, 412, 413, 414, 415, 416, 417, 419, 422, 429, 500, 501, 502, 503, 504, 505];

    public function test_if_class_could_return_instance(): void
    {
        $instance = \App\Service\ResponseJSON::getInstance();
        $this->assertInstanceOf(\App\Service\ResponseJSON::class, $instance);
    }

    public function test_if_class_return_allowed_status_code_array(): void
    {
        $allowed = ResponseJSON::getAllowedStatus();
        $this->assertEqualsCanonicalizing($allowed, $this->statusCode);
    }

    public function test_if_not_changed_data_is_empty(): void
    {
        $instance = ResponseJSON::getInstance();
        $this->assertEmpty($instance->getData());
    }

    public function test_if_can_set_data(): void
    {
        $instance = new ResponseJSON;
        $data = ['key' => 'value'];
        $instance->setData($data);
        $this->assertEquals($data, $instance->getData());
    }

    public function test_if_can_set_data_from_paginator(): void
    {
        $response = new ResponseJSON;
        $paginator = $this->createMock(\Illuminate\Pagination\LengthAwarePaginator::class);
        $paginator->method('currentPage')->willReturn(1);
        $paginator->method('perPage')->willReturn(10);
        $paginator->method('total')->willReturn(100);
        $paginator->method('lastPage')->willReturn(10);
        $paginator->method('items')->willReturn(['item1', 'item2']);

        $response->setData(ResponseJSON::fromPaginate($paginator, $paginator));

        $data = $response->getData();

        $this->assertEquals(1, $data['current_page']);
        $this->assertEquals(10, $data['per_page']);
        $this->assertEquals(100, $data['total']);
        $this->assertEquals(10, $data['total_pages']);
        $this->assertEqualsCanonicalizing(['item1', 'item2'], $data['data']);
    }

    public function test_if_can_set_data_directly_from_paginate(): void
    {
        $response = new ResponseJSON;
        $paginator = $this->createMock(\Illuminate\Pagination\LengthAwarePaginator::class);
        $paginator->method('currentPage')->willReturn(1);
        $paginator->method('perPage')->willReturn(10);
        $paginator->method('total')->willReturn(100);
        $paginator->method('lastPage')->willReturn(10);
        $paginator->method('items')->willReturn(['item1', 'item2']);

        $response->setPaginatedData($paginator, $paginator);

        $data = $response->getData();

        $this->assertEquals(1, $data['current_page']);
        $this->assertEquals(10, $data['per_page']);
        $this->assertEquals(100, $data['total']);
        $this->assertEquals(10, $data['total_pages']);
        $this->assertEqualsCanonicalizing(['item1', 'item2'], $data['data']);
    }

    public function test_if_message_is_null_by_default(): void
    {
        $instance = ResponseJSON::getInstance();
        $this->assertNull($instance->getMessage());
    }

    public function test_if_can_set_message(): void
    {
        $instance = ResponseJSON::getInstance();
        $message = 'This is a test message.';
        $instance->setMessage($message);
        $this->assertEquals($message, $instance->getMessage());
    }

    public function test_if_can_set_message_using_exception(): void
    {
        $instance = ResponseJSON::getInstance();
        $exception = new \Exception('Error');
        $instance->setMessage($exception);
        $this->assertEquals('Error', $instance->getMessage());
    }

    public function test_if_hide_sensitive_error_message_without_debug_mode(): void
    {
        config(['app.debug' => false]);

        $instance = ResponseJSON::getInstance();
        $exception = new \Exception('Sensitive error message');
        $instance->setError($exception);
        $this->assertEquals('Server Error', $instance->getMessage());
    }

    public function test_if_status_code_is200_by_default(): void
    {
        $response = ResponseJSON::getInstance();
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_if_status_code_will_be_fixed_when_set_wrong(): void
    {
        $response = ResponseJSON::getInstance();
        $response->setStatusCode(999);
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function test_if_can_set_status_code(): void
    {
        $response = ResponseJSON::getInstance();
        $response->setStatusCode(201);
        $this->assertEquals(201, $response->getStatusCode());
    }

    public function test_if_can_set_error_from_exception(): void
    {
        $response = ResponseJSON::getInstance();
        $response->setError(new \Exception('Test exception', 403));
        $this->assertFalse($response->getSuccess());
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('Test exception', $response->getMessage());
    }

    public function test_if_can_set_error_from_exception_forcing_status_code(): void
    {
        $response = ResponseJSON::getInstance();
        $response->setError(new \Exception('Test exception', 403), 500);

        $response->setStatusCode(500);

        $this->assertFalse($response->getSuccess());
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('Test exception', $response->getMessage());
    }

    public function test_if_can_set_error_from_string(): void
    {
        $response = ResponseJSON::getInstance();
        $response->setError('Test exception');

        $response->setStatusCode(500);

        $this->assertFalse($response->getSuccess());
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('Test exception', $response->getMessage());
    }

    public function test_if_errors_is_empty_when_not_set(): void
    {
        $response = ResponseJSON::getInstance();
        $this->assertEmpty($response->getErrors());
    }

    public function test_if_can_set_errors(): void
    {
        $response = ResponseJSON::getInstance();
        $errors = ['field1' => 'error1', 'field2' => 'error2'];
        $response->setErrors($errors);
        $this->assertEquals($errors, $response->getErrors());
    }

    public function test_if_can_remove_numeric_keys_from_data(): void
    {
        $data = [
            10 => 'value1',
            11 => 'value2',
            12 => [
                'key1' => 'teste1',
                'key2' => 'teste2',
            ],
            'key1' => 'value3',
            'key2' => 'value4',
        ];

        ResponseJSON::removeNumericKeys($data);

        $expected = [
            'value1',
            'value2',
            [
                'key1' => 'teste1',
                'key2' => 'teste2',
            ],
            'key1' => 'value3',
            'key2' => 'value4',
        ];

        $this->assertEqualsCanonicalizing($expected, $data);
    }

    public function test_if_remove_numeric_keys_does_not_affect_non_numeric_keys(): void
    {
        $data = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => [
                'subkey1' => 'subvalue1',
                'subkey2' => 'subvalue2',
            ],
        ];

        ResponseJSON::removeNumericKeys($data);

        $expected = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => [
                'subkey1' => 'subvalue1',
                'subkey2' => 'subvalue2',
            ],
        ];

        $this->assertEqualsCanonicalizing($expected, $data);
    }

    public function test_if_remove_numeric_keys_doest_affect_empty_array(): void
    {
        $data = [];

        ResponseJSON::removeNumericKeys($data);

        $expected = [];

        $this->assertEqualsCanonicalizing($expected, $data);
    }

    public function test_if_remove_numeric_keys_doest_affect_non_array(): void
    {
        $data = 'This is a string';

        $result = ResponseJSON::removeNumericKeys($data);

        $this->assertEquals('This is a string', $result);
    }

    public function test_if_hide_numeric_fields_is_false_by_default(): void
    {

        $response = ResponseJSON::getInstance();
        $this->assertFalse($response->getHideNumericIndex());
    }

    public function test_if_can_set_hide_numeric_fields(): void
    {
        $response = ResponseJSON::getInstance();
        $response->setHideNumericIndex(true);
        $this->assertTrue($response->getHideNumericIndex());
    }

    public function test_if_return_json_response(): void
    {
        $response = ResponseJSON::getInstance();
        $response->setData(['key' => 'value']);
        $response->setMessage('Test message');
        $response->setStatusCode(200);

        $jsonResponse = $response->render();

        $this->assertInstanceOf(JsonResponse::class, $jsonResponse);
        $this->assertEquals(200, $jsonResponse->getStatusCode());
        $this->assertEquals($jsonResponse->getData(true), [
            'success' => true,
            'message' => 'Test message',
            'errors' => [],
            'error_code' => null,
            'data' => ['key' => 'value'],
        ]);
    }

    public function test_if_can_pre_render(): void
    {
        $response = ResponseJSON::getInstance();
        $response->setData(['key' => 'value']);
        $response->setMessage('Test message');
        $response->setStatusCode(200);

        $preRendered = $response->preRender();

        $this->assertIsArray($preRendered);
        $this->assertEquals([
            'success' => true,
            'message' => 'Test message',
            'errors' => [],
            'error_code' => null,
            'data' => ['key' => 'value'],
        ], $preRendered);
    }

    public function test_if_can_render_without_data(): void
    {
        $response = ResponseJSON::getInstance();
        $response->setData(['key' => 'value']);
        $response->setMessage('Test message');
        $response->setStatusCode(200);

        $jsonResponse = $response->renderWithoutData();

        $this->assertInstanceOf(JsonResponse::class, $jsonResponse);
        $this->assertEquals(200, $jsonResponse->getStatusCode());
        $this->assertEquals($jsonResponse->getData(true), [
            'success' => true,
            'message' => 'Test message',
            'errors' => [],
            'error_code' => null,
        ]);
    }
}
