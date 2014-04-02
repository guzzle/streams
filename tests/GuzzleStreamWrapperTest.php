<?php

namespace GuzzleHttp\Tests\Stream;

use GuzzleHttp\Stream\GuzzleStreamWrapper;
use GuzzleHttp\Stream\Stream;

/**
 * @covers GuzzleHttp\Stream\GuzzleStreamWrapper
 */
class GuzzleStreamWrapperTest extends \PHPUnit_Framework_TestCase
{
    public function testResource()
    {
        $stream = Stream::factory('foo');
        $handle = GuzzleStreamWrapper::getResource($stream);
        $this->assertSame('foo', fread($handle, 3));

        $this->assertSame(3, ftell($handle));

        $this->assertSame(3, fwrite($handle, 'bar'));

        $this->assertSame(0, fseek($handle, 0));
        $this->assertSame('foobar', fread($handle, 6));

        $this->assertTrue(feof($handle));

        $this->assertTrue(fclose($handle));

        $this->assertSame('foobar', (string) $stream);
    }

}
