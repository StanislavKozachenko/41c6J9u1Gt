<?php

namespace app\messages;

/**
 * Class AppMessages
 *
 * Централизованное хранилище всех сообщений приложения:
 * ошибок, уведомлений, текстов логов и подсказок для пользователя.
 */
class AppMessages
{
    // === Общие ===
    public const ERROR_COMMON = 'Поле обязательно для заполнения.';
    public const ERROR_AUTHOR_LENGTH = 'Имя должно быть от 2 до 15 символов.';
    public const ERROR_MESSAGE_LENGTH = 'Сообщение должно быть от 5 до 1000 символов.';
    public const ERROR_EMAIL = 'Некорректный e-mail.';
    public const ERROR_CAPTCHA = 'Неверный проверочный код.';
    public const ERROR_IP = 'Некорректный IP-адрес.';

    // === Сообщения о валидации тегов ===
    public const ERROR_TAGS = 'Разрешены только теги <b>, <i>, <s>.';

    // === Ограничения по времени ===
    public const EDIT_EXPIRED = 'Срок редактирования истёк.';
    public const DELETE_EXPIRED = 'Срок удаления истёк.';

    // === Flash-сообщения (пользовательские уведомления) ===
    public const CREATE_SUCCESS = 'Пост успешно создан! Письмо отправлено на ваш e-mail.';
    public const CREATE_FAIL = 'Ошибка при сохранении поста.';
    public const EDIT_SUCCESS = 'Пост обновлён.';
    public const DELETE_SUCCESS = 'Пост удалён.';
    public const CREATE_RATE_LIMIT = 'Вы можете отправить следующий пост через %d мин. %d сек.';

    // === Ошибки поиска и токена ===
    public const ERROR_NOT_FOUND = 'Пост не найден.';
    public const ERROR_BAD_TOKEN = 'Неверный токен.';

    // === Сообщения логов ===
    public const LOG_TOKEN_FAIL = 'Не удалось сгенерировать токен: ';
    public const LOG_DELETE_FAIL = 'Ошибка при удалении поста: ';
    public const LOG_MAIL_ERROR = 'Ошибка при отправке письма: ';

    // === Подписи и вспомогательные тексты ===
    public const LABEL_AUTHOR = 'Имя автора';
    public const LABEL_EMAIL = 'E-mail';
    public const LABEL_MESSAGE = 'Сообщение';
    public const LABEL_IP = 'IP-адрес';
    public const LABEL_CREATED_AT = 'Дата создания';
    public const LABEL_VERIFICATION_CODE = 'Проверочный код (капча)';

    // === Кнопки ===
    public const BTN_CREATE = 'Отправить';
    public const BTN_UPDATE = 'Обновить';
    public const BTN_DELETE_CONFIRM = 'Подтвердить удаление';
    public const BTN_CANCEL = 'Отмена';

    // === Тексты e-mail уведомлений ===
    public const MAIL_SUBJECT = 'Управляйте вашим постом!';
    public const MAIL_TEXT = "Ваш пост создан!\n\nРедактировать: %s\nУдалить: %s";
}
