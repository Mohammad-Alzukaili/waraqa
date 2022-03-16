<?php

namespace Mawdoo3\Waraqa\Services\Mediawiki;


use Illuminate\Support\Facades\Log;

/**
 *
 */
class Title {

    /**
     * Create a new Title from text, such as what one would find in a link. De-
     * codes any HTML entities in the text.
     *
     * @param string|int|null $title The link text; spaces, prefixes, and an
     *   initial ':' indicating the main namespace are accepted.
     *   by a prefix.  If you want to force a specific namespace even if
     *   $text might begin with a namespace prefix, use makeTitle() or
     *   makeTitleSafe().
     * @return string|null
     */
    public static function checkTitle($title)
    {
        // DWIM: Integers can be passed in here when page titles are used as array keys.
        if ($title !== null && !is_string($title) && !is_int($title)) {
            Log::error("$title must be a string");
        }

        if ($title === null) {
            return null;
        }

        return $title;
    }



}


