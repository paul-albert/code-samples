<?php

/**
 * Class for generation of randomly spins sentences.
 *
 * Command to generate random spinned sentence:
 * 
 * /path/to/public_html/protected/yiic randomsentencespinning generate --sourceSentence="{Please|Just} make this {cool|awesome|random} test sentence {rotate {quickly|fast} and random|spin and be random}" --count=10
 * 
 * (Please do sure that file yiic is allowable to run (chmod +x file))
 * (Also note that for our command must be passed one parameter for source sentence)
 * 
 */

class RandomSentenceSpinningCommand extends CConsoleCommand
{

    /**
     * declaring section for some important class constants
     */
    const RESULT_HEADER            = "\nRandomly spinned sentence(s):\n\n";
    const REGEXP_PATTERN_MODIFIERS = 'is';
    const REGEXP_PATTERN_BORDER    = '/';
    const REGEXP_OPENING_BRACKET   = '{';
    const REGEXP_CLOSING_BRACKET   = '}';
    const DELIMITER                = '|';
    const GENERATES_COUNT          = 1;
    
    /**
     * Main action for randomly spinned sentence generation from source.
     * 
     * @param string $sourceSentence - source sentence
     * @param int $count - how many times need to generate (by default 1)
     */
    public function actionGenerate($sourceSentence, $count = self::GENERATES_COUNT)
    {
        // check for first argument of source sentence that is needed to spin
        if (empty($sourceSentence)) {
            throw new CException('The "sourceSentence" argument must be specified.');
        }
        // check for (obligatory) second argument of generations count
        if ((int) $count < 1) {
            throw new CException('The "count" argument must be specified as positive integer.');
        }
        
        // output header of result
        print self::RESULT_HEADER;
        
        // sentence spin in cycle from 1 to $count times
        for ($i = 0; $i < (int) $count; $i++) {
            print ($i + 1) . ') ' . $this->generateSpinned($sourceSentence) . "\n";
        }
    }
    
    /**
     * Spins source sentence randomly (basic logic is placing here).
     * 
     * @param string $str - source string for spin
     * @return type - spinned string
     */
    private function generateSpinned($str)
    {
        // build reg-exp pattern for search
        $regExpPattern = implode('', array(
            self::REGEXP_PATTERN_BORDER,    // left border for reg-exp
            self::REGEXP_OPENING_BRACKET,   // search for opening bracket (for include)
            '([^',                          // search all symbols except of opening and closing brackets
            self::REGEXP_OPENING_BRACKET,   // search for opening bracket (for exclude)
            self::REGEXP_CLOSING_BRACKET,   // and search for closing bracket too (for exclude)
            ']*)',                          // how many symbols search (0 or more)
            self::REGEXP_CLOSING_BRACKET,   // search for closing bracket (for include)
            self::REGEXP_PATTERN_BORDER,    // right border for reg-exp
            self::REGEXP_PATTERN_MODIFIERS, // modifiers for reg-exp
        )); // will be like as '/{([^{}]*)}/is' that is good for nested search
        
        // set empty array for found matches (before searching)
        $matches = array();
        
        // below - main cycle for search matches until all matches are found:
        
        // search of matches in string (through regular expressions mechanism and through "closures")
        $find = function($regExpPattern, $str, &$matches) { return (int) preg_match_all($regExpPattern, $str, $matches); };
        while ($find($regExpPattern, $str, $matches) > 0) {
            // check of existence for found matches (0 - source strings, 1 - found strings)
            if (isset($matches[0]) && isset($matches[1])) {
                // set count of found matches
                $foundMatchesCount = count($matches[1]);
                // cycle for found strings
                for ($i = 0;$i < $foundMatchesCount; $i++) {
                    // search for needed pieces of text in found string (through "closures")
                    $getPiecesByDelimiter = function($text) { return explode(self::DELIMITER, $text); };
                    $pieces = $getPiecesByDelimiter($matches[1][$i]);
                    
                    // shuffle for pieces array (i.e. randomizes that array) (through "closures")
                    $randomize = function(&$array) { shuffle($array); };
                    $randomize($pieces);
                    
                    // get first item of array (through "closures")
                    $firstItem = function($array) { return array_shift($array); };
                    
                    // and change source string through replacing source places by found strings (through "closures")
                    $replaced = function($search, $replace) use($str) { return str_replace($search, $replace, $str); };
                    $str = $replaced($matches[0][$i], $firstItem($pieces));
                }
            }
        }
        
        // return spinned string
        return $str;
    }
    
}
