<?php

/*
    Refrences:
    * https://www.educative.io/answers/how-to-clean-form-input-data-in-php
    * https://www.geeksforgeeks.org/php/how-to-validate-and-sanitize-user-input-with-php/
*/

class Common {
    public static function clearInput($value) {
        if (is_array($value)) {
            return array_map([self::class, 'clearInput'], $value);
        }

        $value = trim($value);
        $value = stripslashes($value);
        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

        return $value;
    }

    public static function get($key) {
        if (!isset($_GET[$key])) {
            return null;
        }

        return self::clearInput($_GET[$key]);
    }

    public static function post($key) {
        if (!isset($_POST[$key])) {
            return null;
        }

        return self::clearInput($_POST[$key]);
    }

    // login.php --> /login.php?user_id ...
    public static function createLinkToSitePage(string $page, array $map = []) : string {
        $url = "";
        $url .= "/";
        $url .= $page;

        if (($user = SessionController::getUser()) !== null) {
            $map["user_id"] = $user->getUserID();
        }

        if (!empty($map)) {
            $url .= "?";
            $i=0;
            foreach ($map as $key => $value) {
                if ($i !== 0) $url .= "&";
                $url .= $key;
                $url .= "=";
                $url .= $value;

                $i++;
            }
        }

        return $url;
    }
}
