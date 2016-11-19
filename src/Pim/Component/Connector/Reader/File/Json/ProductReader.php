<?php

namespace Pim\Component\Connector\Reader\File\Json;

use Akeneo\Component\Batch\Item\FlushableInterface;
use Akeneo\Component\Batch\Item\ItemReaderInterface;
use Akeneo\Component\Batch\Step\StepExecutionAwareInterface;
use Pim\Component\Connector\Reader\File\FileIteratorFactory;

/**
 * Product csv reader
 *
 * This specialized csv reader exists to replace relative media path to absolute path, in order for later process to
 * know where to find the files.
 *
 * @author    Gildas Quemener <gildas@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductReader implements ItemReaderInterface, StepExecutionAwareInterface, FlushableInterface
{
    /** @var FileIteratorFactory */
    protected $fileIteratorFactory;

    /** @var FileIteratorInterface */
    protected $fileIterator;

    /** @var StepExecution */
    protected $stepExecution;

    /** @var array */
    protected $options;

    /**
     * @param FileIteratorFactory     $fileIteratorFactory
     * @param array                   $options
     */
    public function __construct(
        FileIteratorFactory $fileIteratorFactory,
        array $options = []
    ) {
        $this->fileIteratorFactory = $fileIteratorFactory;
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function read()
    {
        if (null === $this->fileIterator) {
            $jobParameters = $this->stepExecution->getJobParameters();
            $filePath = $jobParameters->get('filePath');
            $this->fileIterator = $this->fileIteratorFactory->create(
                $filePath,
                $this->options
            );
            $this->fileIterator->rewind();
        }

        $this->fileIterator->next();

        if ($this->fileIterator->valid() && null !== $this->stepExecution) {
            $this->stepExecution->incrementSummaryInfo('item_position');
        }

        if(!$this->fileIterator->valid()) {
            return null;
        }

        $standardProduct = $this->fileIterator->current();

        if (null === $standardProduct) {
            return null;
        }

        return $standardProduct;


    }

    /**
     * {@inheritdoc}
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        $this->fileIterator = null;
    }
}
