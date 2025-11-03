<?php

namespace app\controllers;

use yii\web\Controller;

class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function actions(): array
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    /**
     * Рредирект на страницу постов (PostController)
     */
    public function actionIndex()
    {
        return $this->redirect(['post/index']);
    }
}
