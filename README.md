# otus_course

## Создание и подключение собственного Composer-пакета

### 1. Создание пакета

#### Структура проекта:

```text
otus-util/
├── src/
│   └── Example.php
├── composer.json
├── README.md
└── .gitignore
```

#### composer.json

```json
{
    "name": "some/util-package",
    "description": "Utility",
    "type": "library",
    "license": "proprietary",
    "autoload": {
        "psr-4": {
            "Some\\UtilPackage\\": "src/"
        }
    },
    "require": {
        "php": "^8.2"
    }
}
```

#### Генерация autoload

```bash
composer install
# или
composer dump-autoload
```

#### Проверка

```php
use Some\UtilPackage\Example;

$example = new Example();
```

---

## 2. Подключение пакета в проект

### Локальная разработка через symlink

Структура:

```text
Projects/
├── package-util/
└── project/
```

В `project/composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../package-util",
            "options": {
                "symlink": true
            }
        }
    ],
    "require": {
        "some/util-package": "@dev"
    }
}
```

Установить пакет:

```bash
composer update
# или
composer require some/util-package:@dev

```

При изменении файлов библиотеки изменения сразу становятся доступны в проекте без переустановки пакета.

---

## Подключение через Git

Разместить библиотеку в Git-репозитории.

Добавить в `composer.json` проекта:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:some/util-package.git"
        }
    ],
    "require": {
        "some/util-package": "^1.0"
    }
}
```

или

```bash
composer require some/util-package:^1.0
```

Composer автоматически скачает библиотеку из Git.

---

## 3. Версионирование

SemVer (Semantic Versioning).
Формат версии:

```
MAJOR.MINOR.PATCH
```

Например:

```
1.0.0
1.1.0
1.1.1
2.0.0
```

### PATCH

Изменение:

* исправление ошибок;
* оптимизация;
* изменения без изменения API.

---

### MINOR

Изменение:

* добавление нового функционала;
* полная обратная совместимость сохраняется.

---

### MAJOR

Изменение:

* несовместимые изменения API;
* удаление методов;
* изменение сигнатур;
* изменение поведения, требующее изменения пользовательского кода.

---

## 4. Выпуск новой версии

```bash
git add .
git commit -m "Add new feature"
git push
```

Создать тег:

```bash
git tag v1.2.0
git push origin v1.2.0
```

После этого Composer сможет установить новую версию:

```bash
composer update some/util-package
# или
composer require some/util-package:^1.2
```
---

# Рекомендуемый рабочий процесс

Во время активной разработки использовать **Path Repository** с `symlink=true`. Это позволяет мгновенно видеть изменения библиотеки в проекте.

После стабилизации функциональности:

1. Закоммитить изменения.
2. Создать тег новой версии (`v1.0.0`, `v1.1.0` и т.д.).
3. Отправить изменения и тег в Git.
4. В рабочих проектах выполнить обновление зависимости через Composer.
