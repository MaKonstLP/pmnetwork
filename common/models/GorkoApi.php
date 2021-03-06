<?php
namespace common\models;

use Yii;
use yii\base\Model;
use common\models\Restaurants;
use common\models\Rooms;
use common\components\AsyncRenewRestaurants;
use common\components\AsyncRenewImages;

class GorkoApi extends Model
{
	public function renewAllData($params) {
		foreach ($params as $param) {			
			if($param['subdomens']){
				$rest_where = [
					'active' => 1,
					'city_id' => $param['subdomen']
				];
			}
			else{
				$rest_where = ['active' => 1];
			}

			$current_rest_models = Restaurants::find()
				->with('rooms')
				->select('gorko_id')
				->where($rest_where)
				->all();

			//СУЩЕСТВУЮЩИЕ РЕСТОРАНЫ/ЗАЛЫ МАССИВ
			$current_rest_ids = [];
			$current_room_ids = [];
			foreach ($current_rest_models as $key => $value) {
				array_push($current_rest_ids, $value->gorko_id);
				foreach ($value->rooms as $keyr => $valuer) {
					array_push($current_room_ids, $valuer->gorko_id);
				}
			}			

			$api_url = 'https://api.gorko.ru/api/v3/venuecard?list[seed]=1&entity[languageId]=1&list[page]=1&list[perPage]=10000&list[typeId]=1&'.$param['params'];

			//ПЕРВЫЙ ЗАПРОС ПО API			
			$ch_venues = curl_init();
			
			curl_setopt($ch_venues, CURLOPT_HTTPHEADER, array("Cookie: ab_test_venue_city_show=1; ab_test_perfect_venue_samara=1"));
		    curl_setopt($ch_venues, CURLOPT_URL, $api_url);
		    curl_setopt($ch_venues, CURLOPT_RETURNTRANSFER,true);
		    curl_setopt($ch_venues, CURLOPT_ENCODING, '');	    

			$venues = json_decode(curl_exec($ch_venues), true);
			curl_close($ch_venues);

			$gorko_rest_ids = [];
			$gorko_room_ids = [];

			$page_count = $venues['meta']['totalPages'];

			//ВТОРОЙ ПАКЕТ ЗАПРОСОВ В API, В ЗАВИСИМОСТИ ОТ КОЛ-ВА ЭЛЕМЕНТОВ
			//$mh = curl_multi_init();
			//$channels = [];

			//if($page_count > 1){
			//	for ($i=2; $i <= $page_count; $i++) { 
			//		$channels[$i] = curl_init();
			//		$ch_venues_url = $api_url.$api_per_page.'20'.$api_page.$i;
			//		
			//		curl_setopt($channels[$i], CURLOPT_HTTPHEADER, array("Cookie: ab_test_venue_city_show=1; ab_test_perfect_venue_samara=1"));
			//	    curl_setopt($channels[$i], CURLOPT_URL, $ch_venues_url);
			//	    curl_setopt($channels[$i], CURLOPT_RETURNTRANSFER,true);
			//	    curl_setopt($channels[$i], CURLOPT_ENCODING, '');
			//	    curl_multi_add_handle($mh, $channels[$i]);
			//	}
			//}
//
			//$running = null;
			//do {
			//	curl_multi_exec($mh, $running);
			//} while ($running);
//
			//for ($i=2; $i <= $page_count; $i++) {
			//	curl_multi_remove_handle($mh, $channels[$i]);
			//}

			//ОБРАБОТКА ПЕРВОГО ПУЛА РЕСТОРАНОВ
			foreach ($venues['entity'] as $key => $restaurant) {
				$gorko_rest_ids[$restaurant['id']] = null;
				foreach ($venues['entity'][$key]['room'] as $key => $room) {
					$gorko_room_ids[$room['id']] = null;
				}
			}

			//ОБРАБОТКА ВТОРОГО ПУЛА РЕСТОРАНОВ
			//foreach ($channels as $channel) {
			//	$venues = json_decode(curl_multi_getcontent($channel), true);
			//	foreach ($venues['restaurants'] as $key => $restaurant) {
			//		$gorko_rest_ids[$restaurant['id']] = null;
			//		foreach ($venues['restaurants'][$key]['rooms'] as $key => $room) {
			//			$gorko_room_ids[$room['id']] = null;
			//		}
			//	}
			//}
			//curl_multi_close($mh);

			$log = file_get_contents('/var/www/pmnetwork/log/manual_samara_bd.log');
			$log = json_decode($log, true);
			$log[time()] = ['rest_ids' => $gorko_rest_ids, 'api_url' => $api_url];
			$log = json_encode($log);
			file_put_contents('/var/www/pmnetwork/log/manual_samara_bd.log', $log);

			//СБРОС АКТИВНОСТИ РЕСТОРАНОВ ИЗ БАЗЫ
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

			//СБРОС АКТИВНОСТИ ЗАЛОВ ИЗ БАЗЫ
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

			

			//СОЗДАНИЕ ОЧЕРЕДИ ДЛЯ ОБНОВЛЕНИЯ РЕСТОРАНОВ
			foreach ($gorko_rest_ids as $key => $value) {
				$queue_id = Yii::$app->queue->push(new AsyncRenewRestaurants([
					'gorko_id' 	=> $key,
					'dsn' 		=> $param['dsn'],
					'watermark' => $param['watermark'],
					'imageHash' => $param['imageHash'],
					'only_comm' => $param['only_comm']
				]));
			}
		}

		return count($gorko_rest_ids)."\n";
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
			exit;
		}

		return;
	}

	public function showOne($params) {
		foreach ($params as $param) {
			$api_url = 'https://api.gorko.ru/api/v2/restaurants/331635?embed=rooms,contacts&fields=address,params,covers,district&is_edit=1';
			
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