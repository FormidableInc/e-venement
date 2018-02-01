<?php
/**********************************************************************************
*
*	    This file is part of e-venement.
*
*    e-venement is free software; you can redistribute it and/or modify
*    it under the terms of the GNU General Public License as published by
*    the Free Software Foundation; either version 2 of the License.
*
*    e-venement is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU General Public License for more details.
*
*    You should have received a copy of the GNU General Public License
*    along with e-venement; if not, write to the Free Software
*    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*
*    Copyright (c) 2006-2011 Baptiste SIMON <baptiste.simon AT e-glop.net>
*    Copyright (c) 2006-2011 Libre Informatique [http://www.libre-informatique.fr/]
*
***********************************************************************************/
?>
<?php

class MySearchAnalyzer extends Doctrine_Search_Analyzer_Utf8
{
  protected static $_stopwords = array(
  /*
    'l',
    'le',
    'la',
    'les',
    'un',
    'une',
    'à',
    'de',
    'ma',
    'mon',
    'mes',
    'tes',
    'ton',
    'ta',
  */
  );
  
  public static $cutchars = array(
          '@',
          '.',',','?','!','¡','¿',';','—',
          '♠','♣','♥','♦',
          '-','+',
          '←','↑','→','↓',
          "'",'’','´',
          '●','•',
          '¼','½','¾',
          '“', '”', '„',
          '°','™','©','®',
          '³','²',
  );
  
      public function analyze($text, $encoding = null)
      {
        // translatable special chars
        $transliterate = sfConfig::get('software_internals_transliterate',array());
        $text = str_replace(preg_split('//u', $transliterate['from'], -1), preg_split('//u', $transliterate['to'], -1), $text);
        
        // considering very special chars as spaces
        $text = str_replace(self::$cutchars,' ',$text);
        
        $charset = sfConfig::get('software_internals_charset');
        $text = mb_strtolower(iconv($charset['db'],$charset['ascii'],$text));
        
        // directly copied from lib/vendor/symfony/lib/plugins/sfDoctrinePlugin/lib/vendor/doctrine/Doctrine/Search/Analyzer/Utf8.php
        if (is_null($encoding))
          $encoding = isset($this->_options['encoding']) ? $this->_options['encoding']:'utf-8';

        // check that $text encoding is utf-8, if not convert it
        if (strcasecmp($encoding, 'utf-8') != 0 && strcasecmp($encoding, 'utf8') != 0)
            $text = iconv($encoding, 'UTF-8', $text);
        
        //$text = preg_replace('/\s[0-9]+\s/', ' ', $text);
        $text = preg_replace('/\s[^\s]\s/', ' ', $text);
        $text = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text);
        $text = preg_replace('/\s\s+/', ' ', $text);

        $terms = explode(' ', $text);
        
        $ret = array();
        if ( ! empty($terms)) {
            foreach ($terms as $i => $term) {
                if (empty($term)) {
                    continue;
                }
                $lower = mb_strtolower(trim($term), 'UTF-8');

                if (in_array($lower, self::$_stopwords)) {
                    continue;
                }

                $ret[$i] = $lower;
            }
        }
        return $ret;
      }
}
