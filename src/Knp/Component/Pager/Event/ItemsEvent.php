<?php

namespace Knp\Component\Pager\Event;

use Knp\Component\Pager\ParametersResolver;
use Symfony\Component\EventDispatcher\Event;

/**
 * Specific Event class for paginator
 */
class ItemsEvent extends Event
{
    /**
     * A target being paginated
     *
     * @var mixed
     */
    public $target;

    /**
     * List of options
     *
     * @var array
     */
    public $options;

    /**
     * Items result
     *
     * @var mixed
     */
    public $items;

    /**
     * Count result
     *
     * @var integer
     */
    public $count;

    private $offset;
    private $limit;
    private $parametersResolver;
    private $customPaginationParams = array();

    public function __construct($offset, $limit, ParametersResolver $parametersResolver)
    {
        $this->offset = $offset;
        $this->limit = $limit;
        $this->parametersResolver = $parametersResolver;
    }

    public function setCustomPaginationParameter($name, $value)
    {
        $this->customPaginationParams[$name] = $value;
    }

    public function getCustomPaginationParameters()
    {
        return $this->customPaginationParams;
    }

    public function unsetCustomPaginationParameter($name)
    {
        unset($this->customPaginationParams[$name]);
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function getOffset()
    {
        return $this->offset;
    }

    public function getParametersResolver(): ParametersResolver
    {
        return $this->parametersResolver;
    }
}
