<?php

namespace LasseRafn\Dinero\Tests\Responses;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use LasseRafn\Dinero\Builders\ContactBuilder;
use LasseRafn\Dinero\Exceptions\DineroRequestException;
use LasseRafn\Dinero\Exceptions\DineroServerException;
use LasseRafn\Dinero\Requests\ContactRequestBuilder;
use LasseRafn\Dinero\Tests\TestCase;
use LasseRafn\Dinero\Utils\Request;

class PaginatedResponseTest extends TestCase
{
    /** @test */
    public function can_return_a_paginated_response_on_get()
    {
        $expectedResponse = [
            'Collection' => [[]],
            'Pagination' => [
                'Page'                => 1,
                'PageSize'            => 50,
                'MaxPageSizeAllowed'  => 1000,
                'Result'              => 5,
                'ResultWithoutFilter' => 10,
            ],
        ];

        $mock = new MockHandler([
            new Response(200, [], json_encode($expectedResponse)),
        ]);

        $handler = HandlerStack::create($mock);

        $builder = new ContactRequestBuilder(new ContactBuilder(new Request('', '', null, null, ['handler' => $handler])));

        $response = $builder->get();

        $this->assertCount(1, $response->items);
        $this->assertEquals(1, $response->page);
        $this->assertEquals(50, $response->pageSize);
        $this->assertEquals(1000, $response->maxPageSizeAllowed);
        $this->assertEquals(5, $response->result);
        $this->assertEquals(10, $response->resultWithoutFilter);
    }

    /** @test */
    public function can_fail_to_get_a_response_because_of_server_error()
    {
        $this->expectException(DineroServerException::class);
        $mock = new MockHandler([
            new Response(503, [], json_encode(['error' => 'Server exploded.'])),
        ]);

        $handler = HandlerStack::create($mock);

        $builder = new ContactRequestBuilder(new ContactBuilder(new Request('', '', null, null, ['handler' => $handler])));

        $builder->get();
    }

    /** @test */
    public function can_fail_to_get_a_response_because_of_input_error()
    {
        $this->expectException(DineroRequestException::class);
        $mock = new MockHandler([
            new Response(403, [], json_encode(['error' => 'No access.'])),
        ]);

        $handler = HandlerStack::create($mock);

        $builder = new ContactRequestBuilder(new ContactBuilder(new Request('', '', null, null, ['handler' => $handler])));

        $builder->get();
    }
}
