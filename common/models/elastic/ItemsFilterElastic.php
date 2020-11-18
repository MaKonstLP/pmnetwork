<?php

namespace common\models\elastic;

use yii\base\BaseObject;
use common\models\FilterItems;
use Yii;
use common\models\Filter;
use yii\helpers\ArrayHelper;

use Elasticsearch\ClientBuilder;

class ItemsFilterElastic extends BaseObject{

	public $items,
		   $total,
		   $pages,
		   $query;

	public function __construct($filter_arr = [], $limit = 24, $offset = 0, $widget_flag = false, $main_table, $elastic_model = false, $random = false, $must_not = false, $api_subdomen = false) {

		//echo '<pre style="display:none;">';
		//echo '</pre>';

		$filter_main_model = ArrayHelper::map(Filter::find()->all(), 'alias', 'type');

		$session = Yii::$app->session;
		if($session->get('seed')){
			$seed = $session->get('seed');
		}
		else{
			$rand_seed = random_int(1, 999999);
			$session->set('seed', $rand_seed);
			$seed = $rand_seed;
		}

		if($widget_flag){
			if($session->get('widget_seed')){
			$seed = $session->get('widget_seed');
			}
			else{
				$rand_seed = random_int(1, 999999);
				$session->set('widget_seed', $rand_seed);
				$seed = $rand_seed;
			}
		}

		if($random){
			$seed = random_int(1, 999999);
		}

		if($elastic_model){
			$query = $elastic_model::find();
		}
		elseif($main_table == 'rooms'){
			$query = ItemsElastic::find();
		}
		else{
			$query = RestaurantElastic::find();
			$query->source([
				'id',
				'restaurant_cover_url',
				'restaurant_name',
				'restaurant_address',
				'restaurant_min_capacity',
				'restaurant_max_capacity',
			]);
		}		

		$simple_query = [

		];

		$nested_query = [

		];

		$type_query = [

		];

		$location_query = [

		];
		
		foreach ($filter_arr as $key => $value_temp) {

			foreach ($value_temp as $value) {
				if($filter_main_model[$key] != 'input'){
					$filter_item_obj = FilterItems::find()
						->joinWith(['filter'])
						->where(['filter.alias' => $key])
						->andWhere(['value' => $value])
						->one();
					$filter_item_arr = json_decode($filter_item_obj->api_arr, true);

					foreach ($filter_item_arr as $filter_data) {

						$filter_query = new FilterQueryConstructorElastic($filter_data, $main_table);

						if($filter_query->nested){
							if(!isset($nested_query[$filter_query->query_type])){
								$nested_query[$filter_query->query_type] = [];
							}
						}
						elseif($filter_query->type){
							if(!isset($type_query[$filter_query->query_type])){
								$type_query[$filter_query->query_type] = [];
							}
						}
						elseif($filter_query->location){
							if(!isset($location_query[$filter_query->query_type])){
								$location_query[$filter_query->query_type] = [];
							}
						}
						else{
							if(!isset($simple_query[$filter_query->query_type])){
								$simple_query[$filter_query->query_type] = [];
							}
						}

						foreach ($filter_query->query_arr as $filter_value) {
							if($filter_query->nested){
								array_push($nested_query[$filter_query->query_type], $filter_value);
							}
							elseif($filter_query->type){
								array_push($type_query[$filter_query->query_type], $filter_value);
							}
							elseif($filter_query->location){
								array_push($location_query[$filter_query->query_type], $filter_value);
							}
							else{
								array_push($simple_query[$filter_query->query_type], $filter_value);
							}
						}
					}	
				}
				else{
					$filter_item_obj = FilterItems::find()
						->joinWith(['filter'])
						->where(['filter.alias' => $key])
						->one();
					$filter_item_arr = json_decode($filter_item_obj->api_arr, true);

					$filter_item_arr[0]['value'] = $filter_item_arr[0]['value'].$value;

					foreach ($filter_item_arr as $filter_data) {

						$filter_query = new FilterQueryConstructorElastic($filter_data, $main_table);

						if($filter_query->nested){
							if(!isset($nested_query[$filter_query->query_type])){
								$nested_query[$filter_query->query_type] = [];
							}
						}
						else{
							if(!isset($simple_query[$filter_query->query_type])){
								$simple_query[$filter_query->query_type] = [];
							}
						}

						foreach ($filter_query->query_arr as $filter_value) {
							$filter_query->nested ? 
								array_push($nested_query[$filter_query->query_type], $filter_value): 
								array_push($simple_query[$filter_query->query_type], $filter_value);
						}
					}	
				}	
			}
		}

		$final_query = [
			'bool' => [
				'must' => [],
			]
		];

		if($must_not){
			$final_query['bool']['must_not'] = ['match' => ['id' => $must_not]];
		}

		if(isset(Yii::$app->params['subdomen_id'])){
			$subdomen_id = Yii::$app->params['subdomen_id'];
		}
		elseif($api_subdomen){
			$subdomen_id = $api_subdomen;
		}
		else{
			$subdomen_id = 0;
		}

		if($subdomen_id){
			array_push($final_query['bool']['must'], ['match' => ['restaurant_city_id' => $subdomen_id]]);
		}			

		foreach ($simple_query as $type => $arr_filter) {
			$temp_type_arr = [];
			foreach ($arr_filter as $key => $value) {
				array_push($temp_type_arr, $value);
			}
			array_push($final_query['bool']['must'], ['bool' => ['should' => $temp_type_arr]]);
		}

		foreach ($nested_query as $type => $arr_filter) {
			$temp_type_arr = [];
			foreach ($arr_filter as $key => $value) {
				array_push($temp_type_arr, $value);
			}
			if($main_table == 'rooms'){
				array_push($final_query['bool']['must'], ['bool' => ['should' => $temp_type_arr]]);
			}
			else{
				array_push($final_query['bool']['must'], ['nested' => ["path" => "rooms","query" => ['bool' => ['must' => ['bool' => ['should' => $temp_type_arr]]]]]]);
			}
		}

		foreach ($type_query as $type => $arr_filter) {
			$temp_type_arr = [];
			foreach ($arr_filter as $key => $value) {
				array_push($temp_type_arr, $value);
			}
			if($main_table == 'rooms'){
				array_push($final_query['bool']['must'], ['bool' => ['should' => $temp_type_arr]]);
			}
			else{
				array_push($final_query['bool']['must'], ['nested' => ["path" => "restaurant_types","query" => ['bool' => ['must' => ['bool' => ['should' => $temp_type_arr]]]]]]);
			}
		}

		foreach ($location_query as $type => $arr_filter) {
			$temp_type_arr = [];
			foreach ($arr_filter as $key => $value) {
				array_push($temp_type_arr, $value);
			}
			if($main_table == 'rooms'){
				array_push($final_query['bool']['must'], ['nested' => ["path" => "restaurant_location","query" => ['bool' => ['must' => ['bool' => ['should' => $temp_type_arr]]]]]]);
			}
			else{
				array_push($final_query['bool']['must'], ['nested' => ["path" => "restaurant_location","query" => ['bool' => ['must' => ['bool' => ['should' => $temp_type_arr]]]]]]);
			}
		}

		if($subdomen_id){
			//echo '<pre>';
			//print_r($type_query);
			//echo '</pre>';
			//exit;
		}

		$final_query = [
			"function_score" => [
		      "query" => $final_query,
		      "functions" => [
	              [
	                  "filter" => [ "match" => [ "restaurant_commission" => "2" ] ],
	                  "random_score" => [], 
	                  "weight" => 100
	              ],
	              [
	              	"random_score" => [ "seed" => $seed ],
	              ]
	          ]
		    ]
		];
		
		$query->query($final_query);

		$query->limit($limit)
			  ->offset(($offset-1)*$limit);

		$elastic_search = $query->search();

		if(!$widget_flag){
			$this->total = $elastic_search['hits']['total'];
		}					 

		$this->items = $elastic_search['hits']['hits'];

		$this->pages = ceil($this->total / $limit);

		$this->query = $query;
		
	}

}