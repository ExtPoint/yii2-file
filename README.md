# yii2-file
Yii2 модуль загрузки и скачивания файлов.

## Особенности

* POST/PUT методы загрузки;
* Генерация preview изображений;
* Возможность сжимать/урезать изображения "на лету";
* Хранение всех загруженных файлов в БД.

## Установка

1. Установите пакет через Composer:
```bash
composer require extpoint/yii2-file
```

2. Добавьте модуль в конфигурацию приложения:

```php
<?php

use extpoint\yii2\components\ModuleLoader;

ModuleLoader::add('file', 'extpoint\yii2\file\FileModule'); // <--

return [
    'id' => 'my-project',
    // ...
];
```

3. Добавьте в webpack секцию поиска виджета:

```js
require('extpoint-yii2/webpack')
    .base(/* ... */)
    // ...
    
    .widgets('./vendor/extpoint/yii2-file/lib/widgets') // <--
```

Перезапустите webpack.
