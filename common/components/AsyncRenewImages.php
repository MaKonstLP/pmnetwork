<?php
namespace common\components;

use Yii;
use yii\base\BaseObject;
use common\models\Restaurants;
use common\models\Rooms;
use common\models\Images;

class AsyncRenewImages extends BaseObject implements \yii\queue\JobInterface
{
	public  $item_id,
		   	$type,
		   	$dsn,
		   	$watermark = '/var/www/pmnetwork/frontend/web/img/watermark.png',
		   	$imageHash = 'birthdaypmn';

	public function execute($queue) {
		$connection = new \yii\db\Connection([
		    'dsn' => $this->dsn,
		    'username' => 'root',
		    'password' => 'Gkcfmdsop',
		    'charset' => 'utf8',
		]);
		$connection->open();
		Yii::$app->set('db', $connection);

		$imgModel = Images::find()->where(['id' => $this->item_id, 'type' => $this->type])->one($connection);

		//foreach ($imgModel as $img) {
			$curl = curl_init();
			$file = $this->watermark;
			$mime = mime_content_type($file);
			$info = pathinfo($file);
			$name = $info['basename'];
			$output = curl_file_create($file, $mime, $name);
			$params = [
				'mediaId' => $imgModel->gorko_id,
				'token'=> '4aD9u94jvXsxpDYzjQz0NFMCpvrFQJ1k',
				'watermark' => $output,
				'hash_key' => $this->imageHash
			];
			curl_setopt($curl, CURLOPT_URL, 'https://api.gorko.ru/api/v2/tools/mediaToSatellite');
		    curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
		    curl_setopt($curl, CURLOPT_ENCODING, '');
		    curl_setopt($curl, CURLOPT_POST, true);
		    curl_setopt($curl, CURLOPT_POSTFIELDS, $params);

		    $response = curl_exec($curl);
		    $response_obj = json_decode($response);
		    print_r(json_decode($response));
		    curl_close($curl);

		    $imgModel->subpath = $response_obj->url;
		    $imgModel->waterpath = $response_obj->url_watermark;
		    $imgModel->timestamp = time();

		    $imgModel->save();		    
		//}
	}
}