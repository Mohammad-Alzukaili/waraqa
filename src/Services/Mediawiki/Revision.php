<?php

namespace Mawdoo3\Waraqa\Services\Mediawiki;

use Mawdoo3\Waraqa\Models\Page;
use Mawdoo3\Waraqa\Models\Revision as ModelsRevision;
use Carbon\Carbon;

/**
 *
 */
class Revision
{
    /**
     * @param $text
     * @param $page
     * @return object
     */
    public static function store($text, $page)
    {
        $revision = ModelsRevision::where('rev_page', $page->page_id)->where('rev_text_id', $text->rev_text_id)->first();
        if (empty($revision)) {
            Page::where('page_id', $page->page_id)->update(['page_len' => $text->page_len]);

            $data = [
                'rev_page' => $page->page_id,
                'rev_text_id' => $text->rev_text_id,
                'rev_parent_id' => $page->page_latest,
                'rev_sha1' => $text->rev_sha1,
                'rev_timestamp' => Carbon::now('UTC')->format('YmdHis'),
                'rev_len' => $text->page_len,
            ];

            $revisionId = ModelsRevision::insertGetId($data);

            $revision = array_merge(['revision_id' => $revisionId], $data);
        }

        return (object)$revision;
    }


}


