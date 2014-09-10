<?php
namespace GuzzleHttp\Stream;

/**
 * Represents an asynchronous read-only stream that supports a drain event and
 * pumping data from a source stream.
 */
class AsyncReadStream implements StreamInterface
{
    use StreamDecoratorTrait;

    /** @var callable|null */
    private $drain;

    /** @var callable|null */
    private $pump;

    /** @var int|null */
    private $hwm;

    /** @var bool */
    private $needsDrain;

    /** @var int */
    private $size;

    /**
     * This class accepts an associative array of configuration options.
     *
     * - drain: (callable) Function to invoke when the stream has drained,
     *   meaning the buffer is now writable again because the size of the
     *   buffer is at an acceptable level (e.g., below the high water mark).
     *   The function accepts a single argument, the buffer stream object that
     *   has drained.
     * - pump: (callable) A function that accepts the number of bytes to read
     *   from the source stream. This function will block until all of the data
     *   that was requested has been read, EOF of the source stream, or the
     *   source stream is closed.
     * - size: (int) The expected size in bytes of the data that will be read
     *   (if known up-front).
     *
     * The provided stream must answer to the "hwm" stream metadata variable,
     * providing the high water mark.
     *
     * @param StreamInterface $buffer   Buffer that contains the data that has
     *                                  been read by the event loop.
     * @param array           $config   Associative array of options.
     *
     * @throws \InvalidArgumentException if the buffer is not readable and
     *                                   writable.
     */
    public function __construct(
        StreamInterface $buffer,
        array $config = []
    ) {
        if (!$buffer->isReadable() || !$buffer->isWritable()) {
            throw new \InvalidArgumentException(
                'Buffer must be readable and writable'
            );
        }

        if ($this->hwm = $buffer->getMetadata('hwm') === null) {
            throw new \InvalidArgumentException('Buffer does not provide an '
                . 'hwm metadata value');
        }

        if (isset($config['size'])) {
            $this->size = $config['size'];
        }

        static $callables = ['pump', 'drain'];
        foreach ($callables as $check) {
            if (isset($config[$check])) {
                if (!is_callable($config[$check])) {
                    throw new \InvalidArgumentException(
                        $check . ' must be callable'
                    );
                }
                $this->{$check} = $config[$check];
            }
        }

        $this->stream = $buffer;
    }

    /**
     * Factory method used to create new async stream and an underlying buffer
     * if no buffer is provided.
     *
     * This function accepts the same options as AsyncReadStream::__construct,
     * but added the following key value pairs:
     *
     * - buffer: (StreamInterface) Buffer used to buffer data. If none is
     *   provided, a default buffer is created.
     * - hwm: (int) High water mark to use if a buffer is created on your
     *   behalf.
     * - max_buffer: (int) If provided, wraps the utilized buffer in a
     *   DroppingStream decorator to ensure that buffer does not exceed a given
     *   length. When exceeded, the stream will begin dropping data. Set the
     *   max_buffer to 0, to use a NullBuffer which does not store data.
     * - on_write: (callable) A function that is invoked when data is written
     *   to the underlying buffer. The function accepts the buffer as the first
     *   argument, and the data being written as the second.
     * - drain: (callable) See constructor documentation.
     * - pump: (callable) See constructor documentation.
     *
     * @param array $options Associative array of options.
     *
     * @return array Returns an array containing the buffer used to buffer
     *               data, followed by the ready to use AsyncReadStream object.
     */
    public static function create(array $options = [])
    {
        $maxBuffer = isset($options['max_buffer'])
            ? $options['max_buffer']
            : null;

        if ($maxBuffer === 0) {
            $buffer = new NullBuffer();
        } else {
            $hwm = isset($options['hwm']) ? $options['hwm'] : 16384;
            $buffer = isset($options['buffer'])
                ? $options['buffer']
                : new BufferStream($hwm);
        }

        if ($maxBuffer > 0) {
            $buffer = new DroppingStream($buffer, $options['max_buffer']);
        }

        // Call the on_write callback if an on_write function was provided.
        if (isset($options['on_write'])) {
            $onWrite = $options['on_write'];
            $buffer = FnStream::decorate($buffer, [
                'write' => function ($string) use ($buffer, $onWrite) {
                    $result = $buffer->write($string);
                    $onWrite($buffer, $string);
                    return $result;
                }
            ]);
        }

        return [$buffer, new self($buffer, $options)];
    }

    public function getSize()
    {
        return $this->size;
    }

    public function isWritable()
    {
        return false;
    }

    public function write($string)
    {
        return false;
    }

    public function read($length)
    {
        $result = $this->stream->read($length);
        $currentLen = $this->stream->getSize();
        $resultLen = strlen($result);

        if ($this->drain) {
            // If we need to drain, then drain when the buffer is empty.
            if ($this->needsDrain && $currentLen === 0) {
                $this->needsDrain = false;
                call_user_func($this->drain, $this->stream);
            } else {
                // The buffer needs to drain when it hits the high water mark.
                $this->needsDrain = $currentLen - $resultLen >= $this->hwm;
            }
        }

        // If a pump was provided, the buffer is still open, and not enough
        // data was given, then block until the data is provided.
        if ($this->pump && $resultLen < $length && !$this->isDetached()) {
            $result .= call_user_func($this->pump, $length - $resultLen);
        }

        return $result;
    }
}
