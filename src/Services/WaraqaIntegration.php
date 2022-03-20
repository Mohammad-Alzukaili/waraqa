<?php

namespace Mawdoo3\Waraqa\Services;

use Mawdoo3\Waraqa\Models\MediaWikiUser;
use Mawdoo3\Waraqa\Models\Page;
use Mawdoo3\Waraqa\Services\Mediawiki\ApprovedRevisions;
use Mawdoo3\Waraqa\Services\Mediawiki\CategoryLinksPrepare;
use Mawdoo3\Waraqa\Services\Mediawiki\Logging;
use Mawdoo3\Waraqa\Services\Mediawiki\ExternalLinkPrepare;
use Mawdoo3\Waraqa\Services\Mediawiki\Searchindex;
use Mawdoo3\Waraqa\Services\Mediawiki\UploadImages;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Waraqa\Connection\WAMQPConnect;
use PhpAmqpLib\Message\AMQPMessage;
use Waraqa\Consumer;
use Mawdoo3\Waraqa\Services\Mediawiki\User;
use Mawdoo3\Waraqa\Services\Mediawiki\WikiPage;
use Exception;
use Waraqa\Articles\Article;

class WaraqaIntegration extends Consumer
{
    private $articleRequest;
    private $waraqaUrl;
    private $clientAccessId;
    private $clientPassword;
    private $mediaWikiParserApi;

    public function __construct()
    {
        $this->waraqaUrl = Config('waragaIntegration.WARAQA_URL');
        $this->clientAccessId = Config('waragaIntegration.CLIENT_ACCESS_ID');
        $this->clientPassword = Config('waragaIntegration.CLIENT_PASSWORD');
        $this->mediaWikiParserApi = Config('waragaIntegration.MEDIAWIKI_PARSER_API');
    }


     /**
     * consume
     *
     * @param  mixed $WConnection
     * @param  mixed $callback
     * @param  bool $check
     * @return void
     */
    public function consume(WAMQPConnect $WConnection, $callback, $check=true)
    {
        $connection = $WConnection->connection;
        $channel = $connection->channel();
        $channel->queue_declare($WConnection->queue, false, true, false, false);
        $channel->basic_qos(null, 1, null);
        $channel->exchange_declare($WConnection->exchange, 'direct', false, true, false);
        $channel->queue_bind($WConnection->queue, $WConnection->exchange);
        $channel->basic_consume($WConnection->queue, 'consumer_tag', false, false, false, false, $callback);

        if($check) {
            register_shutdown_function(array($this, 'onShutdown'), $WConnection);
            while (count($channel->callbacks) > 0) {
                $channel->wait();
            }
        }
    }

    public function execute()
    {   try{

            $client_id = 'waraqa-client-' . Config('waragaIntegration.CLIENT_ID');
            echo $client_id . PHP_EOL;
            $connection_obj = new WAMQPConnect(Config('waragaIntegration.AQMP_CONNECTION'), $client_id, '/etc/ssl/certs', $client_id);
            $connection = $connection_obj->connect();
            $this->consume($connection, [$this, 'process']);
        }catch(Exception $ex){
            Log::error(__CLASS__.":".__FUNCTION__.":".$ex->getMessage());
            echo (__CLASS__.":".__FUNCTION__.":".$ex->getMessage() );
        }
    }

    /**
     * @param $article
     * @param $articleHtml
     * @return void
     */
    public function processArticleTest($article, $articleHtml)
    {

        $this->articleRequest = new Article($this->waraqaUrl, $this->clientAccessId, $this->clientPassword);
        $this->waraqaUrl = Config('waragaIntegration.WARAQA_URL');
        $this->clientAccessId = Config('waragaIntegration.CLIENT_ACCESS_ID');
        $this->clientPassword = Config('waragaIntegration.CLIENT_PASSWORD');
        $this->mediaWikiParserApi = Config('waragaIntegration.MEDIAWIKI_PARSER_API');
        try {

            $url = $this->mediaWikiParserApi;

            $html = $this->strip_tags_content($articleHtml, "<colgroup>", true);
            $html = str_replace(array("\n", "\r", '</em>', '<em>', '<nowiki>', '</nowiki>', '</colgroup>', '<col width="100">', '<colgroup>'), '', $html);
            $html = str_replace('style="width', 'style="1width', $html);
            $params = [
                "html" => $html
            ];

            $htmlBody = $this->MwCURL($url, $params);
            $htmlBody = str_replace(array('<pre>', '</pre>', '</em>', '<em>', '<nowiki>', '</nowiki>'), '', $htmlBody);

            if ($article->type == 'إثراء') {
                $this->checkUpdateEnrich($article, $htmlBody);
            } else {
                $this->createPage($article, $htmlBody);
            }
        } catch (\Exception $ex) {
            echo $ex->getMessage();
            Log::error($ex->getMessage());
        }

    }

    public function processArticle($articleId)
    {
        try {
            $this->articleRequest = new Article($this->waraqaUrl, $this->clientAccessId, $this->clientPassword);
            $article = $this->articleRequest->fetchSingle($articleId);
            $url = $this->mediaWikiParserApi;
            $html = $article->body->html;
            $html = $this->strip_tags_content($html, "<colgroup>", true);
            $html = str_replace(array("\n", "\r", '</em>', '<em>', '<nowiki>', '</nowiki>', '</colgroup>', '<col width="100">', '<colgroup>'), '', $html);
            $html = str_replace('style="width', 'style="1width', $html);
            $params = [
                "html" => $html
            ];
            $htmlBody = $this->MwCURL($url, $params);
            $htmlBody = str_replace(array('<pre>', '</pre>', '</em>', '<em>', '<nowiki>', '</nowiki>'), '', $htmlBody);
            if ($article->type == 'إثراء') {
                $this->checkUpdateEnrich($article, $htmlBody);
            } else {
                $this->createPage($article, $htmlBody);
            }
        } catch (\Exception $ex) {
            // \Log::error($ex->getMessage() . ", Line: " . __LINE__ . "\n", __DIR__ . "/waraqaErrors.log");
        }

    }

    public function process(AMQPMessage $message)
    {


        $messageBody = json_decode($message->body, true);
        // $this->logger->info('== AMQPMessage ==' , $messageBody);
        if (!is_null($messageBody) && $messageBody['job'] == 'newarticle') {
            try {
                $this->articleRequest = new Article($this->waraqaUrl, $this->clientAccessId, $this->clientPassword);
                $articleId = $messageBody['args'][0];
                $article = $this->articleRequest->fetchSingle($articleId);
                $url = $this->mediaWikiParserApi;
                $html = $article->body->html;
                $html = $this->strip_tags_content($html, "<colgroup>", true);
                $html = str_replace(array("\n", "\r", '</em>', '<em>', '<nowiki>', '</nowiki>', '</colgroup>', '<col width="100">', '<colgroup>'), '', $html);
                $html = str_replace('style="width', 'style="1width', $html);
                $params = [
                    "html" => $html
                ];
                $htmlBody = $this->MwCURL($url, $params);

                $htmlBody = str_replace(array('<pre>', '</pre>', '</em>', '<em>', '<nowiki>', '</nowiki>'), '', $htmlBody);

                if ($article->type == 'إثراء') {
                    $this->checkUpdateEnrich($article, $htmlBody);
                } else {
                    $this->createPage($article, $htmlBody);
                }
                $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
            } catch (\Exception $ex) {
                // $this->logger->error('== Exception basic_reject ==' , [$messageBody , $ex->getMessage()] );
                // \Log::error($ex->getMessage() . ", Line: " . __LINE__ . "\n", __DIR__ . "/waraqaErrors.log");
                $message->delivery_info['channel']->basic_reject($message->delivery_info['delivery_tag'], false);
            }
        } else {
            // $this->logger->error('== wrong job basic_reject ==' , [$messageBody] );
            // \Log::error("wrong job basic_reject $message->body , Line: " . __LINE__ . "\n", __DIR__ . "/waraqaErrors.log");
            $message->delivery_info['channel']->basic_reject($message->delivery_info['delivery_tag'], false);
        }
    }

    public function checkUpdateEnrich($article, $htmlBody)
    {
        echo "Start process ....\n";
        //handle article slug from title if not exist
        $articleSlug = !empty($article->title) ? str_replace(' ', '_', ucfirst(trim($article->title))) : $article->slug;

        //categories
        $categories = '';
        if ($article->type == 'جديد') {
            if (is_array($article->main_category) && sizeof($article->main_category) > 0 && isset($article->main_category[0]->name) && !empty($article->main_category[0]->name)) {
                $categories .= '[[تصنيف:' . str_replace(' ', '_', $article->main_category[0]->name) . ']]';
            }

            if (is_array($article->sub_category) && sizeof($article->sub_category) > 0) {
                foreach ($article->sub_category as $sub) {
                    $categories .= ' [[تصنيف:' . str_replace(' ', '_', $sub->name) . ']]';
                }
            }
        }

        DB::beginTransaction();

        try {
            //check if page is exist or not
            echo "Start checking or create page .... \n";
            $page = WikiPage::getOrCreate($articleSlug, $article->id);
            echo "Get or create user \n";
            $user = MediaWikiUser::where('user_id', Config('waragaIntegration.WARAQA_USER_ID'))->first();

            //check if this user is exist or not
            // @TODO: if Waraqa-User Not exist make the correct user data for all table?!
            if ($user === null) {
                $userData = [
                    'user_name' => 'Waraqa User',
                    'user_real_name' => 'Waraqa User',
                ];
                echo "create user .... \n";
                $user = User::store($userData);
            }

            if (is_array($article->main_category) && sizeof($article->main_category) > 0 && isset($article->main_category[0]->name) && !empty($article->main_category[0]->name)) {
                echo "check categories .... \n";
                CategoryLinksPrepare::store($page, $article->main_category, $article->sub_category);
            }

            if (strpos($htmlBody, "</ref>") !== FALSE) {
                $htmlBody .= "\n== المراجع ==\n";
            }

            $text = $htmlBody . $categories;

            ExternalLinkPrepare::store($text, $page);

            /**
             * Change an existing article or create a new article. Updates RC and all necessary caches,
             * optionally via the deferred update array.
             */
            echo "Start edit or create page relations (revisions , text , links) ...... \n";
            $content = WikiPage::doEditContent(
                $page->page_id, //page_id
                $user->user_id,
                $text
            );

            echo "Upload image ...... \n";
            UploadImages::getImage($article->feature_image->content_base64, $page);

            $page = Page::where("page_id", $page->page_id)->first();

            echo "Insert Waarqa user ...... \n";
            User::insertWaraqaUser($article, $page);
            Searchindex::update($page->page_id, $page->page_title, $content->old_text); //insert Searchindex when schedule_publish_date

            if (!empty($article->schedule_publish_date)) {
                echo "Publishing article  ...... \n";
                $this->articleRequest->publishArticle($article->id);
                $page = Page::where('page_id', $page->page_id)->first();
                ApprovedRevisions::setApprovedRevID($page);
                echo "Logging  ...... \n";
                Logging::logStore($page, $user);
            }

            echo "at end";
            DB::commit();
        } catch (\Exception $ex) {
            Log::error(json_encode($ex));
            DB::rollBack();
        }

    }

    public function createPage($article, $htmlBody)
    {
        $this->checkUpdateEnrich($article, $htmlBody);
    }

    public function MwCURL(string $url, array $params = [])
    {
        try {
            $curl = curl_init();
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
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => $params
                )
            );
            $response = curl_exec($curl);
            $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($httpcode != 200) {
                Log::warning('method MwCURL()', [
                    'url' => $url,
                    'params' => $params,
                    'response' => $response
                ]);
            }
            return $response;
        } catch (\Exception $ex) {
            Log::error('method MwCURL() exeption', [$ex]);
            Log::error('MwCURL: ' . $ex->getMessage() . ", Line: " . __LINE__ . "\n");
        }
    }

    private function strip_tags_content($text, $tags = '', $invert = FALSE)
    {

        preg_match_all('/<(.+?)[\s]*\/?[\s]*>/si', trim($tags), $tags);
        $tags = array_unique($tags[1]);

        if (is_array($tags) and count($tags) > 0) {
            if ($invert == FALSE) {
                return preg_replace('@<(?!(?:' . implode('|', $tags) . ')\b)(\w+)\b.*?>.*?</\1>@si', '', $text);
            } else {
                return preg_replace('@<(' . implode('|', $tags) . ')\b.*?>.*?</\1>@si', '', $text);
            }
        } elseif ($invert == FALSE) {
            return preg_replace('@<(\w+)\b.*?>.*?</\1>@si', '', $text);
        }
        return $text;
    }

}
