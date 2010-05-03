<?php 
/** 
 * Class of view for JSON 
 * 
 * @author Juan Basso 
 * @url http://blog.cakephp-brasil.org/2008/09/11/trabalhando-com-json-no-cakephp-12/ 
 * @licence MIT 
 */ 

class JsonView extends View { 

    function render($action = null, $layout = null, $file = null) { 

		if (!isset($this->viewVars['response'])) { 
            return "[]";//parent::render($action, $layout, $file); 
        } 
	
        if ( array_key_exists('response', $this->viewVars )) { 
            return $this->renderJson($this->viewVars['response']); 
        } 

/*
        if (is_array($vars)) { 
            $jsonVars = array(); 
            foreach ($vars as $var) { 
                if (isset($this->viewVars[$var])) { 
                    $jsonVars[$var] = $this->viewVars[$var]; 
                } else { 
                    $jsonVars[$var] = null; 
                } 
            } 
            return $this->renderJson($jsonVars); 
        } 
*/
        return 'null'; 
    } 

    function renderJson($content) { 
        //header('Content-type: application/json'); 
        if (function_exists('json_encode')) { 
            // PHP 5.2+
            $out = json_encode($content); 
        } else { 
            // For PHP 4 until PHP 5.1
            $out = $this->encode($content);
        } 
        Configure::write('debug', 0); // Omit time in end of view 
        return $out; 
    } 

    // Adapted from http://www.php.net/manual/en/function.json-encode.php#82904. Author: Steve (30-Apr-2008 05:35) 
    function encode ($response) {
        if (is_null($response)) {
            return 'null'; 
        } 
        if ($response === false) {
            return 'false'; 
        } 
        if ($response === true) {
            return 'true'; 
        } 
        if (is_scalar($response)) {
            if (is_float($response)) {
                return floatval(str_replace(",", ".", strval($response)));
            } 

            if (is_string($response)) {
                static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"')); 
                return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $response) . '"';
            } else { 
                return $response;
            } 
        } 
        $isList = true; 
        for ($i = 0, reset($response); $i < count($response); $i++, next($response)) {
            if (key($response) !== $i) {
                $isList = false; 
                break; 
            } 
        } 
        $result = array(); 
        if ($isList) { 
            foreach ($response as $v) {
                $result[] = $this->encode($v);
            } 
            return '[' . join(',', $result) . ']'; 
        } else { 
            foreach ($response as $k => $v) {
                $result[] = $this->encode($k) . ':' . $this->encode($v);
            } 
            return '{' . join(',', $result) . '}'; 
        } 
    } 

}
?>