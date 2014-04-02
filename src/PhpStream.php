<?php

namespace GuzzleHttp\Stream;

/**
 * PHP stream implementation
 */
class PhpStream implements MetadataStreamInterface
{
    use StreamDecoratorTrait;

    /**
     * Returns a resource representing the stream.
     *
     * @return resource
     */
    public function getResource()
    {
        if (!in_array('guzzle', stream_get_wrappers())) {
            stream_wrapper_register('guzzle', 'GuzzleHttp\Stream\GuzzleStreamWrapper');
        }

        $hash = spl_object_hash($this);

        GuzzleStreamWrapper::$streams[$hash] = $this;

        return fopen('guzzle://' . $hash, 'r+');
    }

    /**
     * Unregisters the stream when the destructed.
     */
    public function __destruct()
    {
        unset(GuzzleStreamWrapper::$streams[spl_object_hash($this)]);
        $this->close();
    }

}
