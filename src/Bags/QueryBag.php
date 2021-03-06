<?php


namespace Cblink\YApiDoc\Bags;


use Illuminate\Support\Arr;

class QueryBag extends BaseBag
{
    public function __construct($query, $dto)
    {
        foreach ($query as $key => $value) {
            $description = sprintf(
                "%s %s",
                (is_array($value) ? 'array :': ''),
                $dto->getRequestTrans($key)
            );

            $item = [];

            if (Arr::has(config('yapi.public.query', []), $key)) {
                $plan = Arr::get(config('yapi.public.query', []), $key);
                $item = [
                    'name' => $key,
                    'required' => (bool) ($plan['required'] ?? $dto->hasRequestExcept($key)),
                    'description' => empty(trim($description)) && !empty($plan) ? $plan['plan'] : $description,
                    'in' => 'query',
                    'type' => 'string'
                ];
            } else {
                $item = [
                    'name' => $key,
                    'required' => $dto->hasRequestExcept($key),
                    'description' => $description,
                    'in' => 'query',
                    'type' => 'string'
                ];
            }

            array_push($this->items, $item);
        }
    }
}
