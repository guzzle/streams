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
        return fopen('guzzle://' . static::registerStream($stream), 'r+');
    }

    /**
     * Registers a stream to be used as a PHP stream wrapper.
     *
     * @param StreamInterface $stream A stream
     *
     * @return string                 The hash of the stream
     */
    public static function registerStream(StreamInterface $stream)
    {
        if (!in_array('guzzle', stream_get_wrappers())) {
            stream_wrapper_register('guzzle', get_called_class());
        }
        $hash = spl_object_hash($stream);
        self::$streams[$hash] = $stream;

        return $hash;
    }

    /**
     * Unregisters a stream.
     *
     * @param StreamInterface $stream A stream
     */
    public static function unregisterStream(StreamInterface $stream)
    {
        unset(self::$streams[spl_object_hash($stream)]);
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
        static::unregisterStream($this->stream);
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
