<?php

namespace Lmc\Matej\Http\Plugin;

use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Http\Client\Promise\HttpFulfilledPromise;
use Lmc\Matej\Exception\AuthorizationException;
use Lmc\Matej\Exception\RequestException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class ExceptionPluginTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideSuccessStatusCodes
     * @param mixed $statusCode
     */
    public function shouldReturnResponseWhenNoError($statusCode)
    {
        $request = new Request('GET', 'http://foo.com/endpoint');
        $response = new Response($statusCode);
        $next = function (RequestInterface $receivedRequest) use ($request, $response) {
            $this->assertSame($request, $receivedRequest);

            return new HttpFulfilledPromise($response);
        };
        $plugin = new ExceptionPlugin();
        $promise = $plugin->handleRequest($request, $next, function () {
        });
        $this->assertInstanceOf(HttpFulfilledPromise::class, $promise);
    }

    /**
     * @return array[]
     */
    public function provideSuccessStatusCodes()
    {
        return ['HTTP 200' => [StatusCodeInterface::STATUS_OK], 'HTTP 201' => [StatusCodeInterface::STATUS_CREATED]];
    }

    /**
     * @test
     * @dataProvider provideErrorStatusCodes
     * @param mixed $statusCode
     * @param mixed $expectedExceptionClass
     */
    public function shouldThrowExceptionBasedOnStatusCode($statusCode, $expectedExceptionClass)
    {
        $request = new Request('GET', 'http://foo.com/endpoint');
        $response = new Response($statusCode);
        $next = function (RequestInterface $receivedRequest) use ($request, $response) {
            $this->assertSame($request, $receivedRequest);

            return new HttpFulfilledPromise($response);
        };
        $plugin = new ExceptionPlugin();
        $this->expectException($expectedExceptionClass);
        $plugin->handleRequest($request, $next, function () {
        });
    }

    /**
     * @return array[]
     */
    public function provideErrorStatusCodes()
    {
        return ['HTTP 400' => [StatusCodeInterface::STATUS_BAD_REQUEST, RequestException::class], 'HTTP 401' => [StatusCodeInterface::STATUS_UNAUTHORIZED, AuthorizationException::class], 'HTTP 404' => [StatusCodeInterface::STATUS_NOT_FOUND, RequestException::class], 'HTTP 500' => [StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR, RequestException::class], 'Imaginary HTTP 599' => [599, RequestException::class]];
    }
}
