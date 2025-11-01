<?php

namespace app\controllers;

use Yii;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\Response;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\db\Exception as DbException;
use app\models\Post;
use app\messages\AppMessages;

/**
 * PostController handles creation, display, editing, and soft deletion of posts.
 */
class PostController extends Controller
{
    private const PAGE_SIZE = 4; // Кол-во постов на странице
    private const POST_INTERVAL = 180; // 3 минуты
    private const EDIT_LIMIT = 12 * 3600; // 12 часов
    private const DELETE_LIMIT = 14 * 24 * 3600; // 14 дней
    private const POST_MESSAGE_MIN = 5; // Минимальная длина поста
    private const POST_MESSAGE_MAX = 1000; // Максимальная длина поста
    private const AUTHOR_NAME_MIN = 2; // Минмальная длина имени автора
    private const AUTHOR_NAME_MAX = 15; // Максимальная длина имени автора

    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /** Капча (если используется) */
    public function actions()
    {
        return [
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /** Главная страница со списком постов */
    public function actionIndex()
    {
        $query = Post::find()->where(['deleted_at' => null])->orderBy(['created_at' => SORT_DESC]);
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => self::PAGE_SIZE],
        ]);

        $pagination = $dataProvider->getPagination();
        $totalCount = $dataProvider->getTotalCount();
        $start = $pagination->page * $pagination->pageSize + 1;
        $end = min($start + $pagination->pageSize - 1, $totalCount);

        return $this->render('index', [
            'model' => new Post(),
            'dataProvider' => $dataProvider,
            'start' => $start,
            'end' => $end,
            'totalCount' => $totalCount,
            'pagination' => $pagination,
        ]);
    }


    /** Создание поста */
    public function actionCreate()
    {
        $post = new Post();
        $request = Yii::$app->request;
        $ip = $request->userIP;
        $now = time();

        if (!$post->load($request->post())) {
            return $this->redirect(['index']);
        }

        // === Валидация ===
        $post->author = trim($post->author);
        $post->message = trim($post->message);

        if (strlen($post->author) < self::AUTHOR_NAME_MIN || strlen($post->author) > self::AUTHOR_NAME_MAX)
            $post->addError('author', AppMessages::ERROR_AUTHOR_LENGTH);
        $post = $this->createPostModel();

        if (!filter_var($post->email, FILTER_VALIDATE_EMAIL))
            $post->addError('email', AppMessages::ERROR_EMAIL);

        if (strlen($post->message) < self::POST_MESSAGE_MIN || strlen($post->message) > self::POST_MESSAGE_MAX)
            $post->addError('message', AppMessages::ERROR_MESSAGE_LENGTH);

        // Проверка тегов
        $allowedTags = ['b', 'i', 's'];
        preg_match_all('/<([a-z][a-z0-9]*)\b[^>]*>/i', $post->message, $matches);
        $invalidTags = array_diff(array_unique(array_map('strtolower', $matches[1] ?? [])), $allowedTags);
        if (!empty($invalidTags))
            $post->addError('message', AppMessages::ERROR_TAGS);

        // Ограничение по IP
        $lastPost = Post::find()->where(['ip' => $ip])->orderBy(['created_at' => SORT_DESC])->one();
        if ($lastPost && ($now - $lastPost->created_at < self::POST_INTERVAL)) {
            $wait = self::POST_INTERVAL - ($now - $lastPost->created_at);
            Yii::$app->session->setFlash('error', sprintf(AppMessages::CREATE_RATE_LIMIT, floor($wait / 60), $wait % 60));
            return $this->redirect(['index']);
        }

        // Ошибки формы
        if ($post->hasErrors()) {
            return $this->render('index', [
                'model' => $post,
                'dataProvider' => new ActiveDataProvider([
                    'query' => Post::find()->where(['deleted_at' => null])->orderBy(['created_at' => SORT_DESC]),
                    'pagination' => ['pageSize' => self::PAGE_SIZE],
                ]),
            ]);
        }

        // === Сохранение ===
        $post->message = HtmlPurifier::process($post->message, ['HTML.Allowed' => 'b,i,s']);
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

    /** Отправка письма с ссылками */
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
            Yii::error('Ошибка отправки письма: ' . $e->getMessage());
        }
    }

    /**
     * Updates a post by token (editable for 12 hours).
     *
     * @param string $token
     * @return string|Response
     * @throws NotFoundHttpException
     * @throws BadRequestHttpException
     */
    public function actionUpdate(string $token)
    {
        $post = Post::findOne(['token' => $token, 'deleted_at' => null]);

        if (!$post) {
            throw new NotFoundHttpException('Post not found or deleted.');
        }

        if (time() - $post->created_at > 12 * 3600) {
            throw new BadRequestHttpException('Editing period expired.');
        }

        if ($post->load(Yii::$app->request->post())) {
            try {
                if ($post->save()) {
                    return $this->redirect(['index']);
                }
            } catch (DbException $e) {
                Yii::error('Failed to update post: ' . $e->getMessage());
                $post->addError('message', 'Internal error, please try again later.');
            }
        }

        return $this->render('update', [
            'model' => $post,
        ]);
    }

    /**
     * Soft deletes a post by token (confirm deletion).
     *
     * @param string $token
     * @return string|Response
     * @throws NotFoundHttpException
     * @throws BadRequestHttpException
     */
    public function actionDelete(string $token)
    {
        $post = Post::findOne(['token' => $token, 'deleted_at' => null]);

        if (!$post) {
            throw new NotFoundHttpException('Post not found or already deleted.');
        }

        if (time() - $post->created_at > 14 * 24 * 3600) {
            throw new BadRequestHttpException('Deletion period expired.');
        }

        if (Yii::$app->request->isPost) {
            try {
                $post->softDelete();
                Yii::$app->session->setFlash('success', 'Post deleted.');
                return $this->redirect(['index']);
            } catch (DbException $e) {
                Yii::error('Failed to delete post: ' . $e->getMessage());
                Yii::$app->session->setFlash('error', 'Internal error, please try again later.');
            }
        }

        // Render confirmation page
        return $this->render('delete', [
            'model' => $post,
        ]);
    }
}
