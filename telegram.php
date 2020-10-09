<?php

//curl method get
function curlGet($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

//https://stackoverflow.com/a/9826656
function get_string_between($string, $start, $end){
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}

//https://stackoverflow.com/a/2790919
function start_with($string, $query) {
	return substr($string, 0, strlen($query)) === $query;
}

function fix_link_telegram($str) {
	return str_replace('href="#', 'href="https://core.telegram.org/bots/api#', $str);
}

function get_telegram_api($sub_method) {
	$core = curlGet("https://core.telegram.org/bots/api");
	$core_r = explode("\n", $core);
	$result = array();
	if (count($core_r) > 0) {
		$end_sub = false;
		$hasils = array();
		$cur_sub;
		$cur_method;
		$cur_des = '';
		$td_field;
		$td_param;
		$td_type;
		$td_req;
		$td_des;
		$td_info = array();
		foreach ($core_r as $key => $value) {
			if (isset($cur_sub) && isset($cur_method) && isset($cur_des) && start_with($value, '<h4>')) {
				$hasil = array();
				if ($cur_sub == $sub_method) {
					if ($sub_method == 'Available methods') {
						$hasil['method'] = $cur_method;
					}
					if ($sub_method == 'Available types') {
						$hasil['type'] = $cur_method;
					}
					$hasil['description'] = $cur_des;
					$cur_des = '';
					if (count($td_info) > 0) {
						$hasil['data'] = $td_info;
						$td_info = array();
					}
					array_push($hasils, $hasil);
				} else {
					if (!$end_sub) {
						$end_sub = true;
						if ($sub_method == 'Available methods') {
							$hasil['method'] = $cur_method;
						}
						if ($sub_method == 'Available types') {
							$hasil['type'] = $cur_method;
						}
						$hasil['description'] = $cur_des;
						$cur_des = '';
						if (count($td_info) > 0) {
							$hasil['data'] = $td_info;
							$td_info = array();
						}
						array_push($hasils, $hasil);
					}
				}
			}
			if (strpos($value, '<h3><a class="anchor" name="') !== false) {
				$cur_sub = get_string_between($value,'</a>', '</h3>');
			} else {
				if (isset($cur_sub) && $cur_sub == $sub_method) {
					if (start_with($value, '<h4>')) {
						$cur_method = get_string_between($value,'</a>', '</h4>');
					} else {
						if (isset($cur_method)) {
							if (start_with($value, '<td>')) {
								$cur_td = get_string_between($value, '<td>', '</td>');
								if ($sub_method == 'Available methods') {
									if (!isset($td_param)) {
										$td_param = $cur_td;
										if (strpos($td_param, 'href="#') !== false) {
											$td_param = fix_link_telegram($td_param);
										}
									} else if (!isset($td_type)) {
										$td_type = $cur_td;
										if (strpos($td_type, 'href="#') !== false) {
											$td_type = fix_link_telegram($td_type);
										}
									} else if (!isset($td_req)) {
										$td_req = $cur_td;
									} else if (!isset($td_des)) {
										$td_des = $cur_td;
										if (strpos($td_des, 'href="#') !== false) {
											$td_des = fix_link_telegram($td_des);
										}
									} else {
										//
									}
									if (isset($td_param) && isset($td_type) && isset($td_req) && isset($td_des)) {
										array_push($td_info, array('parameter'=>$td_param, 'type'=>$td_type, 'required'=>$td_req, 'description'=>$td_des));
										unset($td_param);
										unset($td_type);
										unset($td_req);
										unset($td_des);
									}
								}
								if ($sub_method == 'Available types') {
									if (!isset($td_field)) {
										$td_field = $cur_td;
										if (strpos($td_field, 'href="#') !== false) {
											$td_field = fix_link_telegram($td_field);
										}
									} else if (!isset($td_type)) {
										$td_type = $cur_td;
										if (strpos($td_type, 'href="#') !== false) {
											$td_type = fix_link_telegram($td_type);
										}
									} else if (!isset($td_des)) {
										$td_des = $cur_td;
										if (strpos($td_des, 'href="#') !== false) {
											$td_des = fix_link_telegram($td_des);
										}
									} else {
										//
									}
									if (isset($td_field) && isset($td_type) && isset($td_des)) {
										array_push($td_info, array('field'=>$td_field, 'type'=>$td_type, 'description'=>$td_des));
										unset($td_field);
										unset($td_type);
										unset($td_des);
									}
								}
							} else {
								if (start_with($value, '<p>')) {
									$cur_dess = get_string_between($value, '<p>', '</p>');
									if (strpos($cur_dess, 'href="#') !== false) {
										$cur_des .= fix_link_telegram($cur_dess);
									} else {
										$cur_des .= $cur_dess;
									}
								}
							}
						}
					}
				}
			}
		}
		$result[$sub_method] = $hasils;
	}
	return $result;
}

if (isset($_GET['sub'])) {
	$sub = $_GET['sub'];
	$response = get_telegram_api($sub);
	header("Content-Type: application/json; charset=UTF-8");
	echo json_encode($response);
}

?>