<?php

use app\models\Post;
use yii\helpers\Html;
use yii\widgets\LinkPager;

/* @var $this yii\web\View */
/* @var $posts Post[] */
/* @var $model Post */
/* @var $pagination yii\data\Pagination */
/* @var $start int */
/* @var $end int */
/* @var $totalCount int */

$this->title = 'StoryValut App';
?>

<div class="mb-2">
    Показаны записи <?= $start ?>–<?= $end ?> из <?= $totalCount ?>
</div>

<div class="row">
    <div class="col-md-8">
        <?php foreach ($posts as $post): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title"><?= Html::encode($post->author) ?></h5>
                    <p class="card-text"><?= $post->message ?></p>
                    <small class="text-muted">
                        <?= $post->getCreatedAtRelative() ?> |
                        <?= Html::encode($post->getMaskedIp()) ?> |
                        <?= $post->getAuthorPostCount() ?> постов
                    </small>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="d-flex justify-content-center mt-3">
            <?= LinkPager::widget([
                    'pagination' => $pagination,
                    'options' => ['class' => 'pagination'],
                    'linkOptions' => ['class' => 'btn btn-success mx-1'],
                    'pageCssClass' => 'page-item',
                    'prevPageCssClass' => 'page-item',
                    'nextPageCssClass' => 'page-item',
                    'firstPageCssClass' => 'page-item',
                    'lastPageCssClass' => 'page-item',
                    'disabledListItemSubTagOptions' => ['class' => 'page-link disabled'],
            ]) ?>
        </div>
    </div>

    <div class="col-md-4">
        <?= $this->render('_form', [
                'model' => $model,
                'action' => ['post/create'],
        ]) ?>
    </div>
</div>
