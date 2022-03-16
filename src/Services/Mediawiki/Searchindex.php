<?php

namespace Mawdoo3\Waraqa\Services\Mediawiki;

use Mawdoo3\Waraqa\Models\SearchIndex as ModelsSearchIndex;
use Exception;

class Searchindex
{

    /**
     * @param $id
     * @param $title
     * @param $text
     * @return bool
     * @throws Exception
     */
    public static final function update($id, $title, $text)
    {
        ModelsSearchIndex::where('si_page', $id)->delete();
        // $si_title = self::getNormalizedTitle(str_replace('_',' ', $title));

        $data = [
            'si_page' => $id,
            'si_title' => self::getNormalizedTitle(str_replace('_',' ', $title)),
            'si_text' => self::getNormalizedText(self::getNormalizedTitle($text)),
        ];

        return ModelsSearchIndex::insert($data);
    }

	private static function getNormalizedTitle( $title ) {

		$title =  trim($title);
        $out = preg_replace_callback(
			"/([\\xc0-\\xff][\\x80-\\xbf]*)/",
			[ Searchindex::class, 'stripForSearchCallback' ],
			self::lc( $title ) );

        $minLength = 4;
		if ( $minLength > 1 ) {
			$n = $minLength - 1;
			$out = preg_replace(
				"/\b(\w{1,$n})\b/",
				"$1u800",
				$out );
		}

		$out = preg_replace(
			"/(\w)\.(\w|\*)/u",
			"$1u82e$2",
			$out );

        return $out;
	}


    private static function getNormalizedText( $text ) {
        $text = self::normalizeForSearch($text);
        $lc = self::legalSearchChars() . '&#;';
        $text = preg_replace( "/<\\/?\\s*[A-Za-z][^>]*?>/",
        ' ', self::lc( " " . $text . " " ) ); # Strip HTML markup
        $text = preg_replace( "/(^|\\n)==\\s*([^\\n]+)\\s*==(\\s)/sD",
            "\\1\\2 \\2 \\2\\3", $text ); # Emphasize headings

        # Strip external URLs
        $uc = "A-Za-z0-9_\\/:.,~%\\-+&;#?!=()@\\x80-\\xFF";
        $protos = "http|https|ftp|mailto|news|gopher";
        $pat = "/(^|[^\\[])({$protos}):[{$uc}]+([^{$uc}]|$)/";
        $text = preg_replace( $pat, "\\1 \\3", $text );

        $p1 = "/([^\\[])\\[({$protos}):[{$uc}]+]/";
        $p2 = "/([^\\[])\\[({$protos}):[{$uc}]+\\s+([^\\]]+)]/";
        $text = preg_replace( $p1, "\\1 ", $text );
        $text = preg_replace( $p2, "\\1 \\3 ", $text );

        # Internal image links
        $pat2 = "/\\[\\[image:([{$uc}]+)\\.(gif|png|jpg|jpeg)([^{$uc}])/i";
        $text = preg_replace( $pat2, " \\1 \\3", $text );

        $text = preg_replace( "/([^{$lc}])([{$lc}]+)]]([a-z]+)/",
            "\\1\\2 \\2\\3", $text ); # Handle [[game]]s

        # Strip all remaining non-search characters
        $text = preg_replace( "/[^{$lc}]+/", " ", $text );

        /**
         * Handle 's, s'
         *
         *   $text = preg_replace( "/([{$lc}]+)'s /", "\\1 \\1's ", $text );
         *   $text = preg_replace( "/([{$lc}]+)s' /", "\\1s ", $text );
         *
         * These tail-anchored regexps are insanely slow. The worst case comes
         * when Japanese or Chinese text (ie, no word spacing) is written on
         * a wiki configured for Western UTF-8 mode. The Unicode characters are
         * expanded to hex codes and the "words" are very long paragraph-length
         * monstrosities. On a large page the above regexps may take over 20
         * seconds *each* on a 1GHz-level processor.
         *
         * Following are reversed versions which are consistently fast
         * (about 3 milliseconds on 1GHz-level processor).
         */
        $text = strrev( preg_replace( "/ s'([{$lc}]+)/", " s'\\1 \\1", strrev( $text ) ) );
        $text = strrev( preg_replace( "/ 's([{$lc}]+)/", " s\\1", strrev( $text ) ) );

        # Strip wiki '' and '''
        $text = preg_replace( "/''[']*/", " ", $text );

        return $text;
	}
    public static function legalSearchChars() {
		return "A-Za-z_'.0-9\\x80-\\xFF\\-";
	}


	protected static function normalizeForSearch( $string ) {
		static $full = null;
		static $half = null;

		if ( $full === null ) {
			$fullWidth = "０１２３４５６７８９ＡＢＣＤＥＦＧＨＩＪＫＬＭＮＯＰＱＲＳＴＵＶＷＸＹＺａｂｃｄｅｆｇｈｉｊｋｌｍｎｏｐｑｒｓｔｕｖｗｘｙｚ";
			$halfWidth = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
			$full = str_split( $fullWidth, 3 );
			$half = str_split( $halfWidth );
		}

		$string = str_replace( $full, $half, $string );
		return $string;
	}

		/**
	 * @param string $str
	 * @param bool $first
	 * @return mixed|string
	 */
	static function lc( $str, $first = false ) {
		if ( $first ) {
			if ( self::isMultibyte( $str ) ) {
				return mb_strtolower( mb_substr( $str, 0, 1 ) ) . mb_substr( $str, 1 );
			} else {
				return strtolower( substr( $str, 0, 1 ) ) . substr( $str, 1 );
			}
		} else {
			return self::isMultibyte( $str ) ? mb_strtolower( $str ) : strtolower( $str );
		}
	}

	/**
	 * @param string $str
	 * @return bool
	 */
	static function isMultibyte( $str ) {
		return strlen( $str ) !== mb_strlen( $str );
	}


    	/**
	 * Armor a case-folded UTF-8 string to get through MySQL's
	 * fulltext search without being mucked up by funny charset
	 * settings or anything else of the sort.
	 * @param array $matches
	 * @return string
	 */
	protected static function stripForSearchCallback( $string ) {
		return 'u8' . bin2hex( $string[1] );
	}

}
