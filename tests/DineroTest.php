<?php

namespace LasseRafn\Dinero\Tests;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use LasseRafn\Dinero\Dinero;
use LasseRafn\Dinero\Exceptions\DineroRequestException;
use LasseRafn\Dinero\Exceptions\DineroServerException;
use LasseRafn\Dinero\Requests\ContactRequestBuilder;
use LasseRafn\Dinero\Requests\CreditnoteRequestBuilder;
use LasseRafn\Dinero\Requests\InvoiceRequestBuilder;
use LasseRafn\Dinero\Requests\ProductRequestBuilder;

class DineroTest extends TestCase
{
    /** @test */
    public function can_set_auth_info()
    {
        $dinero = new Dinero('clientId', 'clientSecret');

        $this->assertEquals(null, $dinero->getAuthToken());
        $this->assertEquals(null, $dinero->getOrgId());

        $dinero->setAuth('foo', 'bar');

        $this->assertSame('foo', $dinero->getAuthToken());
        $this->assertSame('bar', $dinero->getOrgId());
    }

    /** @test */
    public function can_auth_with_the_api()
    {
        $expectedResponse = [
            'access_token'  => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9(...)',
            'token_type'    => 'Bearer',
            'expires_in'    => 3600,
            'refresh_token' => null,
        ];

        $mock = new MockHandler([
            new Response(200, [], json_encode($expectedResponse)),
        ]);

        $handler = HandlerStack::create($mock);

        $dinero = new Dinero('clientId', 'clientSecret', null, null, ['handler' => $handler]);

        $this->assertNull($dinero->getOrgId());
        $this->assertNull($dinero->getAuthToken());

        $authResponse = $dinero->auth('foo', 'bar');

        $this->assertSame($expectedResponse['access_token'], $authResponse->access_token);
        $this->assertSame($expectedResponse['token_type'], $authResponse->token_type);
        $this->assertSame($expectedResponse['expires_in'], $authResponse->expires_in);
        $this->assertSame($expectedResponse['refresh_token'], $authResponse->refresh_token);

        $this->assertSame($expectedResponse['access_token'], $dinero->getAuthToken());
        $this->assertSame('bar', $dinero->getOrgId());
    }

    /** @test */
    public function can_return_builders()
    {
        $dinero = new Dinero('clientId', 'clientSecret');

        $this->assertSame(ContactRequestBuilder::class, get_class($dinero->contacts()));
        $this->assertSame(InvoiceRequestBuilder::class, get_class($dinero->invoices()));
        $this->assertSame(CreditnoteRequestBuilder::class, get_class($dinero->creditnotes()));
        $this->assertSame(ProductRequestBuilder::class, get_class($dinero->products()));
    }

    /** @test */
    public function can_get_auth_url()
    {
        $dinero = new Dinero('clientId', 'clientSecret');

        $this->assertSame('https://authz.dinero.dk/dineroapi/oauth/token', $dinero->getAuthUrl());
    }

    /** @test */
    public function can_fail_to_auth_with_the_api_because_of_invalid_data()
    {
        $this->expectException(DineroRequestException::class);

        $expectedResponse = [
            'error' => 'invalid_client',
        ];

        $mock = new MockHandler([
            new Response(401, [], json_encode($expectedResponse)),
        ]);

        $handler = HandlerStack::create($mock);

        $dinero = new Dinero('clientId', 'clientSecret', null, null, ['handler' => $handler]);

        $dinero->auth('foo', 'bar');
    }

    /** @test */
    public function can_fail_to_auth_with_the_api_because_of_server_problems()
    {
        $this->expectException(DineroServerException::class);

        $expectedResponse = [
            'error' => 'A server error occured. No more information about this error could be found.',
        ];

        $mock = new MockHandler([
            new Response(503, [], json_encode($expectedResponse)),
        ]);

        $handler = HandlerStack::create($mock);

        $dinero = new Dinero('clientId', 'clientSecret', null, null, ['handler' => $handler]);

        $dinero->auth('foo', 'bar');
    }
}
