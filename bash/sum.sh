#!/bin/bash

REGEX='^-?[0-9]+([.][0-9]+)?$'

if [[ $# -ne 2 ]]; then
    echo "Ошибка: ожидается 2 аргумента, получено $#" >&2
    exit 1
fi

if ! [[ $1 =~ $REGEX ]]; then
    echo "Ошибка: '$1' - невалидное число" >&2
    exit 1
fi

if ! [[ $2 =~ $REGEX ]]; then
    echo "Ошибка: '$2' - невалидное число" >&2
    exit 1
fi

awk "BEGIN { print $1 + $2 }"
