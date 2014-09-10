<?php
namespace GuzzleHttp\Tests\Stream;

use GuzzleHttp\Stream\NullBuffer;

class NullBufferTest extends \PHPUnit_Framework_TestCase
{
    public function testDoesNothing()
    {
        $b = new NullBuffer();
        $this->assertEquals('', $b->read(10));
        $this->assertEquals(4, $b->write('test'));
        $this->assertEquals('', (string) $b);
        $this->assertNull($b->getMetadata('a'));
        $this->assertEquals([], $b->getMetadata());
        $this->assertEquals(0, $b->getSize());
        $this->assertEquals('', $b->getContents());
        $this->assertEquals(0, $b->tell());
        $this->assertFalse($b->flush());

        $this->assertTrue($b->isReadable());
        $this->assertTrue($b->isWritable());
        $this->assertTrue($b->isSeekable());
        $this->assertFalse($b->seek(10));

        $this->assertTrue($b->eof());
        $this->assertFalse($b->isDetached());
        $b->detach();
        $this->assertTrue($b->isDetached());
        $this->assertTrue($b->eof());
        $b->close();
    }
}
