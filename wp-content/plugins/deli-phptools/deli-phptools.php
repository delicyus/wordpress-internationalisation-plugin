<?php 
/*
Plugin Name: Delicyus - PHP tools
Description: Functions utilitaires pour formatter, afficher...
Version: 201708
Author: delicyus
Author URI: http://delicyus.com
License: GPL2
*/
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) die;




/**
 * 	BETTER VAR DUMP
 *	@return 	le dump() dans du html
 * 	@author 	delicyus.com
 * */

function tab($arg=null)
{
		echo '<pre style="border:1px dashed #ccc;background-color:#f0e9f4">';
		var_dump($arg);
		echo '</pre>';
}
function tt($arg){
	return tab($arg);
}
/**
 * 	CHECK IF A REMOTE FILE EXISTS
 *
 *	@return TRUE/FALSE
 *
 * 	@package PHP add-ons
 * 	@since alpha
 * 	@author delicyus.com
 * */
	function checkRemoteFile($url){
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL,$url);
	    // don't download content
	    curl_setopt($ch, CURLOPT_NOBODY, 1);
	    curl_setopt($ch, CURLOPT_FAILONERROR, 1);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    if(curl_exec($ch)!==FALSE)
	    {
	        return true;
	    }
	    else
	    {
	        return false;
	    }
	}

/**
 * 	CURL HELPER FUNCTION
 *
 * 	@param	$url
 * 	@return Contenu du fichier
 *
 * 	@package PHP add-ons
 * */
	function curl_get($url) {
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
		$return = curl_exec($curl);
		curl_close($curl);
		return $return;
	}


/**Converti un timestamp vers une date 2014-09-08 avec strftime
 * 	@return la date en version humaine
 * 	@package PHP add-ons
 *
 * 	 */
	function humanDate($timestamp)
	{
		if(empty($timestamp))
		return false;

		#return (strftime( '%d %b %Y <small>%Hh %Mm %Ss</small>', $timestamp ));
		return (strftime( '%d %b %Y', $timestamp ));
	}

	/** Converti un timestamp vers une date 2014-09-08 14:56:25
	 * 	@return la date en version humaine
	 * 	@package PHP add-ons
	 *
	 *
	 * 	@since		beta 1.1
	 * */
	function UnixtoDate($timestamp , $format = null )
	{
		if(empty($timestamp))
		return false;

		if($format===null)
		$format =  "Y-m-d H:i:s" ;

		return date(  $format , $timestamp );
	}





#  Tronquer une chaine en tenat compte du HTML
/**
 * Truncates text.
 *
 * Cuts a string to the length of $length and replaces the last characters
 * with the ending if the text is longer than length.
 *
 * @param string  $text String to truncate.
 * @param integer $length Length of returned string, including ellipsis.
 * @param mixed $ending If string, will be used as Ending and appended to the trimmed string. Can also be an associative array that can contain the last three params of this method.
 * @param boolean $exact If false, $text will not be cut mid-word
 * @param boolean $considerHtml If true, HTML tags would be handled correctly
 * @return string Trimmed string.
 */

	function truncate($text, $length = 100, $ending = '...', $exact = true, $considerHtml = false) {
		if (is_array($ending)) {
			extract($ending);
		}
		if ($considerHtml) {
			if (mb_strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
				return $text;
			}
			$totalLength = mb_strlen($ending);
			$openTags = array();
			$truncate = '';
			preg_match_all('/(<\/?([\w+]+)[^>]*>)?([^<>]*)/', $text, $tags, PREG_SET_ORDER);
			foreach ($tags as $tag) {
				if (!preg_match('/img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param/s', $tag[2])) {
					if (preg_match('/<[\w]+[^>]*>/s', $tag[0])) {
						array_unshift($openTags, $tag[2]);
					} else if (preg_match('/<\/([\w]+)[^>]*>/s', $tag[0], $closeTag)) {
						$pos = array_search($closeTag[1], $openTags);
						if ($pos !== false) {
							array_splice($openTags, $pos, 1);
						}
					}
				}
				$truncate .= $tag[1];

				$contentLength = mb_strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', ' ', $tag[3]));
				if ($contentLength + $totalLength > $length) {
					$left = $length - $totalLength;
					$entitiesLength = 0;
					if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', $tag[3], $entities, PREG_OFFSET_CAPTURE)) {
						foreach ($entities[0] as $entity) {
							if ($entity[1] + 1 - $entitiesLength <= $left) {
								$left--;
								$entitiesLength += mb_strlen($entity[0]);
							} else {
								break;
							}
						}
					}

					$truncate .= mb_substr($tag[3], 0 , $left + $entitiesLength);
					break;
				} else {
					$truncate .= $tag[3];
					$totalLength += $contentLength;
				}
				if ($totalLength >= $length) {
					break;
				}
			}

		} else {
			if (mb_strlen($text) <= $length) {
				return $text;
			} else {
				$truncate = mb_substr($text, 0, $length - strlen($ending));
			}
		}
		if (!$exact) {
			$spacepos = mb_strrpos($truncate, ' ');
			if (isset($spacepos)) {
				if ($considerHtml) {
					$bits = mb_substr($truncate, $spacepos);
					preg_match_all('/<\/([a-z]+)>/', $bits, $droppedTags, PREG_SET_ORDER);
					if (!empty($droppedTags)) {
						foreach ($droppedTags as $closingTag) {
							if (!in_array($closingTag[1], $openTags)) {
								array_unshift($openTags, $closingTag[1]);
							}
						}
					}
				}
				$truncate = mb_substr($truncate, 0, $spacepos);
			}
		}

		$truncate .= $ending;

		if ($considerHtml) {
			foreach ($openTags as $tag) {
				$truncate .= '</'.$tag.'>';
			}
		}

		return $truncate;
	}

	/** @usage
	 *
	$text1 = 'The quick brown fox jumps over the lazy dog';
	$text2 = 'Heiz&ouml;lr&uuml;cksto&szlig;abd&auml;mpfung';
	$text3 = '<b>&copy; 2005-2007, Cake Software Foundation, Inc.</b><br />written by Alexander Wegener';
	$text4 = '<img src="mypic.jpg"> This image tag is not XHTML conform!<br><hr/><b>But the following image tag should be conform <img src="mypic.jpg" alt="Me, myself and I" /></b><br />Great, or?';
	$text5 = '0<b>1<i>2<span class="myclass">3</span>4<u>5</u>6</i>7</b>8<b>9</b>0';

	// normal truncate tests
	echo htmlentities(truncate($text1, 15)).'<br/>';
	echo htmlentities(truncate($text1, 15, '...', false)).'<br/>';
	echo htmlentities(truncate($text1, 100)).'<br/>';
	echo htmlentities(truncate($text2, 10, '...')).'<br/>';
	echo htmlentities(truncate($text2, 10, '...', false)).'<br/>';
	echo htmlentities(truncate($text3, 20)).'<br/>';
	echo htmlentities(truncate($text4, 15)).'<br/>';
	echo htmlentities(truncate($text5, 6, '')).'<br/>';

	echo '---------------------------------------------<br/>';

	// html considering tests
	echo htmlentities(truncate($text1, 15, '...', true, true)).'<br/>';
	echo htmlentities(truncate($text1, 15, '...', false, true)).'<br/>';
	echo htmlentities(truncate($text2, 10, '...', true, true)).'<br/>';
	echo htmlentities(truncate($text2, 10, '...', false, true)).'<br/>';
	echo htmlentities(truncate($text3, 20, '...', true, true)).'<br/>';
	echo htmlentities(truncate($text4, 15, '...', true, true)).'<br/>';
	echo htmlentities(truncate($text4, 45, '...', true, true)).'<br/>';
	echo htmlentities(truncate($text4, 90, '...', true, true)).'<br/>';
	echo htmlentities(truncate($text5, 6, '', true, true)).'<br/>';
	 *
	 * */


	/** ! ! ADMIN ONLY ! !

	 * */
		function ttt($arg=null)
		{
			if ( current_user_can( 'manage_options' ) )
			{
					echo '<pre>';
					var_dump($arg);
					echo '</pre>';
			}			
		}
?>