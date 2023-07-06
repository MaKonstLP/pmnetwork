<?php
namespace common\components;

use Yii;
use yii\base\BaseObject;
use common\models\Restaurants;
use common\models\Rooms;
use common\models\ImagesExt;
use common\models\ImagesModule;

class AsyncRenewImages extends BaseObject implements \yii\queue\JobInterface
{
	public  $gorko_id,
		   	$params,
		   	$rest_flag,
			$rest_gorko_id,
			$room_gorko_id,
			$elastic_index,
			$elastic_type,
			$city_id;

	public function execute($queue) {
		//$imgModel = ImagesModule::find()
        //	->where(['gorko_id' => $this->gorko_id])
        //	->one();
        //$current = false;
        //if($imgModel) $current = true;
		//$log = file_get_contents('/var/www/pmnetwork/log/images.log');
	    //$log_arr = json_decode($log, true);
	    //$log_arr[] = ['id' => $this->gorko_id];
	    //$log_new = json_encode($log_arr);
	    //file_put_contents('/var/www/pmnetwork/log/images.log', $log_new);
	    //exit;

		$watermark_path = $this->params['watermark'];
		if(!$watermark_path)
			return 1;
		$imageHash = $this->params['imageHash'];
		$watermark_pos = $this->params['watermark_pos'];
		if(isset($this->params['watermark_city']) && isset($this->params['watermark_city'][$this->city_id])){
			$watermark_path = $this->params['watermark_city'][$this->city_id]['watermark'];
			$imageHash = $this->params['watermark_city'][$this->city_id]['imageHash'];
			$watermark_pos = $this->params['watermark_city'][$this->city_id]['watermark_pos'];
		}
		//Получение картинки из root таблицы
		$connection = new \yii\db\Connection($this->params['main_connection_config']);
        $connection->open();
        Yii::$app->set('db', $connection);
		$imgModel = ImagesExt::find()->where(['gorko_id' => $this->gorko_id])->one($connection);

		//Получение дублей и ватермарок для модуля по API

		$curl = curl_init();
		$file = $watermark_path;
		$mime = mime_content_type($file);
		$info = pathinfo($file);
		$name = $info['basename'];
		$output = curl_file_create($file, $mime, $name);
		$payload = [
			'mediaId' 			=> $imgModel->gorko_id,
			'token'				=> '4aD9u94jvXsxpDYzjQz0NFMCpvrFQJ1k',
			'watermark' 		=> $output,
			'hash_key' 			=> $imageHash,
			'watermarkPosition' => $watermark_pos
		];
		curl_setopt($curl, CURLOPT_URL, 'https://api.gorko.ru/api/v2/tools/mediaToSatellite');
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
	    curl_setopt($curl, CURLOPT_ENCODING, '');
	    curl_setopt($curl, CURLOPT_POST, true);
	    curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
	    $response = curl_exec($curl);
	    $response_obj = json_decode($response);
	    curl_close($curl);

	    //Добавление записи в MySQL модуля
	    $connection = new \yii\db\Connection($this->params['site_connection_config']);
        $connection->open();
        Yii::$app->set('db', $connection);
        $timestamp = time();
        $imgModel = ImagesModule::find()
        	->where(['gorko_id' => $this->gorko_id])
        	->one();
        if(!$imgModel) $imgModel = new ImagesModule;
        $imgModel->gorko_id = $this->gorko_id;
	    $imgModel->subpath = $response_obj->url;
	    $imgModel->waterpath = $response_obj->url_watermark;
	    $imgModel->timestamp = $timestamp;
	    $imgModel->save();

	    //Добавление waterpath/subpath/timestamp в elastic
	    if($this->elastic_type == 'rest'){
	    	if($this->rest_flag){
		    	$param = '{
				  "script": {
				    "source": "def targets = ctx._source.restaurant_images.findAll(image -> image.id == params.id); for(image in targets) {image.waterpath = params.waterpath; image.subpath = params.subpath; image.timestamp = params.timestamp}",
				    "params": {
				      "id": '.$this->gorko_id.',
				      "waterpath": "'.$response_obj->url_watermark.'",
				      "subpath": "'.$response_obj->url.'",
				      "timestamp": '.$timestamp.'
				    }
				  }
				}';
		    }
		    else{
		    	$param = '{
				  "script": {
				    "source": "def targets = ctx._source.rooms.findAll(room -> room.gorko_id == params.room_id);for (room in targets) {def targets2 = room.images.findAll(image -> image.id == params.id);for(image in targets2) {image.waterpath = params.waterpath; image.subpath = params.subpath; image.timestamp = params.timestamp}}",
				    "params": {
				      "room_id": '.$this->room_gorko_id.',
				      "id": '.$this->gorko_id.',
				      "waterpath": "'.$response_obj->url_watermark.'",
				      "subpath": "'.$response_obj->url.'",
				      "timestamp": '.$timestamp.'
				    }
				  }
				}';
		    }
			    
			$curl = curl_init();
		    curl_setopt($curl, CURLOPT_URL, "http://127.0.0.1:9200/".$this->elastic_index."/items/".$this->rest_gorko_id."/_update");
			curl_setopt($curl,CURLOPT_HTTPHEADER, array("content-type: application/json;"));
		    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		    curl_setopt($curl, CURLOPT_POSTFIELDS, $param);
		    $res = curl_exec($curl);
		    curl_close($curl);
	    }
	    else{
	    	$param = '{
			  "script": {
			    "source": "def targets = ctx._source.images.findAll(image -> image.id == params.id); for(image in targets) {image.waterpath = params.waterpath; image.subpath = params.subpath; image.timestamp = params.timestamp}",
			    "params": {
			      "id": '.$this->gorko_id.',
			      "waterpath": "'.$response_obj->url_watermark.'",
			      "subpath": "'.$response_obj->url.'",
			      "timestamp": '.$timestamp.'
			    }
			  }
			}';

			$curl = curl_init();
		    curl_setopt($curl, CURLOPT_URL, "http://127.0.0.1:9200/".$this->elastic_index."/items/".$this->room_gorko_id."/_update");
			curl_setopt($curl,CURLOPT_HTTPHEADER, array("content-type: application/json;"));
		    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		    curl_setopt($curl, CURLOPT_POSTFIELDS, $param);
		    $res = curl_exec($curl);
		    curl_close($curl);
	    }

	    return 1;
	}
}