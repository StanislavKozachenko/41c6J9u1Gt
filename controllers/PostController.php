<?php

namespace app\controllers;

use Yii;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\helpers\HtmlPurifier;
use app\models\Post;
use app\messages\AppMessages;

/**
 * Class PostController
 *
 * Контроллер управления постами.
 * Поддерживает создание, редактирование и удаление постов с ограничениями по времени и длине.
 */
class PostController extends Controller
{
    private const PAGE_SIZE = 4; // Количество постов на странице
    private const POST_INTERVAL = 180; // Минимальный интервал между постами (секунды)
    private const EDIT_LIMIT = 12 * 3600; // Время, в течение которого можно редактировать пост (секунды)
    private const DELETE_LIMIT = 14 * 24 * 3600; // Время, в течение которого можно удалить пост (секунды)
    private const AUTHOR_MIN = 2; // Минимальная длина имени автора
    private const AUTHOR_MAX = 15; // Максимальная длина имени автора
    private const MESSAGE_MIN = 5; // Минимальная длина сообщения
    private const MESSAGE_MAX = 1000; // Максимальная длина сообщения
    private const ALLOWED_TAGS = ['b', 'i', 's']; // Разрешённые HTML-теги

    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['GET', 'POST'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions(): array
    {
        return [
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Главная страница с постами и формой добавления нового поста
     */
    public function actionIndex()
    {
        return $this->render('index', $this->prepareIndexData());
    }

    /**
     * Подготовка данных для отображения списка постов
     */
    private function prepareIndexData(Post $model = null): array
    {
        $query = Post::find()->where(['deleted_at' => null])->orderBy(['created_at' => SORT_DESC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => self::PAGE_SIZE],
        ]);

        $pagination = $dataProvider->getPagination();
        $posts = $dataProvider->getModels();
        $totalCount = $dataProvider->getTotalCount();

        $currentPage = $pagination->getPage() ?? 0;
        $pageSize = $pagination->getPageSize() ?? self::PAGE_SIZE;

        $start = $totalCount > 0 ? $currentPage * $pageSize + 1 : 0;
        $end = $totalCount > 0 ? min($start + $pageSize - 1, $totalCount) : 0;

        return [
            'model' => $model ?? new Post(),
            'posts' => $posts,
            'pagination' => $pagination,
            'totalCount' => $totalCount,
            'start' => $start,
            'end' => $end,
        ];
    }

    /**
     * Создание нового поста
     */
    public function actionCreate()
    {
        $post = new Post();
        $request = Yii::$app->request;
        $ip = $request->userIP;
        $now = time();

        if (!$post->load($request->post())) {
            return $this->redirect(['index']);
        }

        $this->validatePost($post, $ip, $now);

        if ($post->hasErrors()) {
            return $this->render('index', $this->prepareIndexData($post));
        }

        $post->message = HtmlPurifier::process($post->message, ['HTML.Allowed' => implode(',', self::ALLOWED_TAGS)]);
        $post->ip = $ip;
        $post->created_at = $now;

        if ($post->save()) {
            $this->sendManageLinks($post);
            Yii::$app->session->setFlash('success', AppMessages::CREATE_SUCCESS);
        } else {
            Yii::$app->session->setFlash('error', AppMessages::CREATE_FAIL);
        }

        return $this->redirect(['index']);
    }

    /**
     * Валидация данных нового поста
     */
    private function validatePost(Post $post, string $ip, int $now): void
    {
        $post->author = trim($post->author);
        $post->message = trim($post->message);

        if (strlen($post->author) < self::AUTHOR_MIN || strlen($post->author) > self::AUTHOR_MAX) {
            $post->addError('author', AppMessages::ERROR_AUTHOR_LENGTH);
        }

        if (!filter_var($post->email, FILTER_VALIDATE_EMAIL)) {
            $post->addError('email', AppMessages::ERROR_EMAIL);
        }

        if (strlen($post->message) < self::MESSAGE_MIN || strlen($post->message) > self::MESSAGE_MAX) {
            $post->addError('message', AppMessages::ERROR_MESSAGE_LENGTH);
        }

        preg_match_all('/<([a-z][a-z0-9]*)\b[^>]*>/i', $post->message, $matches);
        if (array_diff(array_unique(array_map('strtolower', $matches[1] ?? [])), self::ALLOWED_TAGS)) {
            $post->addError('message', AppMessages::ERROR_TAGS);
        }

        $lastPost = Post::find()->where(['ip' => $ip])->orderBy(['created_at' => SORT_DESC])->one();
        if ($lastPost && ($now - $lastPost->created_at < self::POST_INTERVAL)) {
            $wait = self::POST_INTERVAL - ($now - $lastPost->created_at);
            $wait_text = sprintf(AppMessages::CREATE_RATE_LIMIT, floor($wait / 60), $wait % 60);
            Yii::$app->session->setFlash(
                'error',
                $wait_text
            );
            $post->addError('message', $wait_text);
        }
    }

    /**
     * Редактирование поста
     */
    public function actionEdit($id, $token)
    {
        $post = $this->findPostOrFlash($id, $token);
        if (!$post) return $this->redirect(['index']);

        if (time() - $post->created_at > self::EDIT_LIMIT) {
            Yii::$app->session->setFlash('error', AppMessages::EDIT_EXPIRED);
            return $this->redirect(['index']);
        }

        if (Yii::$app->request->isPost) {
            $message = trim(Yii::$app->request->post('Post')['message'] ?? '');
            $error = $this->validateEditMessage($message);
            if ($error) {
                Yii::$app->session->setFlash('error', $error);
                return $this->redirect(['post/edit', 'id' => $post->id, 'token' => $token]);
            }

            $post->message = HtmlPurifier::process($message, ['HTML.Allowed' => implode(',', self::ALLOWED_TAGS)]);
            $post->save(false);
            Yii::$app->session->setFlash('success', AppMessages::EDIT_SUCCESS);
            return $this->redirect(['index']);
        }

        return $this->render('edit', ['model' => $post]);
    }

    private function validateEditMessage(string $message): ?string
    {
        if (strlen($message) < self::MESSAGE_MIN || strlen($message) > self::MESSAGE_MAX) {
            return AppMessages::ERROR_MESSAGE_LENGTH;
        }

        preg_match_all('/<([a-z][a-z0-9]*)\b[^>]*>/i', $message, $matches);
        if (array_diff(array_unique(array_map('strtolower', $matches[1] ?? [])), self::ALLOWED_TAGS)) {
            return AppMessages::ERROR_TAGS;
        }

        return null;
    }

    /**
     * Удаление поста
     */
    public function actionDelete($id, $token)
    {
        $post = $this->findPostOrFlash($id, $token);
        if (!$post) return $this->redirect(['index']);

        if ($post->deleted_at !== null) {
            Yii::$app->session->setFlash('info', AppMessages::DELETE_SUCCESS);
            return $this->redirect(['index']);
        }

        if (time() - $post->created_at > self::DELETE_LIMIT) {
            Yii::$app->session->setFlash('error', AppMessages::DELETE_EXPIRED);
            return $this->redirect(['index']);
        }

        if (Yii::$app->request->isPost) {
            $post->deleted_at = time();
            $post->save(false);
            Yii::$app->session->setFlash('success', AppMessages::DELETE_SUCCESS);
            return $this->redirect(['index']);
        }

        return $this->render('delete', ['model' => $post]);
    }

    /**
     * Поиск поста по ID и токену с flash-сообщением при ошибке
     */
    private function findPostOrFlash($id, $token): ?Post
    {
        $post = Post::findOne($id);
        if (!$post) {
            Yii::$app->session->setFlash('error', AppMessages::ERROR_NOT_FOUND);
            return null;
        }
        if ($post->token !== $token) {
            Yii::$app->session->setFlash('error', AppMessages::ERROR_BAD_TOKEN);
            return null;
        }
        return $post;
    }

    /**
     * Отправка письма с ссылками на редактирование и удаление
     */
    private function sendManageLinks(Post $post): void
    {
        try {
            $editUrl = Yii::$app->urlManager->createAbsoluteUrl(['post/edit', 'id' => $post->id, 'token' => $post->token]);
            $deleteUrl = Yii::$app->urlManager->createAbsoluteUrl(['post/delete', 'id' => $post->id, 'token' => $post->token]);

            Yii::$app->mailer->compose()
                ->setFrom(['noreply@storyvalut.com' => 'StoryValut App'])
                ->setTo($post->email)
                ->setSubject(AppMessages::MAIL_SUBJECT)
                ->setTextBody(sprintf(AppMessages::MAIL_TEXT, $editUrl, $deleteUrl))
                ->send();
        } catch (\Exception $e) {
            Yii::error(AppMessages::LOG_MAIL_ERROR . $e->getMessage(), __METHOD__);
        }
    }
}
