<?php
namespace common\components;

use Yii;
use yii\base\BaseObject;
use common\models\Restaurants;
use common\models\Rooms;
use common\models\Images;
use common\components\AsyncRenewImages;

class AsyncRenewRestaurants extends BaseObject implements \yii\queue\JobInterface
{
	public  $gorko_id,
		   	$dsn,
		   	$imageLoad,
		   	$watermark = '/var/www/pmnetwork/pmnetwork/frontend/web/img/watermark.png',
		   	$imageHash = 'svadbanaprirode',
		   	$only_comm;

	public function execute($queue) {

		if( $curl = curl_init() ) {
		    curl_setopt($curl, CURLOPT_URL, 'https://api.gorko.ru/api/v2/restaurants/'.$this->gorko_id.'?embed=rooms,contacts&fields=address,params,covers,district&is_edit=1');
		    curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
		    curl_setopt($curl, CURLOPT_ENCODING, '');
		    $response = json_decode(curl_exec($curl), true)['restaurant'];
		    curl_close($curl);

		    $connection = new \yii\db\Connection([
			    'dsn' => $this->dsn,
			    'username' => 'root',
			    'password' => 'Gkcfmdsop',
			    'charset' => 'utf8',
			]);
			$connection->open();
			Yii::$app->set('db', $connection);

			if(!$response['commission'] && $this->only_comm){
				return 1;
			}

		    $model = Restaurants::find()->where(['gorko_id' => $this->gorko_id])->one($connection);
		    
		    if(!$model){
		    	$model = new Restaurants();
		    }

		    $attributes = [];
		    $attributes['active'] = 1;
		    $attributes['in_elastic'] = 0;
		    $attributes['gorko_id'] = $response['id'];
		    $attributes['name'] = $response['name'];
			$attributes['address'] = $response['address'];

			if(isset($response['params']['param_location'])){
				$attributes['location'] = '';
				$flag = true;
				print_r($response['params']['param_location']['value']);
				foreach ($response['params']['param_location']['value'] as $location){
					if($flag){
						$attributes['location'] .= $location;
						$flag = false;
					}
					else{
						$attributes['location'] .= ','.$location;
					}
					
				}
			}

			if(isset($response['params']['param_type'])){
				$attributes['type'] = '';
				$flag = true;
				foreach ($response['params']['param_type']['value'] as $type){
					if($flag){
						$attributes['type'] .= $type;
						$flag = false;
					}
					else{
						$attributes['type'] .= ','.$type;
					}
					
				}
			}			

			if($response['covers'][0]){
				$attributes['cover_url'] = str_replace("=s0", "", $response['covers'][0]['original_url']);
			}

			if(count($response['capacity']) > 0){
				$min_capacity = min($response['capacity']);
				$max_capacity = max($response['capacity']);
				$attributes['min_capacity'] = $min_capacity;
				$attributes['max_capacity'] = $max_capacity;
			}

			$attributes['latitude'] = strval($response['latitude']);
			$attributes['longitude'] = strval($response['longitude']);
			$attributes['district'] = $response['district']['id'] ? $response['district']['id'] : 0;
			$attributes['parent_district'] = $response['district']['parent_id'] ? $response['district']['parent_id'] : 0;
			$attributes['city_id'] = $response['city']['id'];
			$attributes['commission'] = $response['commission'] ? $response['commission'] : 0;
			$attributes['own_alcohol'] = isset($response['params']['param_own_alcohol']) ? $response['params']['param_own_alcohol']['display']['text'] : '';
			if(isset($response['params']['param_own_alcohol'])){
				$attributes['alcohol'] = $response['params']['param_own_alcohol']['value'];
			}
			$attributes['cuisine'] = isset($response['params']['param_cuisine']) ? $response['params']['param_cuisine']['display']['text'] : '';
			if(isset($response['params']['param_firework'])){
				$attributes['firework'] = $response['params']['param_firework']['value'];
			}
			if(isset($response['params']['param_parking'])){
				$attributes['parking'] = $response['params']['param_parking']['value'];
			}
			if(isset($response['params']['param_extra_services'])){
				$attributes['extra_services'] = $response['params']['param_extra_services']['display']['text'] ? $response['params']['param_extra_services']['display']['text'] : '';
			}
			if(isset($response['params']['param_payment'])){
				$attributes['payment'] = $response['params']['param_payment']['display']['text'] ? $response['params']['param_payment']['display']['text'] : '';
			}
			if(isset($response['params']['param_special'])){
				$attributes['special'] = $response['params']['param_special']['display']['text'] ? $response['params']['param_special']['display']['text'] : '';
			}	

			foreach ($response['contacts'] as $key => $value) {
				if($value['key'] == 'phone'){
					$attributes['phone'] = $value['value'];
				}
			}

			$model->attributes = $attributes;
		    $rest_save = $model->save();

		    //$log = file_get_contents('/var/www/pmnetwork/pmnetwork/log/manual.log');
		    //$log .= json_encode($attributes);
		    //$log .= json_encode($model->getErrors());
		    //file_put_contents('/var/www/pmnetwork/pmnetwork/log/manual.log', $log);

		    $restModel = Restaurants::find()->where(['gorko_id' => $response['id']])->one($connection);

		    if($restModel){

			    foreach ($response['covers'] as $key => $image) {
					$imgModel = Images::find()->where(['gorko_id' => $image['id']])->one($connection);
					$imgAttributes = [];

					if($imgModel && ($imgModel->item_id != $restModel->id)) {
						$imgModel->item_id = $restModel->id;
						$imgModel->save();
					}
			    
				    if(!$imgModel){
				    	$imgModel = new Images();
				    	$imgAttributes['gorko_id'] = $image['id'];
				    	$imgAttributes['sort'] = $key;
				    	$imgAttributes['realpath'] = str_replace('=s0', '', $image['original_url']);
				    	$imgAttributes['type'] = 'restaurant';
				    	$imgAttributes['item_id'] = $restModel->id;
				    	$imgModel->attributes = $imgAttributes;
				    	$imgModel->save();

				    	$queue_id = Yii::$app->queue->push(new AsyncRenewImages([
							'item_id' => $imgModel->id,
							'dsn' => $this->dsn,
							'type' => 'restaurant',
							'watermark' => $this->watermark,
							'imageHash' => $this->imageHash,
						]));
				    }
				    elseif(!$imgModel->subpath){
				    	$queue_id = Yii::$app->queue->push(new AsyncRenewImages([
							'item_id' => $imgModel->id,
							'dsn' => $this->dsn,
							'type' => 'restaurant',
							'watermark' => $this->watermark,
							'imageHash' => $this->imageHash,
						]));
				    }
				}
				
				foreach ($response['rooms'] as $key => $room) {
					$roomModel = Rooms::find()->where(['gorko_id' => $room['id']])->one($connection);
			    
				    if(!$roomModel){
				    	$roomModel = new Rooms();
				    }

				    $roomAttributes = [];
				    $roomAttributes['active'] = 1;
		    		$roomAttributes['in_elastic'] = 0;
			    	$roomAttributes['gorko_id'] = $room['id'];
			    	$roomAttributes['name'] = $room['name'] ? $room['name'] : $room['type_name'];
			    	$roomAttributes['restaurant_id'] = $restModel->id;
			    	$roomAttributes['price'] = $room['prices'][0]['value'];
			    	$roomAttributes['capacity'] = $room['params']['param_capacity_0']['value'];

			    	if($room['cover_url']){
						$roomAttributes['cover_url'] = str_replace("w230-h150-n-l95", "w445-h302-n-l95", $room['cover_url']);
					}

			    	if(isset($room['params']['param_capacity_reception_0'])){
			    		$roomAttributes['capacity_reception'] = $room['params']['param_capacity_reception_0']['value'] ? $room['params']['param_capacity_reception_0']['value'] : 0;
			    	}
			    	
			    	$roomAttributes['type'] = $room['type'];
			    	$roomAttributes['type_name'] = $room['type_name'];

			    	if(isset($room['params']['param_rent_only_0'])){
						$roomAttributes['rent_only'] = $room['params']['param_rent_only_0']['value'] == 1 ? 1 : 0;
					}
					if(isset($room['params']['param_bright_room_0'])){
						$roomAttributes['bright_room'] = $room['params']['param_bright_room_0']['value'] == 1 ? 1 : 0;
					}
					if(isset($room['params']['param_separate_entrance_0'])){
						$roomAttributes['separate_entrance'] = $room['params']['param_separate_entrance_0']['value'] == 1 ? 1 : 0;
					}
					if(isset($room['params']['param_features_0'])){
						$roomAttributes['features'] = $room['params']['param_features_0']['value'] ? $room['params']['param_features_0']['value'] : '';
					}

					if(isset($room['params']['param_min_price_0']) && $room['params']['param_min_price_0']['value']){
						$roomAttributes['banquet_price'] = $room['params']['param_min_price_0']['value'];
					}
					else{
						$roomAttributes['banquet_price'] = $room['prices'][0]['value'] * $room['params']['param_capacity_0']['value'];
					}

			    	$roomModel->attributes = $roomAttributes;
			    	$roomModel->save();

			    	$roomModel = Rooms::find()->where(['gorko_id' => $room['id']])->one($connection);

			    	if($roomModel){
			    		foreach ($room['media'] as $key => $image) {
							$imgModel = Images::find()->where(['gorko_id' => $image['id']])->one($connection);
							$imgAttributes = [];

							if($imgModel && ($imgModel->item_id != $roomModel->id)) {
								$imgModel->item_id = $roomModel->id;
								$imgModel->save();
							}
					    
						    if(!$imgModel){
						    	$imgModel = new Images();
						    	$imgAttributes['gorko_id'] = $image['id'];
						    	$imgAttributes['sort'] = $key;
						    	$imgAttributes['realpath'] = str_replace('=s0', '', $image['original_url']);
						    	$imgAttributes['type'] = 'rooms';
						    	$imgAttributes['item_id'] = $roomModel->id;
						    	$imgModel->attributes = $imgAttributes;
					    		$imgModel->save();

					    		$queue_id = Yii::$app->queue->push(new AsyncRenewImages([
									'item_id' => $imgModel->id,
									'dsn' => $this->dsn,
									'type' => 'rooms',
									'watermark' => $this->watermark,
									'imageHash' => $this->imageHash,
								]));
						    }
						    elseif(!$imgModel->subpath){
						    	$queue_id = Yii::$app->queue->push(new AsyncRenewImages([
									'item_id' => $imgModel->id,
									'dsn' => $this->dsn,
									'type' => 'rooms',
									'watermark' => $this->watermark,
									'imageHash' => $this->imageHash,
								]));
						    }			    
						}
			    	}		    	
				}
			}	    
	  	}

	  	return $restModel;
	}
}