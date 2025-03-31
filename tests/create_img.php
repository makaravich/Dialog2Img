<?php

// Подключаем класс
require_once '../src/Dialog_2_Img.php';

// Тестовый диалог
$dialog = <<<DIALOG
*До встречи с ним моя жизнь была чёрно-белой
А сейчас стала разноцветной?
*Нет
*Сейчас стала просто чёрной
DIALOG;

// Создаем экземпляр класса
$image_gen = new Dialog_2_Img(['imagesPath'=>'./img']);

// Генерируем анимированный GIF и сохраняем его в папке img

$outputPath = $image_gen->create($dialog);

// Выводим путь к сгенерированному файлу
echo "Файл был успешно создан: $outputPath";