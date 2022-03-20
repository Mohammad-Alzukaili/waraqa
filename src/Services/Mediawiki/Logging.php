<?php

namespace Mawdoo3\Waraqa\Services\Mediawiki;

use Mawdoo3\Waraqa\Models\Logging as ModelsLogging;

/**
 *
 */
class Logging
{
    /**
     * @param $page
     * @param $user
     * @return mixed
     */
    public static function logStore($page,$user)
    {
        $logparams = '<a href="'. Config('FULL_SERVER_URL') . 'index.php?title=' . $page->title . '&amp;oldid=' . $page->page_latest .'">'.$page->page_latest.'</a>';
        if (!empty($page)){
            $data = [
                'log_type' => 'approval',
                'log_action' => 'approve',
                'log_timestamp' => $page->page_touched,
                'log_user' => $user->user_id,
                'log_user_text' => $user->user_name,
                'log_namespace' => 0,
                'log_title' => $page->page_title,
                'log_comment' => '',
                'log_params' => $logparams,
                'log_deleted' => 0,
                'log_page'=>$page->page_id
            ];
            $log = ModelsLogging::create($data);
        }
        return $log->id;
    }

}

