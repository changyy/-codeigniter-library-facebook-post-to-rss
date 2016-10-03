<?php

class Example extends CI_Controller {
	public function index() {
		echo time();
	}

	public function login() {
		
		$this->load->helper('url');
		$this->load->library('facebook_post_rss_library');
		if ($this->facebook_post_rss_library->oauth_check_login()) {
			echo $this->facebook_post_rss_library->oauth_token;
		} else {
			//redirect($this->facebook_post_rss_library->oauth_get_login_url(current_url()));
			echo ($this->facebook_post_rss_library->oauth_get_login_url(current_url()));
		}
	}

	public function rss($object_id, $page = 1) {
		$access_token = $this->input->get_post('access_token');

		$this->load->library('facebook_post_rss_library');
		if ($this->facebook_post_rss_library->token_validation($access_token)) {
			$post_id = array();
			$post = array();
			// https://developers.facebook.com/docs/graph-api/reference/v2.7/group/feed
			// https://developers.facebook.com/docs/graph-api/reference/v2.7/post
			$ret = $this->facebook_post_rss_library->query($access_token, "/$object_id/feed?fields=message,link,caption,created_time,description,object_id,picture,story,updated_time,admin_creator,name");
			if ($ret->getHttpStatusCode() == 200) {
				$data = $ret->getDecodedBody();
				if (isset($data['data']) && is_array($data['data'])) {
					foreach($data['data'] as $info) {
						array_push($post_id, $info['id']);
						array_push($post, $info);
					}
				}
				//print_r($data);
			}
			//print_r($post);

			$field_item_map = array(
				'title' => array( 
					'name' ,
					'message',
					'link',
				),
				'guid' => array(
					'id',
				),
				'author' => array(
				),
				'description' => array(
				),
			);

			echo 
				'<?xml version="1.0" encoding="UTF-8"?>',
				'<rss version="2.0">',
				'<channel>';

			date_default_timezone_set('Asia/Taipei');
			foreach($post as $article) {
				echo
					'<item>',
						//'<guid>',$article['id'],'</guid>',
						'<pubDate>',date('r', strtotime($article['created_time'])),'</pubDate>',
						'<link>',
							'https://www.facebook.com/',$object_id, '/posts/',preg_replace('/[0-9]+_/','', $article['id']),
						'</link>';

				foreach($field_item_map as $fieldname => $value_index) {
					echo "<$fieldname>";
					foreach($value_index as $index) {
						if (isset($article[$index]) && !empty($article[$index])) {
							echo htmlspecialchars($article[$index]);
							break;
						}
					}
					echo "</$fieldname>";

				}
				echo
					'</item>'
				;
			}
			echo
				'</channel>',
				'</rss>'
			;

		}
	}
}
