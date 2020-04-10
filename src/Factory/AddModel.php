<?php
declare(strict_types=1);

namespace Hyperf\Curd\Factory;

use Closure;
use Hyperf\Curd\Common\AddAfterParams;
use Hyperf\DbConnection\Db;
use Hyperf\Utils\Context;

class AddModel extends BaseModel
{
    /**
     * 自动生成时间戳
     * @var bool
     */
    private bool $autoTimestamp = true;
    /**
     * 后置闭包
     * @var Closure|null
     */
    private ?Closure $after = null;
    /**
     * 错误返回
     * @var array
     */
    private array $error = [
        'error' => 1,
        'msg' => 'add failed'
    ];

    /**
     * 自动生成时间戳
     * @param bool $value
     * @return $this
     */
    public function setAutoTimestamp(bool $value): self
    {
        $this->autoTimestamp = $value;
        return $this;
    }

    /**
     * 设置后置处理
     * @param Closure $value
     * @return $this
     */
    public function afterHook(Closure $value): self
    {
        $this->after = $value;
        return $this;
    }

    /**
     * 执行
     * @return array
     */
    public function result(): array
    {
        if ($this->autoTimestamp) {
            $this->body['create_time'] = $this->body['update_time'] = time();
        }

        $result = null;
        if (empty($this->after)) {
            $result = Db::table($this->name)->insert($this->body);
        } else {
            $result = Db::transaction(function () {
                $id = null;
                if (!empty($this->body['id'])) {
                    $id = $this->body['id'];
                    $result = Db::table($this->name)->insert($this->body);
                    if (!$result) {
                        return false;
                    }
                } else {
                    $id = Db::table($this->name)->insertGetId($this->body);
                }

                if (empty($id)) {
                    $this->error = [
                        'error' => 1,
                        'msg' => 'this [id] is empty'
                    ];
                    Db::rollBack();
                    return false;
                }

                $param = new AddAfterParams();
                $param->setId($id);
                $func = $this->after;
                if (!$func($param)) {
                    $this->error = Context::get('error', [
                        'error' => 1,
                        'msg' => 'after hook failed'
                    ]);
                    Db::rollBack();
                    return false;
                }

                return true;
            });
        }

        return !$result ? $this->error : [
            'error' => 0,
            'msg' => 'ok'
        ];
    }
}