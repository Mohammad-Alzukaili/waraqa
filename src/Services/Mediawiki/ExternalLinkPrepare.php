<?php

namespace Mawdoo3\Waraqa\Services\Mediawiki;

use Mawdoo3\Waraqa\Models\ExternalLinks;
use Mawdoo3\Waraqa\Models\Page;

class ExternalLinkPrepare
{


    /**
     * @param $html
     * @return array
     */
    public static final function getAllHref($html)
    {
        $regex = '/https?\:\/\/[^\" ]+/i';
        preg_match_all($regex, $html, $matches);

        $urls = [];

        if (!empty($matches)) {
            $urls = array_unique($matches[0]);
        }

        return $urls;
    }

    /**
     * Make URL indexes, appropriate for the el_index field of externallinks.
     *
     * @param string $url
     * @return array
     */
    static function wfMakeUrlIndexes($url)
    {
        $bits = self::convertPit($url);

        // Reverse the labels in the hostname, convert to lower case
        // For emails reverse domainpart only
        if ($bits['scheme'] == 'mailto') {
            $mailparts = explode('@', $bits['host'], 2);
            if (count($mailparts) === 2) {
                $domainpart = strtolower(implode('.', array_reverse(explode('.', $mailparts[1]))));
            } else {
                // No domain specified, don't mangle it
                $domainpart = '';
            }
            $reversedHost = $domainpart . '@' . $mailparts[0];
        } else {
            $reversedHost = strtolower(implode('.', array_reverse(explode('.', $bits['host']))));
        }
        // Add an extra dot to the end
        // Why? Is it in wrong place in mailto links?
        if (substr($reversedHost, -1, 1) !== '.') {
            $reversedHost .= '.';
        }
        // Reconstruct the pseudo-URL
        $prot = $bits['scheme'];
        $index = $prot . $bits['delimiter'] . $reversedHost;
        // Leave out user and password. Add the port, path, query and fragment
        if (isset($bits['port'])) {
            $index .= ':' . $bits['port'];
        }
        if (isset($bits['path'])) {
            $index .= $bits['path'];
        } else {
            $index .= '/';
        }
        if (isset($bits['query'])) {
            $index .= '?' . $bits['query'];
        }
        if (isset($bits['fragment'])) {
            $index .= '#' . $bits['fragment'];
        }

        if ($prot == '') {
            return ["http:$index", "https:$index"];
        } else {
            return [$index];
        }
    }

    private static function convertPit($url)
    {
        $wgUrlProtocols = [
            'bitcoin:', 'ftp://', 'ftps://', 'geo:', 'git://', 'gopher://', 'http://',
            'https://', 'irc://', 'ircs://', 'magnet:', 'mailto:', 'mms://', 'news:',
            'nntp://', 'redis://', 'sftp://', 'sip:', 'sips:', 'sms:', 'ssh://',
            'svn://', 'tel:', 'telnet://', 'urn:', 'worldwind://', 'xmpp:', '//'
        ];
        // Protocol-relative URLs are handled really badly by parse_url(). It's so
        // bad that the easiest way to handle them is to just prepend 'http:' and
        // strip the protocol out later.
        $wasRelative = substr($url, 0, 2) == '//';
        if ($wasRelative) {
            $url = "http:$url";
        }
        $bits = parse_url($url);

        // parse_url() returns an array without scheme for some invalid URLs, e.g.
        // parse_url("%0Ahttp://example.com") == array( 'host' => '%0Ahttp', 'path' => 'example.com' )
        if (!$bits || !isset($bits['scheme'])) {
            return false;
        }

        // parse_url() incorrectly handles schemes case-sensitively. Convert it to lowercase.
        $bits['scheme'] = strtolower($bits['scheme']);

        // most of the protocols are followed by ://, but mailto: and sometimes news: not, check for it
        if (in_array($bits['scheme'] . '://', $wgUrlProtocols)) {
            $bits['delimiter'] = '://';
        } elseif (in_array($bits['scheme'] . ':', $wgUrlProtocols)) {
            $bits['delimiter'] = ':';
            // parse_url detects for news: and mailto: the host part of an url as path
            // We have to correct this wrong detection
            if (isset($bits['path'])) {
                $bits['host'] = $bits['path'];
                $bits['path'] = '';
            }
        } else {
            return false;
        }

        /* Provide an empty host for eg. file:/// urls (see bug 28627) */
        if (!isset($bits['host'])) {
            $bits['host'] = '';

            if (isset($bits['path'])) {
                /* parse_url loses the third / for file:///c:/ urls (but not on variants) */
                if (substr($bits['path'], 0, 1) !== '/') {
                    $bits['path'] = '/' . $bits['path'];
                }
            } else {
                $bits['path'] = '';
            }
        }

        // If the URL was protocol-relative, fix scheme and delimiter
        if ($wasRelative) {
            $bits['scheme'] = '';
            $bits['delimiter'] = '//';
        }

        return $bits;
    }

    /**
     * @param $text
     * @param Page $page
     * @return void
     */
    public static final function store($text, Page $page)
    {
        $urls = ExternalLinkPrepare::getAllHref($text);
        ExternalLinks::where('el_from', $page->page_id)->delete();

        foreach ($urls as $url) {

            if (empty($page->externalLinks->where('el_to', $url)->first())) {
                $elIndex = ExternalLinkPrepare::wfMakeUrlIndexes($url);

                if (!empty($elIndex[0])) {
                    $page->externalLinks()->create([
                        'el_id' => (optional(ExternalLinks::select('el_id')->latest('el_id')->first())->el_id + 1),
                        'el_to' => strip_tags($url),
                        'el_index' => strip_tags($elIndex[0]),
                    ]);
                }

            }

        }

    }
}
