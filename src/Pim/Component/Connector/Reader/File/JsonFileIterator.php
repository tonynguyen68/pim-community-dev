<?php

namespace Pim\Component\Connector\Reader\File;

use Akeneo\Component\Batch\Item\InvalidItemException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Finder\Finder;

/**
 * Class JsonFileIterator
 *
 * @author    Alexandre Hocquard <alexandre.hocquard@akeneo.com>
 * @copyright 2016 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class JsonFileIterator implements FileIteratorInterface
{
    /** @var string */
    protected $type;

    /** @var string */
    protected $filePath;

    /** @var \SplFileInfo */
    protected $fileInfo;

    /** @var array */
    protected $headers;

    /** @var array */
    protected $products;

    /** @var integer */
    protected $cursor = 0;

    /**
     * @param string $type
     * @param string $filePath
     * @param array  $options
     *
     * @throws UnsupportedTypeException
     * @throws FileNotFoundException
     */
    public function __construct($type, $filePath, array $options = [])
    {
        $this->type = $type;
        $this->filePath = $filePath;
        $this->fileInfo = new \SplFileInfo($filePath);

        if (!$this->fileInfo->isFile()) {
            throw new FileNotFoundException(sprintf('File "%s" could not be found', $this->filePath));
        }

        $this->products = json_decode(file_get_contents($filePath), true);
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->cursor = -1;
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidItemException
     */
    public function current()
    {
        return $this->products[$this->cursor];
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->cursor++;
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->products[$this->cursor]['identifier'];
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        $cursor = $this->cursor;

        return $cursor+=1 < count($this->products);
    }

    /**
     * {@inheritdoc}
     */
    public function getDirectoryPath()
    {
        return $this->fileInfo->getPath();
    }

    /**
     * Close reader and remove folder created when archive has been extracted
     */
    public function __destruct()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders()
    {
        return $this->headers;
    }
}
