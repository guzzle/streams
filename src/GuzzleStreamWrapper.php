<?php

namespace GuzzleHttp\Stream;

/**
 * PHP stream implementation
 */
class GuzzleStreamWrapper
{

    public static $streams = array();

    public function stream_open($path, $mode, $options, &$opened_path)
    {
        list(, $hash) = explode('://', $path);
        $this->stream = static::$streams[$hash];

        return true;
    }

    public function stream_read($count)
    {
        return $this->stream->read($count);
    }

    public function stream_write($data)
    {
        return $this->stream->write($data);
    }

    function stream_tell()
    {
        return $this->stream->tell();
    }

    function stream_eof()
    {
        return $this->stream->eof();
    }

    function stream_seek($offset, $whence)
    {
        return $this->stream->seek($offset, $whence);
    }

    function stream_metadata($path, $option, $var)
    {
        return false;
    }
}
