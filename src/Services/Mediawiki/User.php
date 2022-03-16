<?php
namespace Mawdoo3\Waraqa\Services\Mediawiki;

use Mawdoo3\Waraqa\Models\MediaWikiUser;
use Mawdoo3\Waraqa\Models\Page;
use Mawdoo3\Waraqa\Models\Revision;
use Mawdoo3\Waraqa\Models\UserProperty;
use DateTime;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 *
 */
class User {


 public function __construct(){

 }

public static function newFromRow($row){
    $user = new User;
	return $user;
}




/**
 * check if user exist or not
 */


static function  store($data){
    //insert user to db
    $user = MediaWikiUser::create(
        [           'user_id' => config('waragaIntegration.WARAQA_USER_ID'), //next id
                    'user_name' => $data['user_name'],
                    'user_password' => ':A:7ba14fad98ebd764a5cd9d6b4df579fd',
                    'user_newpassword' => '',
                    'user_email' => 'admin@waraqa.com',
                    'user_email_authenticated' => Carbon::now('UTC')->format('YmdHis'),
                    'user_real_name' => $data['user_real_name'],/// null
                    'user_token' => '',
                    'user_registration' =>  Carbon::now('UTC')->format('YmdHis'),
                    'user_editcount' => null,
                    'user_touched' => Carbon::now('UTC')->format('YmdHis'),
                ]
    );

    return $user;
}

    public static function insertWaraqaUser($article, $page)
    {

        Page::where("page_id" , $page->page_id)->update(array('waraqa_article_id' => $article->id));

        $updateWaraqaUsersData = new UpdateWaraqaUsersData();
        $loginToken = $updateWaraqaUsersData->login();
        $authorization = "Authorization: Bearer $loginToken";
        $articleUsers = $updateWaraqaUsersData->getArticlesManpower(['articles' => [$article->id]], $authorization);

        foreach ($articleUsers as $row) {
            $article_id = $row->id;
            // Update or create writer
            if (!empty($row->writer->id)) {
                $id = $updateWaraqaUsersData->updateOrCreateUser($row->writer);
                Page::where("waraqa_article_id" , $article_id)->update(array('waraqa_users_id' => $id));
                Revision::where("rev_id" , $page->page_latest)->update(array('waraqa_writer_id' => $id));

            }
            if (!empty($row->general_proofreader->id)) {
                //todo
                $id = $updateWaraqaUsersData->updateOrCreateUser($row->general_proofreader);
                Page::where("waraqa_article_id" ,$article_id)->update(array('waraqa_proofreader_id' => $id));
                Revision::where("rev_id" , $page->page_latest)->update(array('waraqa_proofreader_id' => $id));

            }
            if (!empty($row->content_proofreader->id)) {
                $id = $updateWaraqaUsersData->updateOrCreateUser($row->content_proofreader);
                Page::where("waraqa_article_id" , $article_id)->update(array('waraqa_proofreader_id' => $id));
                Revision::where("rev_id" , $page->page_latest)->update(array('waraqa_proofreader_id' => $id));

            }
        }
    }

}

?>
