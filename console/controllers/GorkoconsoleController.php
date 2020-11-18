<?php
namespace console\controllers;

use Yii;
use yii\console\Controller;
use common\models\GorkoApi;
use common\models\Subdomen;
use common\models\Restaurants;

class GorkoconsoleController extends Controller
{
	public $siteArr = [
		'korporativ' => [
			'params' 	=> [
				'params' 		=> 'city_id={{city_id}}&type_id=1&event=17',
				'watermark' 	=> '/var/www/pmnetwork/pmnetwork/frontend/web/img/ny_ball.png',
				'imageHash' 	=> 'newyearpmn',
				'dsn' 			=> 'mysql:host=localhost;dbname=pmn_gorko_ny',
				'subdomens' 	=> true,
				'subdomen' 		=> null,
				'elasticModel' 	=> 'frontend\modules\gorko_ny\models\ElasticItems',
				'only_comm'		=> true,
			]			
		],
		'svadbanaprirode' => [
			'params' 	=> [
				'params' 		=> 'city_id=4400&type_id=1&type=30,11,17,14',
				'watermark' 	=> '/var/www/pmnetwork/pmnetwork/frontend/web/img/watermark.png',
				'imageHash' 	=> 'svadbanaprirode',
				'dsn' 			=> 'mysql:host=localhost;dbname=pmn_svadbanaprirode',
				'subdomens' 	=> false,
				'subdomen' 		=> null,
				'elasticModel' 	=> 'frontend\modules\svadbanaprirode\models\ElasticItems',
				'only_comm'		=> false,
			]
		]
	];

	public function actionRenewAllData($site)
	{
		$siteArr = $this->siteArr;
		if (!array_key_exists($site, $siteArr)) {
		    return 0;
		}
		if($siteArr[$site]['params']['subdomens']){
			$connection = new \yii\db\Connection([
		    	'dsn' 		=> $siteArr[$site]['params']['dsn'],
			    'username' 	=> 'pmnetwork',
			    'password' 	=> 'P2t8wdBQbczLNnVT',
			    'charset' 	=> 'utf8',
			]);
			$connection->open();
			Yii::$app->set('db', $connection);

			$subdomen_model = Subdomen::find()
				->all($connection);
			foreach ($subdomen_model as $key => $subdomen) {
				$subdomenArr = $siteArr;
				$subdomenArr[$site]['params']['params']   = str_replace('{{city_id}}', $subdomen->city_id, $subdomenArr[$site]['params']['params']);
				$subdomenArr[$site]['params']['subdomen'] = $subdomen->city_id;
				$gorko_api = GorkoApi::renewAllData([$subdomenArr[$site]['params']]);
				print_r($gorko_api);
			}
		}
		else{
			GorkoApi::renewAllData([$siteArr[$site]['params']]);
		}
		

		return 'Понеслась ёбка';
	}

	public function actionSubdomenCheck($site)
	{
		$siteArr = $this->siteArr;
		if (!array_key_exists($site, $siteArr)) {
		    return 0;
		}
		else{
			$connection = new \yii\db\Connection([
		    	'dsn' 		=> $siteArr[$site]['params']['dsn'],
			    'username' 	=> 'pmnetwork',
			    'password' 	=> 'P2t8wdBQbczLNnVT',
			    'charset' 	=> 'utf8',
			]);
			$connection->open();
			Yii::$app->set('db', $connection);
			$subdomen_model = Subdomen::find()->all($connection);

			foreach ($subdomen_model as $key => $subdomen) {
				$restaurants = Restaurants::find()->where(['city_id' => $subdomen->city_id])->all($connection);
				if(count($restaurants) > 9){
					$subdomen->active = 1;
				}
				else{
					$subdomen->active = 0;
				}
				$subdomen->save();
			}
		}
		return 1;			
	}

	public function actionElasticRefresh($site)
	{
		$siteArr = $this->siteArr;
		if (!array_key_exists($site, $siteArr)) {
		    return 0;
		}
		$elasticItems = $siteArr[$site]['params']['elasticModel'];
		$elasticItemsClass = new $elasticItems();
		$elasticItemsClass::refreshIndex();
	}

	public function actionClear()
	{
		$connection = new \yii\db\Connection([
	    	'dsn' 		=> 'mysql:host=localhost;dbname=pmn_svadbanaprirode',
		    'username' 	=> 'pmnetwork',
		    'password' 	=> 'P2t8wdBQbczLNnVT',
		    'charset' 	=> 'utf8',
		]);
		$connection->open();
		Yii::$app->set('db', $connection);
		$restaurants = Restaurants::find()->all($connection);
		foreach ($restaurants as $key => $value) {
			$value->location = null;
			$value->save();
		}
		echo count($restaurants);
	}

}