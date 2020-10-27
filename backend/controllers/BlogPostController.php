<?php

namespace backend\controllers;

use common\models\blog\BlogPost;
use yii\bootstrap\Tabs;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\HttpException;
use yii\helpers\Url;

/**
 * BlogPostController implements the CRUD actions for BlogPost model.
 */
class BlogPostController extends BaseBackendController
{

	/**
	 *
	 * @var boolean whether to enable CSRF validation for the actions in this controller.
	 * CSRF validation is enabled only when both this property and [[Request::enableCsrfValidation]] are true.
	 */
	public $enableCsrfValidation = false;

	public function behaviors()
	{
		return [];
	}

	/**
	 * Lists all BlogPost models.
	 *
	 * @return mixed
	 */
	public function actionIndex()
	{
		Url::remember();
		\Yii::$app->session['__crudReturnUrl'] = null;

		\Yii::$app->session['__crudReturnUrl'] = null;
		$dataProvider = new ActiveDataProvider([
			'query' => BlogPost::find(),
		]);

		return $this->render('index', [
			'dataProvider' => $dataProvider,
		]);
	}

	public function actionSave($id)
	{
		$post = BlogPost::findOne($id);
		return $post->saveHtml();
	}

	/**
	 * Displays a single BlogPost model.
	 *
	 * @param integer $id
	 * @return mixed
	 */
	public function actionView($id)
	{
		\Yii::$app->session['__crudReturnUrl'] = Url::previous();
		Url::remember();

		return $this->render('view', [
			'model' => $this->findModel($id),
		]);
	}


	/**
	 * Creates a new BlogPost model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 *
	 * @return mixed
	 */
	public function actionCreate()
	{
		$model = new BlogPost;

		try {
			if ($model->load($_POST) && $model->save()) {
				\Yii::$app->session->setFlash('success', 'Объект создан, можно загружать файлы');
				return $this->redirect(['update', 'id' => $model->id]);
			} elseif (!\Yii::$app->request->isPost) {
				$model->load($_GET);
			}
		} catch (\Exception $e) {
			$msg = (isset($e->errorInfo[2])) ? $e->errorInfo[2] : $e->getMessage();
			$model->addError('_exception', $msg);
		}
		return $this->render('create', ['model' => $model]);
	}

	/**
	 * Updates an existing BlogPost model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 *
	 * @param integer $id
	 * @return mixed
	 */

	public function actionUpdate($id)
	{
		$model = $this->findModel($id);
		if ($model->load($_POST) && $model->save()) {
			\Yii::$app->session->setFlash('success', 'Пост обновлен');
			return $this->redirect(['update', 'id' => $model->id]);
		} else {
			return $this->render('update', [
				'model' => $model,
			]);
		}
	}

	/**
	 * Deletes an existing BlogPost model.
	 * If deletion is successful, the browser will be redirected to the 'index' page.
	 *
	 * @param integer $id
	 * @return mixed
	 */
	public function actionDelete($id)
	{
		try {
			$this->findModel($id)->delete();
		} catch (\Exception $e) {
			$msg = (isset($e->errorInfo[2])) ? $e->errorInfo[2] : $e->getMessage();
			\Yii::$app->getSession()->addFlash('error', $msg);
			return $this->redirect(Url::previous());
		}


		// TODO: improve detection
		$isPivot = strstr('$id', ',');
		if ($isPivot == true) {
			return $this->redirect(Url::previous());
		} elseif (isset(\Yii::$app->session['__crudReturnUrl']) && \Yii::$app->session['__crudReturnUrl'] != '/') {
			Url::remember(null);
			$url = \Yii::$app->session['__crudReturnUrl'];
			\Yii::$app->session['__crudReturnUrl'] = null;

			return $this->redirect($url);
		} else {
			return $this->redirect(['index']);
		}
	}


	/**
	 * Finds the BlogPost model based on its primary key value.
	 * If the model is not found, a 404 HTTP exception will be thrown.
	 *
	 * @throws HttpException if the model cannot be found
	 * @param integer $id
	 * @return BlogPost the loaded model
	 */
	protected function findModel($id)
	{
		if (($model = BlogPost::findOne($id)) !== null) {
			return $model;
		} else {
			throw new HttpException(404, 'The requested page does not exist.');
		}
	}
}
