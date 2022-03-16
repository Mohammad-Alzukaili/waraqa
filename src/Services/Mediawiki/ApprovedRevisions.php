<?php
namespace Mawdoo3\Waraqa\Services\Mediawiki;

use Mawdoo3\Waraqa\Models\ApprovedRevision;
use Mawdoo3\Waraqa\Models\CacheTable;
use Mawdoo3\Waraqa\Models\Page;
use Mawdoo3\Waraqa\Models\Revision;
use Mawdoo3\Waraqa\Models\SuggestedPic;
use Mawdoo3\Waraqa\Models\WaraqaUser;


class ApprovedRevisions{


    public static function setApprovedRevID(Page $page)
    {
        self::saveApprovedRevIDInDB($page->page_title, $page->page_latest , $page->page_id);

        // $rev_url = $title->getFullURL(array('oldid' => $rev_id));
        //http://esteshary.local/index.php?title=%D8%A3%D9%84%D9%85_%D8%AC%D9%87%D8%A9_%D8%A7%D9%84%D9%82%D9%84%D8%A8_%D9%88%D8%A7%D9%84%D9%8A%D8%AF_%D8%A7%D9%84%D9%8A%D8%B3%D8%B1%D9%891324&oldid=225490

        // Hooks::run('ApprovedRevsRevisionApproved', array($parser, $title, $rev_id)); //run onApproaveRevision
       self::onApprovedRevsRevisionApproved($page);

    }

    public static function saveApprovedRevIDInDB($title, $rev_id , $page_id)
    {
        $old_rev_id = ApprovedRevision::where('page_id' , $page_id)->first();

        $created_at = date("Y-m-d H:i:s");
        $updated_at = date("Y-m-d H:i:s");

        $url_cashe = $title; //slug
        if ($old_rev_id) {
            ApprovedRevision::where('page_id' , $page_id)->update(
                array('rev_id' => $rev_id)
            );

            $check_exist_url = CacheTable::where('url' , $url_cashe)->first();

            if (!$check_exist_url) {
                CacheTable::insert(array('url' => $url_cashe));
            }
        } else {
            ApprovedRevision::insert(array('page_id' => $page_id, 'rev_id' => $rev_id, 'created_at'=> $created_at, 'updated_at'=>$updated_at));
        }

        $page_id_rev =  Revision::where('rev_id' , $rev_id)->first();
        $page = Page::where('page_id'  , $page_id_rev->rev_page)->first();

        if(!is_null($page->waraqa_article_id)){

            if(!is_null($page->waraqa_users_id)){
                self::cacheWaraqaUserProfile($page->waraqa_users_id);
            }

            if(!is_null($page->waraqa_proofreader_id)){
                self::cacheWaraqaUserProfile($page->waraqa_proofreader_id);
            }

            $check_exist_url = CacheTable::where('url' , $url_cashe)->first();

            if(!$check_exist_url) {
                $check_exist_url = CacheTable::insert(array('url'=>$url_cashe));
            }
        }
        //core_modification
        // Update "cache" in memory
        // self::$mApprovedRevIDForPage[$page_id] = $rev_id;

    }



   static function onApprovedRevsRevisionApproved( $page) {
        $cats = WikiPage::getParentCategoryTree($page->page_id);
        $category = null;

        foreach($cats as $cat){
            $category = $cat->cl_to;
            break;
        }

        SuggestedPic::insert(array('suggested_pics.article_title' => $page->page_title,
            'suggested_pics.cat_title' => $category)
        );

        return true;
    }

    public static function cacheWaraqaUserProfile($user_id){
        $writer = WaraqaUser::where('id' , $user_id)->first();
        $profileSlug = "الخبير:" . str_replace(' ', '_', $writer->user_name) . "_" . $writer->user_id;
        $isProfileSlugCached = CacheTable::where('url' , $profileSlug)->get();

        if ($isProfileSlugCached->isEmpty()){
            CacheTable::insert(array('url' => $profileSlug));

        }
    }

}

