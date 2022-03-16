<?php

namespace Mawdoo3\Waraqa\Services\Mediawiki;

use Mawdoo3\Waraqa\Models\CategoryLinks;
use Mawdoo3\Waraqa\Models\Category;
use Carbon\Carbon;

/**
 *
 */
class CategoryLinksPrepare
{
    /**
     * @param $page
     * @param $maincategory
     * @param $subcategory
     * @return void
     */
    public static final function store($page, $maincategory, $subcategory)
    {
        $maincategory = $maincategory[0]->name;
        $maincategory = str_replace(' ', '_', $maincategory);
        $pageTitle = str_replace('_', ' ', $page->page_title);
        if (empty(CategoryLinks::where('cl_from', '=', $page->page_id)->where('cl_to', $maincategory)->first())) {
            CategoryLinks::create([
                'cl_from' => $page->page_id,
                'cl_to' => $maincategory,
                'cl_sortkey' => $pageTitle,
                'cl_timestamp' => Carbon::now('UTC'),
                'cl_sortkey_prefix' => '',
                'cl_collation' => 'uppercase',
                'cl_type' => 'page',
            ]);
            Category::where('cat_title', $maincategory)->increment('cat_pages');
        }

        if (is_array($subcategory) && sizeof($subcategory) > 0) {
            foreach ($subcategory as $sub) {
                $subName = str_replace(' ', '_', $sub->name);
                if (empty(CategoryLinks::where('cl_from', '=', $page->page_id)->where('cl_to', $subName)->first())) {
                    CategoryLinks::create([
                        'cl_from' => $page->page_id,
                        'cl_to' => $subName,
                        'cl_sortkey' => $pageTitle,
                        'cl_timestamp' => Carbon::now('UTC'),
                        'cl_sortkey_prefix' => '',
                        'cl_collation' => 'uppercase',
                        'cl_type' => 'page',
                    ]);
                    Category::where('cat_title', $subName)->increment('cat_pages');
                }
            }
        }
    }

}

