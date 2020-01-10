<?php
declare(strict_types=1);

namespace Hyperf\Curd\Lifecycle;

interface AddBeforeHooks
{
    /**
     * Add pre-processing
     * @return boolean
     */
    public function __addBeforeHooks();
}
