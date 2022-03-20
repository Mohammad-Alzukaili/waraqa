<?php
namespace Mawdoo3\Waraqa\Services\Mediawiki;

use Exception;
use Mawdoo3\Waraqa\Models\CacheTable;
use Mawdoo3\Waraqa\Models\WaraqaUser;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 *
 */
class UpdateWaraqaUsersData {

    private $updatedUsers = [];
    public function __construct()
    {
       Log::info('== Start Update Waraqa Users Data Class ==');
    }
    public function getArticlesManpower($params, $authorization)
    {
        Log::info('Start method: getArticlesManpower()');

        try {
            $url = Config::get('waragaIntegration.WARAQA_URL') . "client-api/articles/manpower";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $authorization, 'Accept: application/json'));
            $response = json_decode(curl_exec($ch));
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpcode == 422) {
                $notExist = array_values((array)$response->errors);
                for ($i = 0; $i < count($params['articles']); $i++)
                    for ($inc = 0; $inc < count($notExist); $inc++)
                        if (strpos($notExist[$inc][0], $params['articles'][$i]))
                            unset($params['articles'][$i]);
                return $this->getArticlesManpower($params, $authorization);
            }
            curl_close($ch);
            if ($httpcode != 200) {
               Log::warning('method getArticlesManpower() params', [$params]);
                Log::warning('method getArticlesManpower() response', [$response]);
            }
            return $response->data;
        } catch (Exception $e) {
            print_r($e);
            Log::error('method getArticlesManpower() exeption', [$e->getMessage()]);
            return null;
        }
    }

    public function checkArticlesUsers($authorization, $offset)
    {
        $dbr = wfGetDB(DB_MASTER);
        $articles_ids_db = $dbr->select(
            array('page'),
            array('page.waraqa_article_id'),
            array("page.waraqa_users_id is not null"),
            __METHOD__,
            array("LIMIT" => 100, "OFFSET" => $offset)
        );
        $articles_ids = [];
        foreach ($articles_ids_db as $row) {
            $articles_ids[] = $row->waraqa_article_id;
        }
        echo "\nArticles ID's Count:" . count($articles_ids) . "\n";
        $params = [
            'articles' => $articles_ids
        ];
        $articlesManpower = $this->getArticlesManpower($params, $authorization);
        $this->logger->debug('method checkArticlesUsers() :response from getArticlesManpower()', [is_null($articlesManpower)]);
        if (is_null($articlesManpower)) return;
        echo "\nGet Articles Manpower Response Count: " . count($articlesManpower) . "\n";
        $this->logger->debug("Get Articles Manpower Response Count: " . count($articlesManpower));
        foreach ($articlesManpower as $row) {
            $article_id = $row->id;
            // Update or create writer
            if (!empty($row->writer->id)) {
                $id = $this->updateOrCreateUser($row->writer);
            }
            if (!empty($row->general_proofreader->id)) {
                $id = $this->updateOrCreateUser($row->general_proofreader);
                $dbr->update(
                    "page",
                    array(
                        'waraqa_proofreader_id' => $id
                    ),
                    array("waraqa_article_id" => $article_id)
                );
            }
            if (!empty($row->content_proofreader->id)) {
                $id = $this->updateOrCreateUser($row->content_proofreader);
                $dbr->update(
                    "page",
                    array(
                        'waraqa_proofreader_id' => $id
                    ),
                    array("waraqa_article_id" => $article_id)
                );
            }
        }
    }
    public function updateOrCreateUser($row) //todo
    {
        //print_r($this->updatedUsers);
        if ($this->isWaraqaUserExists($row->id)) {
            // Update
            return $this->updateUser($row);
        } else {
            // Insert
            return $this->insertUser($row);
        }
    }
    private function insertUser($row)
    {
        // $this->logger->debug('inserting user', ['user_id' => $row->id]);
        // $dbr = wfGetDB(DB_MASTER);
        // $id = $dbr->nextSequenceValue('waraqa_user_id_seq');
        if (is_null($row->profile_image)) {
            $row->profile_picture = $row->gender == "ذكر" ? "/rf/images/doctor_icon_m.svg" : "/rf/images/doctor_icon_f.svg";
        } else {
            $newFile = $row->first_name . "_" . $row->last_name . "_$row->id.jpg";
            // $this->optimizeImage($row->profile_image, $newFile, false); //todo
            $row->profile_picture = $newFile;
        }

        // if user inserted, then clear فريق_استشاري page cache

        // $dbr->insert("waraqa_users", array('user_id' => $row->id,'user_name' => $row->first_name . " " . $row->last_name,'bio' => $row->brief,'picture' => $row->profile_picture,'gender' => $row->gender,'speciality' => $row->sub_major,'social' => json_encode($row->social_media_accounts),'jobs' => json_encode($row->jobs),));
        $waraqaUserId = WaraqaUser::insertGetId(
            array(
                'user_id' => $row->id,
                'user_name' => $row->first_name . " " . $row->last_name,
                'bio' => $row->brief,
                'picture' => $row->profile_picture,
                'gender' => $row->gender,
                'speciality' => $row->sub_major,
                'social' => json_encode($row->social_media_accounts),
                'jobs' => json_encode($row->jobs),
            )
        );

        // $isMawdoo3TeamCached = $dbr->select('cache_table', ['*'], ['url' => "فريق_استشاري"]);
        $isMawdoo3TeamCached = CacheTable::where('url' , "فريق_استشاري")->first();

        // $inserted_id = $dbr->selectField('waraqa_users', 'id', ['user_id' => $row->id]);

        if (empty($isMawdoo3TeamCached)){
            // $dbr->insert('cache_table', array('url' => "فريق_استشاري"));
            CacheTable::insert(array('url' => "فريق_استشاري"));
        }
        // $this->logger->debug('user insetred', ['user_id' => $row->id]);
        return $waraqaUserId;
    }

    public function updateUser($row)
    {

        if (!in_array($row->id, $this->updatedUsers)) {
            // $this->logger->debug('updating user', ['user_id' => $row->id]);
            if (is_null($row->profile_image) || $row->profile_image == "") {
                $row->profile_picture = $row->gender == "ذكر" ? "/rf/images/doctor_icon_m.svg" : "/rf/images/doctor_icon_f.svg";
            } else {
                $newFile = $row->first_name . "_" . $row->last_name . "_$row->id.jpg";
                // $this->optimizeImage($row->profile_image, $newFile, pathinfo($row->profile_image, PATHINFO_EXTENSION) == ""); //todo
                $row->profile_picture = $newFile;
            }

            // $dbr->update("waraqa_users",array('user_name' => $row->first_name . " " . $row->last_name,'bio' => $row->brief,'picture' => $row->profile_picture,'gender' => $row->gender,'speciality' => $row->sub_major,'social' => json_encode($row->social_media_accounts),'jobs' => json_encode($row->jobs),),array("user_id" => $row->id));
             WaraqaUser::where("user_id" , $row->id)->update(
                array(
                    'user_name' => $row->first_name . " " . $row->last_name,
                    'bio' => $row->brief,
                    'picture' => $row->profile_picture,
                    'gender' => $row->gender,
                    'speciality' => $row->sub_major,
                    'social' => json_encode($row->social_media_accounts),
                    'jobs' => json_encode($row->jobs),
            ));

            $this->updatedUsers[] = $row->id; // if user updated, then clear فريق_استشاري page cache and users profile page eg. الخبير:esteshary_editor_1285
            // $isMawdoo3TeamCached = $dbr->select('cache_table', ['*'], ['url' => "فريق_استشاري"]);
            $isMawdoo3TeamCached = CacheTable::where('url' , "فريق_استشاري")->exists();

            if ($isMawdoo3TeamCached == false){
                // $dbr->insert('cache_table', array('url' => "فريق_استشاري"));
                CacheTable::insert(array('url' => "فريق_استشاري"));
            }


            $profileSlug = "الخبير:" . $row->first_name . "_" . $row->last_name . "_" . $row->id;

            // $isprofileSlugCached = $dbr->select('cache_table', ['*'], ['url' => $profileSlug]);
            $isProfileSlugCached = CacheTable::where('url' , "فريق_استشاري")->first();

            if (empty($isProfileSlugCached)){
                // $dbr->insert('cache_table', array('url' => $profileSlug));
                CacheTable::insert(array('url' => $profileSlug));
            }

            Log::info('user updated', ['user_id' => $row->id]);
        }

        // $user = $dbr->selectRow(array('waraqa_users'),array('waraqa_users.id'),array("waraqa_users.user_id =" . $row->id),__METHOD__,array());
        return WaraqaUser::where('user_id',$row->id)->first()->id;
        // return $user->id;
    }

    private function isWaraqaUserExists($id)
    {
        // $dbr = wfGetDB(DB_SLAVE);
        // $writer = $dbr->select(
        //     array('waraqa_users'),
        //     array('waraqa_users.user_id'),
        //     array("waraqa_users.user_id =" . $id),
        //     __METHOD__,
        //     array()
        // );

        return  WaraqaUser::where("user_id" , $id)->exists();

    }

    public function login()
    {
        $url = Config::get('waragaIntegration.WARAQA_URL') . "client-api/login";
        $params = [
            'access_id' => Config::get('waragaIntegration.CLIENT_ACCESS_ID'),
            'password' => Config::get('waragaIntegration.CLIENT_PASSWORD'),
        ];
        $res = json_decode($this->MwCURL($url, $params, "POST"));
        if (isset($res->data->token)) echo "\nLogged in\n";
        return $res->data->token;
    }

    private function MwCURL(string $url, array $params = [], $method, $authorization = "")
    {
        Log::info('method MwCURL() Start');
        $curl = curl_init();
        if ($authorization != "")
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $authorization));
        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_POSTFIELDS => $params
            )
        );
        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $redirectURL = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
        if (strpos($redirectURL, '/admin/login'))
            $response = [];
        if ($httpcode != 200) {
            Log::info('method MwCURL()', [
                'url' => $url,
                'params' => $params,
                'method' => $method,
                'response' => $response
            ]);
        }
        curl_close($curl);
        return $response;
    }


    private function optimizeImage($url, $newFile, $isBase64) //todo
    {
        $this->logger->debug('optimizing user image');
        $imageTempName = '/tmp/origins/profiles/' . $newFile;
        if ($isBase64) {
            $this->logger->debug('optimizing user image base64');
            $image = ImageResize::createFromString(base64_decode($url));
            $this->logger->debug('optimizing user image base64 optimized');
        } else {
            $this->logger->debug('optimizing user image file');
            $image = new ImageResize($this->prepareProfileImageToUpload($url, $newFile));
            $this->logger->debug('optimizing user image file optimized');
        }

        $image->resize(240, 240, true);
        $this->logger->debug('optimizing user image save on server');
        $image->save($imageTempName);
        $this->logger->debug('optimizing user image save on server saved');
        $this->logger->debug('optimizing user image upload');
        $this->uploadToS3($imageTempName, 'profiles/' . $newFile);
        $this->logger->debug('optimizing user image uploaded');
        unlink($imageTempName);
        $this->logger->debug('image optimized and uploaded');
        echo "\nImage Cropped and uploaded\n";
    }

    private function prepareProfileImageToUpload($url, $newFile)
    {
        if (!file_exists('/tmp/origins/')) {
            mkdir('/tmp/origins/', 0777, true);
        }
        if (!file_exists('/tmp/origins/profiles/')) {
            mkdir('/tmp/origins/profiles/', 0777, true);
        }
        file_put_contents('/tmp/origins/profiles/' . $newFile, file_get_contents($url));
        return '/tmp/origins/profiles/' . $newFile;
    }

    private function uploadToS3($file = '', $path = '', $bucket = '')
    {
        $client = S3Client::factory(array(
            'key' => Config('waragaIntegration.AWS_ACCESS_ID'),
            'secret' => Config('waragaIntegration.AWS_ACCESS_KEY'),
        ));
        try {
            $client->putObject(array(
                'Bucket' => empty($bucket) ? Config('waragaIntegration.S3_BUCKET') : $bucket,
                'Key' => $path,
                'SourceFile' => $file,
                'ACL' => 'public-read'
            ));
            return true;
        } catch (exception $e) {
            return $e->getMessage();
        }
        return false;
    }

    public function fetchUserData($user_id, $authorization)
    {
        $response = $this->MwCURL(Config::get('waragaIntegration.WARAQA_URL') . "client-api/manpower/$user_id", [], "GET", $authorization);
        $response = json_decode($response);
        return $response;
    }




}


