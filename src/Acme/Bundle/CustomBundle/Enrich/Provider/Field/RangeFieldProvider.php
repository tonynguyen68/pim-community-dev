<?php

namespace Acme\Bundle\CustomBundle\Enrich\Provider\Field;

use Pim\Component\Catalog\Model\AttributeInterface;
use Pim\Bundle\EnrichBundle\Provider\Field\FieldProviderInterface;

class RangeFieldProvider implements FieldProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getField($attribute)
    {
        return 'acme-range-field';
    }

    /**
     * {@inheritdoc}
     */
    public function supports($element)
    {
        //We only support number fields that have a number min and max property
        return $element instanceof AttributeInterface &&
            $element->getAttributeType() === 'pim_catalog_number' &&
            null !== $element->getNumberMin() &&
            null !== $element->getNumberMax();
    }
}

