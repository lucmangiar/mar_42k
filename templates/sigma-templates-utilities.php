<?php

class Sigma_Templates_Utilities {
	public static function hide_email($email) {
		$fields = explode('@', $email);
		return '*****@' . $fields[1];
	}
}
