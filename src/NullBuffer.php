<?php
namespace GuzzleHttp\Stream;

/**
 * Does not store any data written to it.
 */
class NullBuffer implements StreamInterface
{
    private $detached = false;

    public function __toString()
    {
        return '';
    }

    public function getContents()
    {
        return '';
    }

    public function close() {}

    public function detach() {
        $this->detached = true;
    }

    public function isDetached()
    {
        return $this->detached;
    }

    public function getSize()
    {
        return 0;
    }

    public function isReadable()
    {
        return true;
    }

    public function isWritable()
    {
        return true;
    }

    public function isSeekable()
    {
        return true;
    }

    public function eof()
    {
        return true;
    }

    public function tell()
    {
        return 0;
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        return false;
    }

    public function read($length)
    {
        return false;
    }

    public function write($string)
    {
        return strlen($string);
    }

    public function flush()
    {
        return false;
    }

    public function getMetadata($key = null)
    {
        if ($key == 'hwm') {
            return 0;
        } elseif ($key) {
            return null;
        } else {
            return [];
        }
    }
}
