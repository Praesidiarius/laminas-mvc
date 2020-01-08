<?php

/**
 * @see       https://github.com/laminasframwork/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminasframwork/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminasframwork/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace LaminasTest\Mvc\ResponseSender;

use Laminas\Http\Response;
use Laminas\Mvc\ResponseSender;
use Laminas\Mvc\ResponseSender\SimpleStreamResponseSender;
use Laminas\Stdlib;
use PHPUnit\Framework\TestCase;

use function file_get_contents;
use function fopen;
use function ob_get_clean;
use function ob_start;

/**
 * @covers \Laminas\Mvc\ResponseSender\SimpleStreamResponseSender
 */
class SimpleStreamResponseSenderTest extends TestCase
{
    public function testSendResponseIgnoresInvalidResponseTypes()
    {
        $mockResponse          = $this->getMockForAbstractClass(Stdlib\ResponseInterface::class);
        $mockSendResponseEvent = $this->getSendResponseEventMock($mockResponse);
        $responseSender        = new SimpleStreamResponseSender();
        ob_start();
        $responseSender($mockSendResponseEvent);
        $body = ob_get_clean();
        $this->assertEquals('', $body);
    }

    public function testSendResponseTwoTimesPrintsResponseOnlyOnce()
    {
        $file         = fopen(__DIR__ . '/TestAsset/sample-stream-file.txt', 'rb');
        $mockResponse = $this->createMock(Response\Stream::class);
        $mockResponse->expects($this->once())->method('getStream')->will($this->returnValue($file));
        $mockSendResponseEvent = $this->getSendResponseEventMock($mockResponse);
        $responseSender        = new SimpleStreamResponseSender();
        ob_start();
        $responseSender($mockSendResponseEvent);
        $body     = ob_get_clean();
        $expected = file_get_contents(__DIR__ . '/TestAsset/sample-stream-file.txt');
        $this->assertEquals($expected, $body);

        ob_start();
        $responseSender($mockSendResponseEvent);
        $body = ob_get_clean();
        $this->assertEquals('', $body);
    }

    protected function getSendResponseEventMock($response)
    {
        $mockSendResponseEvent = $this->getMockBuilder(ResponseSender\SendResponseEvent::class)
            ->setMethods(['getResponse'])
            ->getMock();
        $mockSendResponseEvent->expects($this->any())->method('getResponse')->will($this->returnValue($response));
        return $mockSendResponseEvent;
    }
}
