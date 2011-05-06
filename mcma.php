<?php
/*
Copyright (c) 2011 Damian Zaremba <damian@damianzaremba.co.uk>

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

class mcma_bad_auth extends Exception { }

class mcma_api {
	private $server;
	private $port;
	private $username;
	private $password;

	function __construct($server='', $username='', $password='') {
		$this->server = $server;
		$this->port = 8888;
		$this->username = $username;
		$this->password = $password;
	}

	private function do_request($request_array) {
		$this->socket = @fsockopen($this->server, $this->port, $errno, $errstr, 10);

		if($this->socket) {
			$request_string = "";
			foreach($request_array as $key => $value) {
				$request_string .= urlencode($key) . "=" . urlencode($value) . "&";
			}

			$request = "GET /data.json?" . $request_string . " HTTP/1.0\r\n";
			$request .= "Host: " . $this->server . "\r\n";
			$request .= "Authorization: Basic " . base64_encode($this->username . ":" . $this->password) . "\r\n";
			$request .= "Connection: Close\r\n";
			$request .= "\r\n";
			fwrite($this->socket, $request);

			$return_data = "";
			while(!feof($this->socket)) {
				$return_data .= fread($this->socket, 2048);
			}
			@fclose($this->socket);

			$headers = array();
			$parts = explode("\r\n\r\n", $return_data);
			$header_data = explode("\r\n", $parts[0]);
			unset($parts[0]);
			$body_data = implode("\r\n\r\n", $parts);

			$status_data = explode(" ", $header_data[0]);
			$return_code = (int)$status_data[1];

			if($return_code === 403 || $return_code === 401) {
				throw new mcma_bad_auth();
			}

			if($return_code === 200) {
				$data = @json_decode($body_data);
				if($data) {
					return (array)$data;
				}
			}
		}

		return False;
	}

	public function user_in_whitelist($member_name) {
		$whitelist_data = $this->get_config_item("whitelist");
		$whitelist = $whitelist_data['value'];

		if($whitelist !== "none") {
			foreach(explode(",", $whitelist) as $item) {
				$item = trim($item);
				if(strtolower($item) === strtolower($member)) {
					return True;
				}
			}
		}

		return False;
	}

	public function delete_user_from_whitelist($memeber_name) {
		if(!$this->user_in_whitelist($member_name)) {
			return False;
		}

		$whitelist_data = $this->get_config_item("whitelist");
		$whitelist = $whitelist_data['value'];
		$new_whitelist = "";

		if($whitelist !== "none") {
			foreach(explode(",", $whitelist) as $item) {
				$item = trim($item);
				if($item !== $member) {
					$new_whitelist .= $item . ",";
				}
			}
		}

		$this->set_config_item("whitelist", $new_whitelist);
		return True;
	}

	public function add_user_to_whitelist($member_name) {
		if($this->user_in_whitelist($member_name)) {
			return False;
		}

		$whitelist_data = $this->get_config_item("whitelist");
		$whitelist = $whitelist_data['value'];

		if($whitelist !== "none") {
			$new_whitelist = $whitelist . "," . $member_name;
		} else {
			$new_whitelist = $member_name;
		}
		$data = $this->set_config_item("whitelist", $new_whitelist);
		return True;
	}

	public function group_member($group_name, $member_name) {
		$group_data = $this->get_group_members($group_name);
		if(in_array($member_name, $group_data['members'])) {
			return True;
		}
		return False;
	}

	public function is_group($group_name) {
		$groups_data = $this->get_groups();		
		if(in_array($group_name, $groups_data['groups'])) {
			return True;
		}
		return False;
	}

	public function add_user_to_group($group_name, $member_name) {
		if(!$this->is_group($group_name)) {
			$this->add_group($group_name);
		}

		if(!$this->group_member($group_name, $member_name)) {
			$data = $this->add_group_member($group_name, $member_name);
			return True;
		}

		return False;
	}

	public function send_server_message($message) {
		$req_data = array(
			"req" => "sendchat",
			"message" => $message,
		);

		$data = $this->do_request($req_data);
		if($data) {
			return $data;
		}
		return False;
	}

	public function get_server_console($after_timestamp=0) {
		$req_data = array(
			"req" => "getchat",
			"sine" => $after_timestamp,
		);

		$data = $this->do_request($req_data);
		if($data) {
			return $data;
		}
		return False;
	}

	public function get_server_status() {
		$req_data = array(
			"req" => "status",
		);

		$data = $this->do_request($req_data);
		if($data) {
			return $data;
		}
		return False;
	}

	public function get_mcmyadmin_version() {
		$req_data = array(
			"req" => "versions",
		);

		$data = $this->do_request($req_data);
		if($data) {
			return $data;
		}
		return False;
	}

	public function get_group_members($group_name) {
		$req_data = array(
			"req" => "getgroupinfo",
			"grp" => $group_name,
		);

		$data = $this->do_request($req_data);
		if($data) {
			return $data;
		}
		return False;
	}

	public function get_groups() {
		$req_data = array(
			"req" => "getgrouplist",
		);

		$data = $this->do_request($req_data);
		if($data) {
			return $data;
		}
		return False;
	}

	public function add_group($group_name) {
		$req_data = array(
			"req" => "addgroupvalue",
			"type" => "groupslist",
			"grp" => "group",
			"value" => $group_name,
		);

		$data = $this->do_request($req_data);
		if($data) {
			return $data;
		}
		return False;
	}

	public function add_group_member($group_name, $member_name) {
		$req_data = array(
			"req" => "addgroupvalue",
			"type" => "groupmembers",
			"grp" => $group_name,
			"value" => $member_name,
		);

		$data = $this->do_request($req_data);
		if($data) {
			return $data;
		}
		return False;
	}

	public function delete_group_member($group_name, $member_name) {
		$req_data = array(
			"req" => "removegroupvalue",
			"type" => "groupmembers",
			"grp" => $group_name,
			"value" => $member_name,
		);

		$data = $this->do_request($req_data);
		if($data) {
			return $data;
		}
		return False;
	}

	public function delete_group($group_name) {
		$req_data = array(
			"req" => "removegroupvalue",
			"type" => "groupslist",
			"grp" => "group",
			"value" => $group_name,
		);

		$data = $this->do_request($req_data);
		if($data) {
			return $data;
		}
		return False;
	}

	public function get_config_item($item_name) {
		$req_data = array(
			"req" => "getconfig",
			"key" => $item_name,
		);

		$data = $this->do_request($req_data);
		if($data) {
			return $data;
		}
		return False;
	}

	public function set_config_item($item_name, $item_value) {
		$req_data = array(
			"req" => "setconfig",
			"key" => $item_name,
			"value" => $item_value,
		);

		$data = $this->do_request($req_data);

		if($data) {
			return $data;
		}
		return False;
	}
}

function mcma_ConfigOptions() {
	$configarray = array(
		"Group Name" => array(
			"Type" => "text",
			"Size" => "50",
		),

		"Whitelist user" => array(
			"Type" => "yesno",
			"Description" => ""
		),
	);
	return $configarray;
}

function mcma_CreateAccount($params) {
	$mcma = new mcma_api($params["serverip"], $params["serverusername"], $params["serverpassword"]);

	try {
		if(array_key_exists("Minecraft username", $params['customfields']) && !empty($params['customfields']['Minecraft username'])) {
			if(array_key_exists("configoption1", $params) && !empty($params['configoption1'])) {
				$data = $mcma->add_user_to_group($params['configoption1'], $params['customfields']['Minecraft username']);
			} else {
				return "No group name specified";
			}
		} else {
			return "No minecraft username specified";
		}
	} catch (mcma_bad_auth $e) {
		return "Bad server details (auth fail)";
	}

	try {
		if(array_key_exists("configoption2", $params) && !empty($params['configoption2']) && $params['configoption2'] === "on") {
			if(array_key_exists("Minecraft username", $params['customfields']) && !empty($params['customfields']['Minecraft username'])) {
				$data = $mcma->add_user_to_whitelist($params['customfields']['Minecraft username']);
			} else {
				return "No minecraft username specified";
			}
		} else {
			return "No group name specified";
		}
	} catch (mcma_bad_auth $e) {
		return "Bad server details (auth fail)";
	}

	return "success";
}

function mcma_TerminateAccount($params) {
	$mcma = new mcma_api($params["serverip"], $params["serverusername"], $params["serverpassword"]);

	try {
		if(array_key_exists("Minecraft username", $params['customfields']) && !empty($params['customfields']['Minecraft username'])) {
			if(array_key_exists("configoption1", $params) && !empty($params['configoption1'])) {
				$data = $mcma->delete_group_member($params['configoption1'], $params['customfields']['Minecraft username']);
			} else {
				return "No group name specified";
			}
		} else {
			return "No minecraft username specified";
		}
	} catch (mcma_bad_auth $e) {
		return "Bad server details (auth fail)";
	}

	return "success";
}

function mcma_SuspendAccount($params) {
	$mcma = new mcma_api($params["serverip"], $params["serverusername"], $params["serverpassword"]);

	try {
		if(array_key_exists("Minecraft username", $params['customfields']) && !empty($params['customfields']['Minecraft username'])) {
			if(array_key_exists("configoption1", $params) && !empty($params['configoption1'])) {
				$data = $mcma->delete_group_member($params['configoption1'], $params['customfields']['Minecraft username']);
			} else {
				return "No group name specified";
			}
		} else {
			return "No minecraft username specified";
		}
	} catch (mcma_bad_auth $e) {
		return "Bad server details (auth fail)";
	}

	return "success";
}

function mcma_UnsuspendAccount($params) {
	$mcma = new mcma_api($params["serverip"], $params["serverusername"], $params["serverpassword"]);

	try {
		if(array_key_exists("Minecraft username", $params['customfields']) && !empty($params['customfields']['Minecraft username'])) {
			if(array_key_exists("configoption1", $params) && !empty($params['configoption1'])) {
				$data = $mcma->add_user_to_group($params['configoption1'], $params['customfields']['Minecraft username']);
			} else {
				return "No group name specified";
			}
		} else {
			return "No minecraft username specified";
		}
	} catch (mcma_bad_auth $e) {
		return "Bad server details (auth fail)";
	}

	try {
		if(array_key_exists("configoption2", $params) && !empty($params['configoption2']) && $params['configoption2'] === "on") {
			if(array_key_exists("Minecraft username", $params['customfields']) && !empty($params['customfields']['Minecraft username'])) {
				$data = $mcma->add_user_to_whitelist($params['customfields']['Minecraft username']);
			} else {
				return "No minecraft username specified";
			}
		} else {
			return "No group name specified";
		}
	} catch (mcma_bad_auth $e) {
		return "Bad server details (auth fail)";
	}

	return "success";
}

function mcma_AdminLink($params) {
	return '<a href="http://' . $params["serverusername"] . ':' . $params["serverpassword"] . '@' . $params["serverip"] . ':8888" target="_blank">Login</a>';
}

function mcma_AdminCustomButtonArray() {
	$buttonarray = array(
		"Sync Group" => "server_sync",
	);

	return $buttonarray;
}

function mcma_server_sync($params) {
	$mcma = new mcma_api($params["serverip"], $params["serverusername"], $params["serverpassword"]);

	try {
		if(array_key_exists("Minecraft username", $params['customfields']) && !empty($params['customfields']['Minecraft username'])) {
			if(array_key_exists("configoption1", $params) && !empty($params['configoption1'])) {
				$data = $mcma->add_user_to_group($params['configoption1'], $params['customfields']['Minecraft username']);
			} else {
				return "No group name specified";
			}
		} else {
			return "No minecraft username specified";
		}
	} catch (mcma_bad_auth $e) {
		return "Bad server details (auth fail)";
	}

	try {
		if(array_key_exists("configoption2", $params) && !empty($params['configoption2']) && $params['configoption2'] === "on") {
			if(array_key_exists("Minecraft username", $params['customfields']) && !empty($params['customfields']['Minecraft username'])) {
				$data = $mcma->add_user_to_whitelist($params['customfields']['Minecraft username']);
			} else {
				return "No minecraft username specified";
			}
		} else {
			return "No group name specified";
		}
	} catch (mcma_bad_auth $e) {
		return "Bad server details (auth fail)";
	}

	return "success";
}
?>
