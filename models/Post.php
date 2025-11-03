<?php

declare(strict_types=1);

namespace app\models;

use Yii;
use yii\base\Exception;
use yii\base\Security;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\helpers\HtmlPurifier;
use app\messages\AppMessages;

/**
 * Модель Post — пользовательские сообщения.
 *
 * @property int $id
 * @property string $author
 * @property string $email
 * @property string $message
 * @property string $ip
 * @property int $created_at
 * @property int|null $updated_at
 * @property int|null $deleted_at
 * @property string $token
 */
class Post extends ActiveRecord
{
    /** Поле для капчи */
    public $verificationCode;

    /** Название таблицы */
    public static function tableName(): string
    {
        return '{{%post}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    BaseActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
                    BaseActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
                'value' => time(),
            ],
        ];
    }

    /** Правила валидации */
    public function rules(): array
    {
        return [
            [['author', 'email', 'message', 'verificationCode'], 'required', 'message' => AppMessages::ERROR_COMMON],
            ['author', 'string', 'min' => 2, 'max' => 15, 'tooShort' => AppMessages::ERROR_AUTHOR_LENGTH, 'tooLong' => AppMessages::ERROR_AUTHOR_LENGTH],
            ['message', 'string', 'min' => 5, 'max' => 1000, 'tooShort' => AppMessages::ERROR_MESSAGE_LENGTH, 'tooLong' => AppMessages::ERROR_MESSAGE_LENGTH],
            ['email', 'email', 'message' => AppMessages::ERROR_EMAIL],
            ['ip', 'ip', 'message' => AppMessages::ERROR_IP],
            ['verificationCode', 'captcha', 'captchaAction' => 'post/captcha', 'caseSensitive' => false, 'message' => AppMessages::ERROR_CAPTCHA],
        ];
    }

    /** Подписи полей */
    public function attributeLabels(): array
    {
        return [
            'author' => AppMessages::LABEL_AUTHOR,
            'email' => AppMessages::LABEL_EMAIL,
            'message' => AppMessages::LABEL_MESSAGE,
            'ip' => AppMessages::LABEL_IP,
            'created_at' => AppMessages::LABEL_CREATED_AT,
            'verificationCode' => AppMessages::LABEL_VERIFICATION_CODE,
        ];
    }

    /** Перед сохранением */
    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) return false;

        $this->message = HtmlPurifier::process($this->message, ['HTML.Allowed' => 'b,i,s']);

        if (!$this->ip) $this->ip = Yii::$app->request->userIP ?? '0.0.0.0';

        if ($this->isNewRecord) {
            try {
                $this->token = (new Security())->generateRandomString(64);
            } catch (Exception $e) {
                Yii::error(AppMessages::LOG_TOKEN_FAIL . $e->getMessage(), __METHOD__);
                $this->token = bin2hex(uniqid((string)mt_rand(), true));
            }
        }

        return true;
    }

    /** Мягкое удаление */
    public function softDelete(): bool
    {
        $this->deleted_at = time();
        try {
            return $this->save(false, ['deleted_at']);
        } catch (\yii\db\Exception $e) {
            Yii::error(AppMessages::LOG_DELETE_FAIL . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    /** Количество постов автора (по IP) */
    public function getAuthorPostCount()
    {
        return self::find()->where(['ip' => $this->ip])->andWhere(['deleted_at' => null])->count();
    }

    /** Маскированный IP */
    public function getMaskedIp(): string
    {
        if (strpos($this->ip, ':') !== false) {
            $parts = explode(':', $this->ip);
            return implode(':', array_slice($parts, 0, 4)) . ':****:****:****:****';
        }

        $parts = explode('.', $this->ip);
        return sprintf('%s.%s.**.**', $parts[0] ?? '0', $parts[1] ?? '0');
    }

    /** Относительное время создания */
    public function getCreatedAtRelative(): string
    {
        $formatter = clone Yii::$app->formatter;
        $formatter->locale = 'ru-RU';
        $formatter->timeZone = Yii::$app->timeZone;
        return $formatter->asRelativeTime($this->created_at);
    }
}
