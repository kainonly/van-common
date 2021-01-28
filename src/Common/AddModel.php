<?php
declare(strict_types=1);

namespace Hyperf\Curd\Common;

use Hyperf\Curd\CurdInterface;
use Hyperf\Utils\Context;
use stdClass;

/**
 * Trait AddModel
 * @package Hyperf\Curd\Common
 * @property CurdInterface $curd
 */
trait AddModel
{
    public function add(): array
    {
        $body = $this->curd->should(static::$addValidate);
        if (method_exists($this, 'addBeforeHook') && !$this->addBeforeHook($body)) {
            return Context::get('error', [
                'error' => 1,
                'msg' => 'An exception occurred in the before hook'
            ]);
        }
        $model = $this->curd->model(static::$model, $body)->autoTimestamp(static::$autoTimestamp);
        if (method_exists($this, 'addAfterHook')) {
            $model = $model->afterHook(fn(stdClass $param) => $this->addAfterHook($body, $param));
        }
        return $model->add();
    }
}