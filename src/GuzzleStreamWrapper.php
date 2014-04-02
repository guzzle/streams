<?php

namespace GuzzleHttp\Stream;

/**
 * PHP stream wrapper implementation.
 */
class GuzzleStreamWrapper
{
    /** @var StreamInterface[] */
    private static $streams = array();

    /** @var StreamInterface */
    protected $stream;

    /**
     * Returns a resource representing the stream.
     *
     * @param StreamInterface $stream The stream to get a resource for
     *
     * @return resource
     */
    public static function getResource(StreamInterface $stream)
    {
        if (!in_array('guzzle', stream_get_wrappers())) {
            stream_wrapper_register('guzzle', get_called_class());
        }
        $hash = spl_object_hash($stream);
        self::$streams[$hash] = $stream;

        if ($stream->isReadable()) {
            $mode = $stream->isWritable() ? 'r+' : 'r';
        } elseif ($stream->isWritable()) {
            $mode = 'w';
        } else {
            throw new \RuntimeException('The stream must be readable, writable, or both.');
        }

        return fopen('guzzle://' . $hash, $mode);
    }

    public function stream_open($path, $mode, $options, &$opened_path)
    {
        list(, $hash) = explode('://', $path);

        if (isset(self::$streams[$hash])) {
            $this->stream = self::$streams[$hash];
            return true;
        }

        return false;
    }

    public function stream_read($count)
    {
        return $this->stream->read($count);
    }

    public function stream_write($data)
    {
        return (int) $this->stream->write($data);
    }

    public function stream_close()
    {
        unset(self::$streams[spl_object_hash($this->stream)]);
    }

    public function stream_tell()
    {
        return $this->stream->tell();
    }

    public function stream_eof()
    {
        return $this->stream->eof();
    }

    public function stream_seek($offset, $whence)
    {
        return $this->stream->seek($offset, $whence);
    }

    public function stream_metadata($path, $option, $var)
    {
        return false;
    }
}
