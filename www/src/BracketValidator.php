<?php

class BracketValidator
{
    public function validate(string $input): void
    {
        if (trim($input) === '') {
            throw new \InvalidArgumentException('Строка не должна быть пустой');
        }

        $depth = 0;

        for ($i = 0, $len = strlen($input); $i < $len; $i++) {
            $char = $input[$i];

            if ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth--;
                if ($depth < 0) {
                    throw new \InvalidArgumentException(
                        "Лишняя закрывающая скобка на позиции $i"
                    );
                }
            }
        }

        if ($depth !== 0) {
            throw new \InvalidArgumentException(
                "Осталось $depth незакрытых скобок"
            );
        }
    }
}
