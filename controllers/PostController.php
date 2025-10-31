<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\db\Exception as DbException;
use app\models\Post;

/**
 * PostController handles creation, display, editing, and soft deletion of posts.
 */
class PostController extends Controller
{
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
     * Creates Post object
     *
     * @return Post
     */
    protected function createPostModel(): Post
    {
        return new Post();
    }

    /**
     * Displays list of posts.
     *
     * @return string
     */
    public function actionIndex(): string
    {
        $posts = Post::find()
            ->where(['deleted_at' => null])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        return $this->render('index', [
            'posts' => $posts,
        ]);
    }

    /**
     * Creates a new post.
     *
     * @return string|Response
     */
    public function actionCreate()
    {
        $post = $this->createPostModel();

        if ($post->load(Yii::$app->request->post())) {

            // Limit 1 post per 3 minutes per IP
            $lastPost = Post::find()
                ->where(['ip' => Yii::$app->request->userIP])
                ->andWhere(['deleted_at' => null])
                ->orderBy(['created_at' => SORT_DESC])
                ->one();

            if ($lastPost && (time() - $lastPost->created_at < 180)) {
                $nextTime = date('H:i:s', $lastPost->created_at + 180);
                $post->addError('message', "You can post again at $nextTime");
            } else {
                try {
                    if ($post->validate() && $post->save()) {
                        // Send private edit/delete links to author (debug mail)
                        Yii::$app->mailer->compose()
                            ->setTo($post->email)
                            ->setSubject('Manage your post')
                            ->setTextBody(
                                "Edit: " . Yii::$app->urlManager->createAbsoluteUrl(['post/update', 'token' => $post->token]) .
                                "\nDelete: " . Yii::$app->urlManager->createAbsoluteUrl(['post/delete', 'token' => $post->token])
                            )
                            ->send();

                        return $this->redirect(['index']);
                    }
                } catch (DbException $e) {
                    Yii::error('Failed to save post: ' . $e->getMessage());
                    $post->addError('message', 'Internal error, please try again later.');
                }
            }
        }

        return $this->render('create', [
            'model' => $post,
        ]);
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
