<?php

class Minify
{
  public static function minifyStylesheet($css)
  {
    //remove comments
    $css = preg_replace("/\/\*.+?\*\//is", '', $css);
    //remove whitespaces
    $css = preg_replace("/^\s+/im", '', $css);
    $css = str_replace(array(": ", " {", ", "), array(":", "{", ","), $css);
    //remove \r\n
    $css = str_replace(array("\r", "\n"), '', $css);
    
    return $css;
  }
  
  public static function minifyJavascript($script)
  {
    $params = array();
    $params['js_code'] = $script;
    $params['compilation_level'] = 'SIMPLE_OPTIMIZATIONS';
    $params['output_format'] = 'text';
    $params['output_info'] = 'compiled_code';
    
    $sock = @fsockopen('closure-compiler.appspot.com', 80, $errorno, $errorstr, 30);
    if($sock)
    {
      $param = http_build_query($params);
      
      $post = array();
      $post[] = 'POST /compile HTTP/1.1';
      $post[] = 'Host: closure-compiler.appspot.com';
      $post[] = 'Content-Length: '.strlen($param);
      $post[] = 'Content-Type: application/x-www-form-urlencoded';
      $post[] = 'Connection: close';
      $post[] = '';
      $post[] = $param;
      
      fputs($sock, implode("\r\n", $post));
      $response = '';
      while(!feof($sock))
      {
        $response .= fgets($sock);
      }
      fclose($sock);
      $res = explode("\r\n\r\n", $response);
      $script = $res[1];
    }
    
    return $script;
  }
}