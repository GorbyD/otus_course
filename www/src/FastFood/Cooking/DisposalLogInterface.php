<?php

namespace FastFood\Cooking;

/**
 * Вывод сообщений утилизации
 */
interface DisposalLogInterface
{
    /** @return string[] */
    public function messages(): array;
}
