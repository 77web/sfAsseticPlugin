<?php
/**
 * configure sfAsseticPlugin.
 *
 * @package     sfAsseticPlugin
 * @subpackage  config
 * @auther      Hiromi Hishida<info@77-web.com>
 * @version     n/a
 */
class sfAsseticPluginConfiguration extends sfPluginConfiguration
{
  protected $enableStylesheets = false;
  protected $enableJavascripts = false;
  protected $compressStylesheets = false;
  protected $compressJavascripts = false;
  
  public function initialize()
  {
    if(sfConfig::get('sf_environment')=='prod')
    {
      //pending: read settings from any yaml?
      $this->enableStylesheets = sfConfig::get('sfAsseticPlugin_enable_css', true);
      $this->compressStylesheets = sfConfig::get('sfAsseticPlugin_compress_css', true);
      $this->enableJavascripts = sfConfig::get('sfAsseticPlugin_enable_js', true);
      $this->compressJavascripts = sfConfig::get('sfAsseticPlugin_compress_js', true);
      
      $this->dispatcher->connect('response.filter_content', array($this, 'listenToResponseFilterContent'));
    }
  }
  
  public function listenToResponseFilterContent(sfEvent $event, $content)
  {
    $response = sfContext::getInstance()->getResponse();
    
    if($this->enableStylesheets)
    {
      $content = $this->embedStylesheets($response, $content);
    }
    
    if($this->enableJavascripts)
    {
      $content = $this->embedJavascripts($response, $content);
    }
    
    return $content;
  }
  
  
  protected function embedStylesheets(sfResponse $response, $content)
  {
    $webDir = sfConfig::get('sf_web_dir');
    $assetsCss = array('screen'=>'', 'print'=>'', 'all'=>'');
    foreach($response->getStylesheets() as $file => $options)
    {
      $mediaType = isset($options['media']) ? $options['media'] : 'screen';
      
      if(strpos($file, '://')!==false)
      {
        $path = $file;
      }
      else
      {
        if(strpos($file, '.css')===false)
        {
          $file .= '.css';
        }
        
        if(substr($file, 0, 1)!='/')
        {
          $file = '/css/'.$file;
        }
        $path = $webDir.$file;
      }
      $css = file_get_contents($path);
      
      if(preg_match_all("/url\([^)]+\)/i", $css, $matches, PREG_SET_ORDER))
      {
        $currentPath = dirname($file).'/';
        $parentPath = dirname(dirname($file)).'/';
        foreach($matches as $rawPath)
        {
          $replacedPath = str_replace(array('../', './'), array($parentPath, $currentPath), $rawPath);
          $css = str_replace($rawPath, $replacedPath, $css);
        }
      }
      $assetsCss[$mediaType] .= $css;
    }
    if($this->compressStylesheets)
    {
      foreach($assetsCss as $mediaType => $css)
      {
        $assetsCss[$mediaType] = Minify::minifyStylesheet($css);
      }
    }
    $styles = '';
    foreach($assetsCss as $mediaType => $css)
    {
      if(''!==$css)
      {
        $styles .= '<style type="text/css" media="'.$mediaType.'">'.$css.'</style>';
      }
    }
    
    if('' !== $styles)
    {
      $csspattern = "/^<link[^>]+rel=\"stylesheet\"[^>]+>$/im";
      if(preg_match_all($csspattern, $content, $matches, PREG_SET_ORDER))
      {
        foreach($matches as $match)
        {
          $tag = $match[0];
          $pattern2 = "/<head>.*".str_replace('/', "\/", $tag).".*<\/head>/ims";
          if(preg_match($pattern2, $content))
          {
            $content = str_replace($tag."\n", '', $content);
          }
        }
      }
      return str_replace('</head>', $styles.'</head>', $content);
    }
    
    return $content;
  }
  
  protected function embedJavascripts(sfResponse $response, $content)
  {
    $webDir = sfConfig::get('sf_web_dir');
    $assetsJs = '';
    foreach($response->getJavascripts() as $file => $options)
    {
      if(strpos($file, '://')!==false)
      {
        $path = $file;
      }
      else
      {
        if(strpos($file, '.js')===false)
        {
          $file .= '.js';
        }
        
        if(substr($file, 0, 1)!='/')
        {
          $file = '/js/'.$file;
        }
        $path = $webDir.$file;
      }
      $assetsJs .= file_get_contents($path);
    }
    if($this->compressJavascripts)
    {
      $assetsJs = Minify::minifyJavascript($assetsJs);
    }
    
    if('' !== $assetsJs)
    {
      $scripts = '<script type="text/javascript">'.$assetsJs.'</script>';
      $jspattern = "/^<script[^<]+?<\/script>$/im";
      if(preg_match_all($jspattern, $content, $matches, PREG_SET_ORDER))
      {
        foreach($matches as $match)
        {
          $tag = $match[0];
          $pattern2 = "/<head>.*".str_replace('/', "\/", $tag).".*<\/head>/ims";
          if(preg_match($pattern2, $content))
          {
            $content = str_replace($tag."\n", '', $content);
          }
        }
      }
      return str_replace('</head>', $scripts.'</head>', $content);
    }
    
    return $content;
  }
}