<?php

namespace common\models\api;

use Yii;
use yii\base\BaseObject;
use yii\helpers\ArrayHelper;
use common\models\Restaurants;
use common\models\elastic\ItemsFilterElastic;
use frontend\components\PremiumMixer;

class MapAll extends BaseObject
{

	public $coords;

	public function __construct($elastic_model, $subdomain_id, $filter = [], $type = 'restaurants', $url = '/ploshhadki/', $link_type = 'id', $with_premium = false)
	{
		if ($with_premium) {
			$items = PremiumMixer::getItemsWithPremium($filter, 9000, 1, false, $type, $elastic_model, false, false, false, false, false, true);
		} else {
			$items = new ItemsFilterElastic($filter, 9000, 1, false, $type, $elastic_model, false, false, $subdomain_id);
		}
		
		$this->coords = [
			'type' => 'FeatureCollection',
			'features' => [],
			'filter' => $filter
		];

		foreach ($items->items as $key => $item) {
			switch ($type) {
				case 'restaurants':
					foreach ($item->restaurant_images as $key => $image) {
						$map_preview = isset($image['subpath']) ? $image['subpath'] : '';
						break;
					}
					foreach ($item->restaurant_types as $key => $restaurant_type) {
						$rest_type = $restaurant_type['name'];
						break;
					}
					isset($item->restaurant_unique_id) ? $restaurant_unique_id = $item->restaurant_unique_id : $restaurant_unique_id = null;

					$lowest_price = $item->restaurant_price;
					foreach ($item->rooms as $key => $room) {
						if ($room['price'] < $lowest_price) {
							$lowest_price = $room['price'];
							break;
						}
					}

					array_push($this->coords['features'], [
						'type' => "Feature",
						'id' => $item->id,
						'unique_id' => $restaurant_unique_id,
						'geometry' => [
							'type' => "Point",
							'coordinates' => [$item->restaurant_latitude, $item->restaurant_longitude]
						],
						'properties' => [
							'balloonContent' => $item->restaurant_address,
							'organization' => $item->restaurant_name,
							'type' => $rest_type,
							'address' => $item->restaurant_address,
							'img' => $map_preview,
							'clusterCaption' => $item->restaurant_name,
							'link' => $link_type == 'id' ? $url . $item->id . '/' : $url . $item->restaurant_slug . '/',
							'link_unique' => $link_type == 'id' ? $url . $item->restaurant_unique_id . '/' : $url . $item->restaurant_slug . '/',
							'lowestPrice' => $lowest_price,
							'capacity' => $item->restaurant_max_capacity,
							'restaurant_slug' => isset($item->restaurant_slug) ? $item->restaurant_slug : null,
							'restaurant_unique_id' => isset($item->restaurant_unique_id) ? $item->restaurant_unique_id : null,
						]
					]);
					break;
				case 'rooms':
					foreach ($item->images as $key => $image) {
						$map_preview = isset($image['subpath']) ? $image['subpath'] : '';
						break;
					}
					isset($item->restaurant_unique_id) ? $restaurant_unique_id = $item->restaurant_unique_id : $restaurant_unique_id = null;
					array_push($this->coords['features'], [
						'type' => "Feature",
						'id' => $item->id,
						'unique_id' => $restaurant_unique_id,
						'geometry' => [
							'type' => "Point",
							'coordinates' => [$item->restaurant_latitude, $item->restaurant_longitude]
						],
						'properties' => [
							'balloonContent' => $item->restaurant_address,
							'organization' => $item->restaurant_name . ', ' . $item->name,
							'address' => $item->restaurant_address,
							'img' => $map_preview,
							'clusterCaption' => $item->name,
							'link' => '/catalog/' . $item->id . '/',
							'link_unique' => $link_type == 'id' ? $url . $item->unique_id . '/' : $url . $item->restaurant_slug . '/',
						]
					]);
					break;
			}
		}
	}
}
