<?php
namespace common\models;

use Yii;
use yii\base\Model;
use common\models\Restaurants;
use common\models\Rooms;
use common\components\AsyncRenewRestaurants;
use common\components\AsyncRenewImages;

class GorkoApiTest extends Model
{
	public function renewAllData($params) {
		foreach ($params as $param) {
			$current_rest_models = Restaurants::find()
				->select('gorko_id')
				->where(['active' => 1])
				->asArray()
				->all();
			$current_rest_ids = [];
			foreach ($current_rest_models as $key => $value) {
				array_push($current_rest_ids, $value['gorko_id']);
			}

			$current_room_models = Rooms::find()
				->select('gorko_id')
				->where(['active' => 1])
				->asArray()
				->all();

			$current_room_ids = [];
			foreach ($current_room_models as $key => $value) {
				array_push($current_room_ids, $value['gorko_id']);
			}

			$api_url = 'https://api.gorko.ru/api/v2/directory/venues?'.$param['params'];
			$api_per_page = '&per_page=';
			$api_page = '&page=';
			
			$ch_venues = curl_init();
			$ch_venues_url = $api_url.$api_per_page.'20'.$api_page.'1';
			
			curl_setopt($ch_venues, CURLOPT_HTTPHEADER, array("Cookie: ab_test_venue_city_show=1"));
		    curl_setopt($ch_venues, CURLOPT_URL, $ch_venues_url);
		    curl_setopt($ch_venues, CURLOPT_RETURNTRANSFER,true);
		    curl_setopt($ch_venues, CURLOPT_ENCODING, '');	    

			$venues = json_decode(curl_exec($ch_venues), true);
			curl_close($ch_venues);

			$gorko_rest_ids = [];
			$gorko_room_ids = [];

			foreach ($venues['restaurants'] as $key => $restaurant) {
				$gorko_rest_ids[$restaurant['id']] = null;
				foreach ($venues['restaurants'][$key]['rooms'] as $key => $room) {
					$gorko_room_ids[$room['id']] = null;
				}
				$queue_id = Yii::$app->queue->push(new AsyncRenewRestaurants([
					'gorko_id' => $restaurant['id'],
					'dsn' => Yii::$app->db->dsn,
					'watermark' => $param['watermark'],
					'imageHash' => $param['imageHash']
				]));
			}

			$page_count = $venues['meta']['pages_count'];

			$mh = curl_multi_init();
			$channels = [];

			if($page_count > 1){
				for ($i=2; $i <= $page_count; $i++) { 
					$channels[$i] = curl_init();
					$ch_venues_url = $api_url.$api_per_page.'20'.$api_page.$i;
					
					curl_setopt($channels[$i], CURLOPT_HTTPHEADER, array("Cookie: ab_test_venue_city_show=1"));
				    curl_setopt($channels[$i], CURLOPT_URL, $ch_venues_url);
				    curl_setopt($channels[$i], CURLOPT_RETURNTRANSFER,true);
				    curl_setopt($channels[$i], CURLOPT_ENCODING, '');
				    curl_multi_add_handle($mh, $channels[$i]);
				}
			}

			$running = null;
			do {
				curl_multi_exec($mh, $running);
			} while ($running);

			for ($i=2; $i <= $page_count; $i++) {
				curl_multi_remove_handle($mh, $channels[$i]);
			}

			$iter = 0;

			$imgFlag = true;

			foreach ($channels as $channel) {
				$venues = json_decode(curl_multi_getcontent($channel), true);
				foreach ($venues['restaurants'] as $key => $restaurant) {
					$gorko_rest_ids[$restaurant['id']] = null;
					foreach ($venues['restaurants'][$key]['rooms'] as $key => $room) {
						$gorko_room_ids[$room['id']] = null;
					}
					$queue_id = Yii::$app->queue->push(new AsyncRenewRestaurants([
						'gorko_id' => $restaurant['id'],
						'dsn' => Yii::$app->db->dsn,
						'watermark' => $param['watermark'],
						'imageHash' => $param['imageHash']
					]));
				}
			}



			foreach ($gorko_rest_ids as $id => $value) {
				if (($key = array_search($id, $current_rest_ids)) !== false) {
				    unset($current_rest_ids[$key]);
				}
			}

			foreach ($current_rest_ids as $key => $value) {
				$restaurant = Restaurants::find()
					->where(['gorko_id' => $value])
					->one();
				$restaurant->active = 0;
				$restaurant->save();
			}

			foreach ($gorko_room_ids as $id => $value) {
				if (($key = array_search($id, $current_room_ids)) !== false) {
				    unset($current_room_ids[$key]);
				}
			}			

			foreach ($current_room_ids as $key => $value) {
				$room = Rooms::find()
					->where(['gorko_id' => $value])
					->one();
				$room->active = 0;
				$room->save();
			}

			curl_multi_close($mh);
		}

		return;
	}

	public function showAllData($params) {
		foreach ($params as $param) {
			$current_rest_models = Restaurants::find()
				->select('gorko_id')
				->where(['active' => 1])
				->asArray()
				->all();
			$current_rest_ids = [];
			foreach ($current_rest_models as $key => $value) {
				array_push($current_rest_ids, $value['gorko_id']);
			}

			$current_room_models = Rooms::find()
				->select('gorko_id')
				->where(['active' => 1])
				->asArray()
				->all();

			$current_room_ids = [];
			foreach ($current_room_models as $key => $value) {
				array_push($current_room_ids, $value['gorko_id']);
			}

			$api_url = 'https://api.gorko.ru/api/v2/directory/venues?'.$param['params'];
			$api_per_page = '&per_page=';
			$api_page = '&page=';
			
			$ch_venues = curl_init();
			$ch_venues_url = $api_url.$api_per_page.'20'.$api_page.'1';
			
			curl_setopt($ch_venues, CURLOPT_HTTPHEADER, array("Cookie: ab_test_venue_city_show=1"));
		    curl_setopt($ch_venues, CURLOPT_URL, $ch_venues_url);
		    curl_setopt($ch_venues, CURLOPT_RETURNTRANSFER,true);
		    curl_setopt($ch_venues, CURLOPT_ENCODING, '');	    

			$venues = json_decode(curl_exec($ch_venues), true);
			curl_close($ch_venues);

			$gorko_rest_ids = [];
			$gorko_room_ids = [];

			echo '<pre>';
			print_r($venues['restaurants']);
			echo '</pre>';

			foreach ($venues['restaurants'] as $key => $restaurant) {
				$gorko_rest_ids[$restaurant['id']] = null;
				foreach ($venues['restaurants'][$key]['rooms'] as $key => $room) {
					$gorko_room_ids[$room['id']] = null;
				}
				//$queue_id = Yii::$app->queue->push(new AsyncRenewRestaurants([
				//	'gorko_id' => $restaurant['id'],
				//	'dsn' => Yii::$app->db->dsn,
				//	'watermark' => $param['watermark'],
				//	'imageHash' => $param['imageHash']
				//]));
			}

			$page_count = $venues['meta']['pages_count'];

			$mh = curl_multi_init();
			$channels = [];

			if($page_count > 1){
				for ($i=2; $i <= $page_count; $i++) { 
					$channels[$i] = curl_init();
					$ch_venues_url = $api_url.$api_per_page.'20'.$api_page.$i;
					
					curl_setopt($channels[$i], CURLOPT_HTTPHEADER, array("Cookie: ab_test_venue_city_show=1"));
				    curl_setopt($channels[$i], CURLOPT_URL, $ch_venues_url);
				    curl_setopt($channels[$i], CURLOPT_RETURNTRANSFER,true);
				    curl_setopt($channels[$i], CURLOPT_ENCODING, '');
				    curl_multi_add_handle($mh, $channels[$i]);
				}
			}

			$running = null;
			do {
				curl_multi_exec($mh, $running);
			} while ($running);

			for ($i=2; $i <= $page_count; $i++) {
				curl_multi_remove_handle($mh, $channels[$i]);
			}

			$iter = 0;

			$imgFlag = true;

			foreach ($channels as $channel) {
				$venues = json_decode(curl_multi_getcontent($channel), true);
				echo '<pre>';
				print_r($venues['restaurants']);
				echo '</pre>';
				foreach ($venues['restaurants'] as $key => $restaurant) {
					$gorko_rest_ids[$restaurant['id']] = null;
					foreach ($venues['restaurants'][$key]['rooms'] as $key => $room) {
						$gorko_room_ids[$room['id']] = null;
					}
					//$queue_id = Yii::$app->queue->push(new AsyncRenewRestaurants([
					//	'gorko_id' => $restaurant['id'],
					//	'dsn' => Yii::$app->db->dsn,
					//	'watermark' => $param['watermark'],
					//	'imageHash' => $param['imageHash']
					//]));
				}
			}



			//foreach ($gorko_rest_ids as $id => $value) {
			//	if (($key = array_search($id, $current_rest_ids)) !== false) {
			//	    unset($current_rest_ids[$key]);
			//	}
			//}

			//foreach ($current_rest_ids as $key => $value) {
			//	$restaurant = Restaurants::find()
			//		->where(['gorko_id' => $value])
			//		->one();
			//	$restaurant->active = 0;
			//	$restaurant->save();
			//}

			//foreach ($gorko_room_ids as $id => $value) {
			//	if (($key = array_search($id, $current_room_ids)) !== false) {
			//	    unset($current_room_ids[$key]);
			//	}
			//}			

			//foreach ($current_room_ids as $key => $value) {
			//	$room = Rooms::find()
			//		->where(['gorko_id' => $value])
			//		->one();
			//	$room->active = 0;
			//	$room->save();
			//}

			curl_multi_close($mh);
			exit;
		}

		return;
	}

	public function showOne($params) {
		foreach ($params as $param) {
			$api_url = 'https://api.gorko.ru/api/v2/restaurants/447257?embed=rooms,contacts&fields=address,params,covers,district&is_edit=1';
			
			$ch_venues = curl_init();
			$ch_venues_url = $api_url;
			
			curl_setopt($ch_venues, CURLOPT_HTTPHEADER, array("Cookie: ab_test_venue_city_show=1"));
		    curl_setopt($ch_venues, CURLOPT_URL, $ch_venues_url);
		    curl_setopt($ch_venues, CURLOPT_RETURNTRANSFER,true);
		    curl_setopt($ch_venues, CURLOPT_ENCODING, '');	    

			$venues = json_decode(curl_exec($ch_venues), true);
			curl_close($ch_venues);

			$curl = curl_init();
		    curl_setopt($curl, CURLOPT_URL, $ch_venues_url);
		    curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
		    curl_setopt($curl, CURLOPT_ENCODING, '');
		    $response = json_decode(curl_exec($curl), true);
		    curl_close($curl);

		    echo '<pre>';
		    print_r($response);
		    echo '<pre>';
		    exit;
		}
	}
}