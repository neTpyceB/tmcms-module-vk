<?php
namespace neTpyceB\TMCms\Modules\Vk\Object;

use Exception;
use neTpyceB\TMCms\Modules\CommonObject;

/**
 * Class VkAuth
 * @package neTpyceB\TMCms\Modules\Vk\Object
 *
 * @usage
$provider = new VkAuth(APP_ID, AP_SECRET_KEY, 'http://'. HOST .'/?provider=vkontakte');
if (!isset($_GET['code'])) {
    $provider->redirect($provider->getCode());
}
if (isset($_GET['code'])) {
    $provider->setCode($_GET['code']);
    $res = $provider->getToken();
    if ($res) {
        // We got an access token, let's now get the user's details
        $userDetails = $provider->getUserInfo($provider->getUserId());
        // Etc.
    }
}
 */
class VkAuth extends CommonObject
{
    const API_VERSION = '5.33';
    const REDIRECT_BLANC_URL = 'https://oauth.vk.com/blank.html';
    const AUTH_URL = 'https://oauth.vk.com/authorize?client_id={client_id}&scope={scope}&redirect_uri={redirect_uri}&response_type={response_type}&v={version}';
    const ACCESS_TOKEN_URL = 'https://oauth.vk.com/access_token?client_id={client_id}&client_secret={client_secret}&code={code}&redirect_uri={redirect_uri}';
    const METHOD_URL = 'https://api.vk.com/method/';

    //$scope = "offline,notify,friends,photos,audio,video,wall,docs,groups,email";
    /**
     * @var string
     */
    private static $app_id = '';
    /**
     * @var string
     */
    private static $secret_key = '';
    /**
     * @var string
     */
    private static $redirect_url = '';
    /**
     * @var array
     */
    private $scope = array('wall,offline,photos,docs,email');
    /**
     * @var string
     */
    private $code = '';
    /**
     * Токен доступа Вконтакте
     * @var string
     */
    private $access_token = '';

    /**
     * ID пользователя для запроса
     * @var string
     */
    private $user_id = 0;

    /**
     * @var string
     */
    private $user_email = '';

    /**
     * @param string $app_id
     * @param string $secret_key
     * @param string $redirect_url
     */
    public function __construct($app_id = '', $secret_key = '', $redirect_url = '')
    {
        if ($app_id) $this->setAppId($app_id);
        if ($secret_key) $this->setSecretKey($secret_key);
        if ($secret_key) $this->setSecretKey($secret_key);
    }

    /**
     * @param string $app_id
     * @return $this
     */
    public function setAppId($app_id)
    {
        self::$app_id = $app_id;

        return $this;
    }

    /**
     * @param string $secret_key
     * @return $this
     */
    public function setSecretKey($secret_key)
    {
        self::$secret_key = $secret_key;

        return $this;
    }

    /**
     * @param string $redirect_url
     * @return $this
     */
    public function setRedirectUrl($redirect_url)
    {
        self::$redirect_url = $redirect_url;

        return $this;
    }

    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setUserId($id)
    {
        $this->user_id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getUserEmail()
    {
        return $this->user_email;
    }

    /**
     * @param string $email
     * @return $this
     */
    public function setUserEmail($email)
    {
        $this->user_email = $email;

        return $this;
    }

    /**
     * Составляет URI для получение кода доступа к API
     * @param string $type
     * @return string
     */
    public function getCode($type = 'code')
    {
        if (is_array($this->scope)) {
            $scope = implode(',', $this->scope);
        } else {
            $scope = $this->scope;
        }

        $url = self::AUTH_URL;

        $url = str_replace('{client_id}', self::$app_id, $url);
        $url = str_replace('{scope}', $scope, $url);
        $url = str_replace('{redirect_uri}', self::$redirect_url, $url);
        $url = str_replace('{response_type}', $type, $url);
        $url = str_replace('{version}', self::API_VERSION, $url);

        return $url;
    }

    /**
     * @param string $code
     * @return $this
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Составляет запрос на получение токена доступа
     * Создаёт куки для имени пользователя и токена
     * @throws Exception
     * @return string
     */
    public function getToken()
    {
        if (!$this->code) {
            exit('Wrong code');
        }

        $url = self::ACCESS_TOKEN_URL;
        $url = str_replace('{code}', $this->code, $url);
        $url = str_replace('{client_id}', self::$app_id, $url);
        $url = str_replace('{client_secret}', self::$secret_key, $url);
        $url = str_replace('{redirect_uri}', self::$redirect_url, $url);

        if (function_exists('curl_init')) {

            $ku = curl_init();

            curl_setopt($ku, CURLOPT_URL, $url);
            curl_setopt($ku, CURLOPT_RETURNTRANSFER, true);

            $json = curl_exec($ku);
            curl_close($ku);
        } else {

            $json = file_get_contents($url);
        }

        $token = json_decode($json);

        if ($token->access_token) {
            $this->setToken($token->access_token);
            $this->setUserId($token->user_id);
            $this->setUserEmail($token->email);
            return true;
        } elseif ($token->error) {
            // Session error
            return false;
        }
    }

    /**
     * @param string $token
     * @return $this
     */
    public function setToken($token)
    {
        $this->access_token = $token;

        return $this;
    }

    /**
     * @param int $user_id
     * @return array
     */
    public function getUserInfo($user_id)
    {

        // Возвращает расширенную информацию о пользователях

        // Не требующий access_token

        // url - https://vk.com/dev/users.get

        // user_ids - (int) идентификатор пользователя, возможно перечисление через запятую
        // fields - список дополнительных полей, которые необходимо вернуть
        //          sex, bdate, city, country, timezone, photo_50, photo_100, photo_200_orig, has_mobile, contacts, education, online, counters, relation, last_seen, status, can_write_private_message, can_see_all_posts, can_post, universities
        // name_case - падеж для склонения имени и фамилии пользователя

        $params = array(
            'user_id' => $user_id,
            'fields' => 'first_name,last_name,nickname,screen_name,sex,bdate,city,country,timezone,photo_50,photo_100,photo_200_orig,has_mobile,contacts,education,online,counters,relation,last_seen,status,can_write_private_message,can_see_all_posts,can_post,universities'
        );

        $response = $this->api('users.get', $params);

        return $response;
    }

    /**
     * Выполнение вызова Api метода
     * @param string $method - метод, http://vk.com/dev/methods
     * @param array $vars - параметры метода
     * @param bool $access_token
     * @return array - выводит массив данных или ошибку (но тоже в массиве)
     */
    private function api($method = '', $vars = array(), $access_token = false)
    {
        if ($access_token) {
            $vars['access_token'] = $this->getAccessToken();
        }

        $url = $this->buildQuery($method, $vars);

        return $this->call($url);
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        return $this->access_token;
    }

    /**
     * Построение конечного URI для выхова
     * @param string $method
     * @param array $params
     * @return string
     */
    private function buildQuery($method, $params = array())
    {

        return self::METHOD_URL . $method . '?' . http_build_query($params);
    }

    /**
     * @param string $url
     * @return bool|mixed|string
     */
    private function call($url = '')
    {
        if (function_exists('curl_init')) {
            $json = $this->curlPost($url);
        } else {
            $json = file_get_contents($url);
        }

        $json = json_decode($json, true);

        if (isset($json['response'])) return $json['response'];

        return $json;
    }

    /**
     * @param string $url
     * @return bool|mixed
     */
    private function curlPost($url)
    {
        if (!function_exists('curl_init')) return false;
        $param = parse_url($url);
        if ($curl = curl_init()) {
            curl_setopt($curl, CURLOPT_URL, $param['scheme'] . '://' . $param['host'] . $param['path']);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $param['query']);
            $out = curl_exec($curl);
            curl_close($curl);
            return $out;
        }
        return false;
    }

    /**
     * @param int $user_id
     * @return array
     */
    public function getFollowers($user_id)
    {

        // Возвращает список идентификаторов пользователей, которые являются подписчиками пользователя. Идентификаторы пользователей в списке отсортированы в порядке убывания времени их добавления.

        // Не требующий access_token

        // url - https://vk.com/dev/users.getFollowers

        // user_id - (int) идентификатор пользователя
        // count - (int) количество подписчиков, информацию о которых нужно получить. По умолчанию 100. Максимальное значение 1000
        // fields - список дополнительных полей, которые необходимо вернуть
        //          sex, bdate, city, country, timezone, photo_50, photo_100, photo_200_orig, has_mobile, contacts, education, online, counters, relation, last_seen, status, can_write_private_message, can_see_all_posts, can_post, universities
        // name_case - падеж для склонения имени и фамилии пользователя

        $params = array(
            'user_id' => $user_id,
            'fields' => 'first_name,last_name,nickname,screen_name,sex,bdate,city,country,timezone,photo_50,photo_100,photo_200_orig,has_mobile,contacts,education,online,counters,relation,last_seen,status,can_write_private_message,can_see_all_posts,can_post,universities'
        );

        return $this->api('users.getFollowers', $params);
    }

    /**
     * @param int $user_id
     * @return array
     */
    public function getFriends($user_id)
    {

        // Возвращает информацию о друзьях пользователя

        // Не требующий access_token

        // url - https://vk.com/dev/friends.get

        // user_id - (int) идентификатор пользователя
        // count - (int) количество подписчиков, информацию о которых нужно получить. По умолчанию 100. Максимальное значение 1000
        // fields - список дополнительных полей, которые необходимо вернуть
        //          nickname, domain, sex, bdate, city, country, timezone, photo_50, photo_100, photo_200_orig, has_mobile, contacts, education, online, relation, last_seen, status, can_write_private_message, can_see_all_posts, can_post, universities
        // name_case - падеж для склонения имени и фамилии пользователя

        $params = array(
            'user_id' => $user_id,
            'fields' => 'first_name,last_name,nickname,screen_name,sex,bdate,city,country,timezone,photo_50,photo_100,photo_200_orig,has_mobile,contacts,education,online,counters,relation,last_seen,status,can_write_private_message,can_see_all_posts,can_post,universities'
        );

        $response = $this->api('friends.get ', $params);

        return $response;
    }

    /**
     * @return array
     */
    public function isAppUser($user_id)
    {

        // Возвращает информацию о том, установил ли пользователь приложение.

        // Не требующий access_token

        // url - https://vk.com/dev/users.isAppUser

        // user_id - (int) идентификатор пользователя

        $params = array(
            'user_id' => $user_id
        );

        $response = $this->api('users.isAppUser', $params);

        return $response;
    }

    /**
     * @param int $user_id
     * @return array
     */
    public function getAlbums($user_id)
    {

        // Возвращает список альбомов пользователя или сообщества.

        // Не требующий access_token

        // url - https://vk.com/dev/photos.getAlbums

        // owner_id - (int) идентификатор пользователя или сообщества
        // album_ids - перечисленные через запятую ID альбомов (по умолчанию - все)
        // count - (int) количество альбомов, которое нужно вернуть. (по умолчанию – все альбомы)

        $params = array(
            'owner_id' => $user_id
        );

        $response = $this->api('photos.getAlbums', $params);

        return $response;
    }

    /**
     * @param int $user_id
     * @param int $album_id
     * @return array
     */
    public function getPhotos($user_id, $album_id)
    {

        $album_id = '202712467';

        // Возвращает список фотографий в альбоме.

        // Не требующий access_token

        // url - https://vk.com/dev/photos.get

        // owner_id - (int) идентификатор пользователя или сообщества
        // album_id - (int) идентификатор альбома
        // count - (int) количество альбомов, которое нужно вернуть. (по умолчанию – все альбомы)

        $params = array(
            'owner_id' => $user_id,
            'album_id' => $album_id
        );

        $response = $this->api('photos.get', $params);

        return $response;
    }

    /**
     * @param int $count Интересующее количество постов
     * @param int $offset Смещение относительно самых новых постов
     * @param string $user_id Идентификационный номер пользователя
     * @return array
     */
    public function getWallPosts($user_id, $count = 5, $offset = null)
    {

        // Возвращает список записей со стены пользователя или сообщества.

        // Не требующий access_token

        // url - https://vk.com/dev/wall.get

        // $user_id - (int) идентификатор пользователя или сообщества
        // count - (int) количество записей, которое необходимо получить (но не более 100). По умолчанию 5
        // $offset - (int) для выборки определенного подмножества записей

        $params = array(
            'owner_id' => $user_id,
            'count' => $count,
            'offset' => $offset
        );

        $response = $this->api('wall.get', $params);

        return $response;
    }

    /**
     * @param int $user_id
     * @param string  $message
     * @param string  $attachment
     * @return array
     */
    public function createNewWallPost($user_id, $message = null, $attachment = null)
    {

        // Публикует новую запись на своей или чужой стене

        // Доступен только Standalone-приложениям

        // url - https://vk.com/dev/wall.post

        // owner_id - (int) идентификатор пользователя или сообщества
        // friends_only - (bool) 1 — запись будет доступна только друзьям, 0 — всем пользователям. По умолчанию - 0
        // message - текст сообщения (является обязательным, если не задан параметр attachments)
        // attachments - список объектов

        $params = array(
            'owner_id' => $user_id,
            'message' => $message,
            'attachment' => $attachment
        );

        $response = $this->api('wall.post', $params, true);

        return $response;
    }

    /**
     * @param $user_id
     * @param string  $message
     * @param string  $files
     * @return array|bool
     */
    public function createNewImagePost($user_id, $files = null, $message = null)
    {

        // Публикует новую фотографию в фотоальбоме пользователя

        // Доступен только Standalone-приложениям

        $files = array('image1.jpg', 'image2.jpg');

        if (count($files) == 0) return false;

        $images = $this->uploadPhoto($files);

        $response = '';

        foreach ($images as $image) {
            $params = array(
                'owner_id' => $user_id,
                'message' => $message,
                'attachment' => $image
            );

            $response = $this->api('wall.post', $params, true);
        }

        return $response;
    }

    /**
     * @param array $files
     * @param bool $gid
     * @return array|bool
     */
    public function uploadPhoto($files = array(), $gid = false)
    {

        if (!function_exists('curl_init')) return false;

        $data_json = $this->api('photos.getWallUploadServer', array('group_id' => intval($gid)), true);

        if (!isset($data_json['upload_url'])) return false;

        if (!is_array($files)) {
            $files = array($files);
        }

        $temp = array_chunk($files, 4);

        $files = array();
        $attachments = array();
        foreach ($temp[0] as $key => $data) {
            $path = realpath($data);
            if ($path) {
                $files['file' . ($key + 1)] = (class_exists('CURLFile', false)) ? new CURLFile(realpath($data)) : '@' . realpath($data);
            }
        }

        $upload_url = $data_json['upload_url'];
        $ch = curl_init($upload_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data; charset=UTF-8'));
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible;)');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $files);
        $upload_data = json_decode(curl_exec($ch), true);
        curl_close($ch);

        $upload_data['group_id'] = intval($gid);

        $response = $this->api('photos.saveWallPhoto', $upload_data, true);

        if (count($response) > 0) {
            foreach ($response as $photo) {
                $attachments[] = $photo['id'];
            }
        }

        return $attachments;
    }

}