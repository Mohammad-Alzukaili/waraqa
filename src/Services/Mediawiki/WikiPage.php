<?php

namespace Mawdoo3\Waraqa\Services\Mediawiki;

use Mawdoo3\Waraqa\Models\CategoryLink;
use Mawdoo3\Waraqa\Models\Mawdoo3RcCron;
use Mawdoo3\Waraqa\Models\MediaWikiUser;
use Mawdoo3\Waraqa\Models\Page;
use Mawdoo3\Waraqa\Models\RecentChange;
use Mawdoo3\Waraqa\Models\Redirect;
use Mawdoo3\Waraqa\Models\Revision;
use Mawdoo3\Waraqa\Models\SiteState;
use Mawdoo3\Waraqa\Models\Text;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 *
 */
class WikiPage
{


    /**
     * Get a random decimal value between 0 and 1, in a way
     * not likely to give duplicate values for any realistic
     * number of articles.
     *
     * @note This is designed for use in relation to Special:RandomPage
     *       and the page_random database field.
     *
     * @return string
     */
    private static function wfRandom()
    {
        // The maximum random value is "only" 2^31-1, so get two random
        // values to reduce the chance of dupes
        $max = mt_getrandmax() + 1;
        $rand = number_format((mt_rand() * $max + mt_rand()) / $max / $max, 12, '.', '');

        return $rand;
    }

    public static final function getOrCreate($slug, $waraqaArticleId)
    {
        $page = Page::where('page_title', $slug)->first();

        if (empty($page)) {
            $nextPageId = (optional(Page::select('page_id')->latest('page_id')->first())->page_id + 1);
            $data = [
                'page_id' => $nextPageId,
                'page_title' => $slug,
                'page_namespace' => 0,
                'page_restrictions' => '',
                'page_is_redirect' => 0, // Will set this shortly...
                'page_is_new' => 0,
                'page_random' => self::wfRandom(),
                'page_touched' => Carbon::now('UTC')->format('YmdHis'),
                'page_latest' => 0, // Fill this in shortly...
                'page_len' => 0, // Fill this in shortly...
                'page_content_model' => 'wikitext',
                'page_links_updated' => Carbon::now('UTC')->format('YmdHis'),
                'waraqa_article_id' => $waraqaArticleId,

            ];

             $page = Page::create($data);

            //increment site_stats
            $siteState = SiteState::first();
            SiteState::updateOrCreate([
                'ss_row_id'=>1
            ],[
                'ss_total_pages' => empty($siteState)==true ? 1 :DB::raw('ss_total_pages + 1'),
                'ss_good_articles' => empty($siteState)==true ? 1 :DB::raw('ss_good_articles + 1')
            ]);
        }

        if (!empty($page)) {
            $page->update(['page_touched' => Carbon::now('UTC')->format('YmdHis'), 'page_links_updated' => Carbon::now('UTC')->format('YmdHis'), 'page_is_redirect' => 0]);
            Redirect::where('rd_from', $page->page_id)->delete();
        }

        return $page;
    }


    public static function getParentCategoryTree($page_id)
    {
        return CategoryLink::where('cl_from', $page_id)->get();
    }

    public static function doEditContent($page_id, $user_id, $text)
    {

        $page = Page::where('page_id', $page_id)->first();
        $user = MediaWikiUser::where('user_id', $user_id)->first();

        $latestRevision = Revision::where('rev_id', $page->page_latest)->first();
        $oldContent = $page->page_latest > 0  ? Text::where('old_id', $latestRevision->rev_text_id)->first() : null;

        $changed = mb_strlen($oldContent->old_text ?? null , '8bit') !== mb_strlen($text , '8bit');

        if ($changed) {
            $isCreateRevision = true;
            $content = ContentHandler::store($text);
        } else {
            $isCreateRevision = false;
            $content = $oldContent;
        }

        // Actually create the revision and create/update the page
        if ($page->page_latest > 0) {
            self::doModify($page, $content, $user, $isCreateRevision);
        } else {
            self::doCreate($page, $content, $user);
        }

        return $content;

    }


    private static function doModify(
        $page, $content, $user, $isCreateRevision
    )
    {
        if ($isCreateRevision){
            // Update article, but only if changed.
            $newsize = mb_strlen($content->old_text,'8bit');
            $latestRevision = Revision::where('rev_id', $page->page_latest)->first();

            // create revision
            $next_rev_id = optional(Revision::orderBy('rev_id', 'desc')->first())->rev_id + 1;

            $row = [
                'rev_id' => $next_rev_id,
                'rev_page' => $page->page_id,
                'rev_text_id' => $content->old_id,
                'rev_comment' => "",
                'rev_minor_edit' => false,
                'rev_user' => $user->user_id,
                'rev_user_text' => $user->user_name,
                'rev_timestamp' => Carbon::now('UTC')->format('YmdHis'),
                'rev_deleted' => 0,
                'rev_len' => $newsize,
                'rev_parent_id' => $latestRevision->rev_id,
                'rev_sha1' => ContentHandler::baseConvert(sha1($content->old_text), 16, 36, 31),
                'rev_content_model' => null,
                'rev_content_format' => null,
                'waraqa_writer_id' => $page->waraqa_users_id, //todo : check
                'waraqa_proofreader_id' => $page->waraqa_proofreader_id//todo : check
            ];

            $revision = Revision::create($row);

            //increment site stats edit
            $siteState = SiteState::first();
            SiteState::updateOrCreate([
                'ss_row_id'=>1
            ],[
                'ss_total_edits' => empty($siteState)==true ? 1 : DB::raw('ss_total_edits + 1'),
            ]);

            // Update page_latest and friends to reflect the new revision

            $page_data = [
                'page_latest' => $revision->rev_id,
                'page_touched' => Carbon::now('UTC')->format('YmdHis'),
                'page_is_new' => 0,
                'page_is_redirect' => 0, //todo
                'page_len' => $revision->rev_len,
                "page_content_model" => "wikitext"
            ];

            $page->update($page_data);

            $recentchangesData = [
                'rc_id' => optional(RecentChange::orderBy('rc_id', 'desc')->first())->rc_id + 1,
                'rc_timestamp' => Carbon::now('UTC')->format('YmdHis'),
                'rc_namespace' => 0, //always 0 for articles
                'rc_title' => $page->page_title,
                'rc_type' => 0, //rc_type : RC_EDIT =0 , RC_NEW = 1 , RC_LOG=3
                'rc_source' => "mw.edit", // "mw.edit" always (mw.edit or mw.new)
                'rc_minor' => 0, //always 0
                'rc_cur_id' => $page->page_id,
                'rc_user' => $user->user_id,
                'rc_user_text' => $user->user_name,
                'rc_comment' => "", //empty always
                'rc_this_oldid' => $revision->rev_id, //revision id
                'rc_last_oldid' => $latestRevision->rev_id, // previous revision id
                'rc_bot' => 1, //1 for mw.edit or mw.new , 0 for logging recent changes
                'rc_ip' => '', //ip address
                'rc_patrolled' => 1,// make it 1 always
                'rc_new' => 0, # obsolete 0 : edit or 1 : for new
                'rc_old_len' => $latestRevision->rev_len, /// 0 for new articles , size for updating
                'rc_new_len' => $revision->rev_len, /// revision size
                'rc_deleted' => 0,
                'rc_logid' => 0,
                'rc_log_type' => null,
                'rc_log_action' => '',
                'rc_params' => ''

            ];

            RecentChange::create($recentchangesData);

            self::incEditCount($user);
        }


    }


    private static function doCreate($page, $content, $user, $summary = "")
    {
        $now = Carbon::now('UTC')->format('YmdHis'); //done
        $newsize = mb_strlen($content->old_text , '8bit'); //done

        // create revision
        $next_rev_id = optional(Revision::orderBy('rev_id', 'desc')->first())->rev_id + 1;

        // @TODO: pass content object?!
        $row = [
            'rev_id' => $next_rev_id,
            'rev_page' => $page->page_id,
            'rev_text_id' => $content->old_id,
            'rev_comment' => "",
            'rev_minor_edit' => false,
            'rev_user' => $user->user_id,
            'rev_user_text' => $user->user_name,
            'rev_timestamp' => Carbon::now('UTC')->format('YmdHis'),
            'rev_deleted' => 0,
            'rev_len' => $newsize,
            'rev_parent_id' => 0,
            'rev_sha1' => ContentHandler::baseConvert(sha1($content->old_text), 16, 36, 31),
            'rev_content_model' => null,
            'rev_content_format' => null,
            'waraqa_writer_id' => $page->waraqa_users_id, //todo : check
            'waraqa_proofreader_id' => $page->waraqa_proofreader_id//todo : check
        ];
        $revision = Revision::create($row);


        // Save the revision text...
        $page_data = [
            'page_latest' => $revision->rev_id,
            'page_touched' => Carbon::now('UTC')->format('YmdHis'),
            'page_is_new' => 1,
            'page_is_redirect' => 0,
            'page_len' => $revision->rev_len,
            "page_content_model" => "wikitext"
        ];

        $affectedRows = Page::where('page_id', $page->page_id)->update($page_data);
        if ($affectedRows > 0) {
            // $this->updateRedirectOn( $dbw, $rt, $lastRevIsRedirect );
        }
        // Hooks::run( 'NewRevisionFromEditComplete', [ $this, $revision, false, $user ] ); //todo

        // Add RC row to the DB
        //  #1 look bottom of file
        $recentchangesData = [
            'rc_id' => optional(RecentChange::orderBy('rc_id', 'desc')->first())->rc_id + 1,
            'rc_timestamp' => Carbon::now('UTC')->format('YmdHis'),
            'rc_namespace' => 0, //always 0 for articles
            'rc_title' => $page->page_title,
            'rc_type' => 1, //rc_type : RC_EDIT =0 , RC_NEW = 1 , RC_LOG=3
            'rc_source' => "mw.new", // "mw.edit" always (mw.edit or mw.new)
            'rc_minor' => 0, //always 0
            'rc_cur_id' => $page->page_id,
            'rc_user' => $user->user_id,
            'rc_user_text' => $user->user_name,
            'rc_comment' => "", //empty always
            'rc_this_oldid' => $revision->rev_id, //revision id
            'rc_last_oldid' => 0, // previous revision id
            'rc_bot' => 1, //1: for recent changes have mw.edit or mw.new
            'rc_ip' => '', //ip address
            'rc_patrolled' => 1,// make it 1 always
            'rc_new' => 1, # obsolete 0 : edit or 1 : for new
            'rc_old_len' => 0, /// 0 for new articles , size for updating
            'rc_new_len' => $revision->rev_len, /// revision size
            'rc_deleted' => 0,
            'rc_logid' => 0,
            'rc_log_type' => null,
            'rc_log_action' => '',
            'rc_params' => ''
        ];

        $recentChange = RecentChange::create($recentchangesData);

        self::incEditCount($user); //done

        // Return the new revision to the caller

        // Update links, etc.
        // $that->doEditUpdates( $revision, $user, [ 'created' => true ] );
        // // Trigger post-create hook
        // $params = [ &$that, &$user, $content, $summary,
        //     $flags & EDIT_MINOR, null, null, &$flags, $revision ];
        // ContentHandler::runLegacyHooks( 'ArticleInsertComplete', $params );
        // Hooks::run( 'PageContentInsertComplete', $params ); //todo
        // // Trigger post-save hook
        // $params = array_merge( $params, [ &$status, $meta['baseRevId'] ] );
        // ContentHandler::runLegacyHooks( 'ArticleSaveComplete', $params );
        // Hooks::run( 'PageContentSaveComplete', $params ); //todo

    }

    public static function incEditCount($user)
    {

        if (is_null($user->user_editcount) == false) {
            $user->update([
                'user_editcount' => DB::raw('user_editcount + 1'),
            ]);
        } else {
            //get revisions number that user edit before
            $rowsNum = Revision::where('rev_user', $user->id)->count();
            $user->update([
                'user_editcount' => $rowsNum ?? 1,
            ]);
        }
    }

}
