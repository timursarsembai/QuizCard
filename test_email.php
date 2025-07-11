<?php
$to = 'sarsembai.timur@gmail.com';
$subject = 'Тест отправки почты';
$message = 'Это тестовое письмо';
$headers = 'From: test@ramazango.kz';

if (mail($to, $subject, $message, $headers)) {
    echo "✅ Письмо отправлено успешно!";
} else {
    echo "❌ Ошибка отправки письма";
}
?>