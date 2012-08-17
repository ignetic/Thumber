<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package   ExpressionEngine
 * @author   ExpressionEngine Dev Team
 * @copyright   Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license   http://expressionengine.com/user_guide/license.html
 * @link   http://expressionengine.com
 * @since   Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * Thumber Plugin
 *
 * @package   ExpressionEngine
 * @subpackage   Addons
 * @category   Plugin
 * @author   Rob Hodges and Andy Lulham
 * @link   http://www.electricputty.co.uk
 */

$plugin_info = array(
  'pi_name' => 'Thumber',
  'pi_version' => '1.0',
  'pi_author' => 'Rob Hodges and Andy Lulham',
  'pi_author_url' => 'http://www.electricputty.co.uk',
  'pi_description' => 'Create image thumbnails from PDF files',
  'pi_usage' => Thumber::usage()
);


class Thumber {
  public $return_data;
  
  private $base;
  private $thumb_cache_rel_dirname = '/images/thumber';

  private function fetch_params()
  {
    /** -------------------------------------
    /**  Initialise default parameters
    /** -------------------------------------*/
    $default_params = array(
      // TODO: width and height should be a single param here
      'width' => '84',
      'height' => '108',
      'crop' => 'yes',
      'page' => '1',
      'extension' => 'png',
      'link' => 'no'
    );
    
    $this->params = $default_params;
    
    $width = $this->EE->TMPL->fetch_param('width', '');
    $height = $this->EE->TMPL->fetch_param('height', '');    
    
    if ($width || $height) {
      // if either is specified, override both defaults
      $this->params['width'] = $width;
      $this->params['height'] = $height;
    }

    /** -------------------------------------
    /**  Loop through input params, set values
    /** -------------------------------------*/
    if($this->EE->TMPL->tagparams) {
      foreach ($this->EE->TMPL->tagparams as $key => $value) {
        // ignore width and height as special parameters
        if($key != 'width' && $key != 'height') {
          if (array_key_exists($key, $this->params)) {
            // if it's in the default array, it's used by the plugin
            $this->params[$key] = $value;
          } else {
            // otherwise, it'll just be passed straight to the img tag
            $this->custom_params[$key] = $value;
          }
        }
      }
    }
    
    // This is just for convenience
    $this->params['dimensions'] = $this->params['width'] . 'x' . $this->params['height'];
  }

  /**
   * Constructor
   */
  public function __construct()
  {
    $this->EE =& get_instance();
    $this->base = $_SERVER['DOCUMENT_ROOT'];
    $this->thumb_cache_dirname = $this->EE->functions->remove_double_slashes($this->base . '/' . $this->thumb_cache_rel_dirname);
    
    $this->EE->TMPL->log_item('** constructor called **');
  }
  
  /** 
   * Check imagemagick and ghostscript are installed
   */
  private function lib_check()
  {
    if (exec("convert -version 2>&1")) {
      $this->EE->TMPL->log_item('**Thumber** Can\'t find Imagemagick on your server.');
      return false;
    }
    
/*
    // TODO: check if ghostscript is installed
    if (ghostscript is not installed) {
      $this->EE->TMPL->log_item('**Thumber** Can\'t find ghostscript on your server.');
      return false;
    }
*/
    
    return true;
  }
  
  /** 
   * Check the cache folder exists and is writable
   */
  private function cache_folder_check()
  {
    if(!file_exists($this->thumb_cache_dirname)) {
      $this->EE->TMPL->log_item('**Thumber** Cache folder: "' . $this->thumb_cache_rel_dirname . '" does not exist.');
      return false;
    }
    
    if(!is_writable($this->thumb_cache_dirname)) {
      $this->EE->TMPL->log_item('**Thumber** Cache folder: "' . $this->thumb_cache_rel_dirname . '" is not writable.');
      return false;
    }
    
    return true;
  }
  
  /** 
   * Get the full path to a file from either an absolute or relative URL
   */
  private function get_fullpath_from_url($src_url) {
    if (!$src_url) {
      $this->EE->TMPL->log_item('**Thumber** No source URL provided.');
      return false;
    }
    
    //check if the source URL is an absolute URL
    if ( substr( $src_url, 0, 4 ) == 'http' )
    {
      $url = parse_url( $src_url );
      $src_url = $url['path'];
    }
    
    $src_fullpath = $this->EE->functions->remove_double_slashes($this->base . $src_url);
    
    if(!file_exists($src_fullpath)) {
      $this->EE->TMPL->log_item('**Thumber** Source URL: "' . $src_url . '" does not exist.');
      return false;
    }
    
    return $src_fullpath;
  }
  
  /** 
   * This is where the heavy lifting happens! Call imagemagick to actually generate the thumbnail
   * according to the specified parameters
   */
  private function generate_conversion($source, $dest) {
    $page = intval($this->params["page"]) - 1;
    
    $modifier = '';
    if ($this->params["width"] && $this->params["height"]) {
      if($this->params['crop'] == 'yes') {
        $modifier = '^ -gravity center -extent ' . $this->params["dimensions"];
      } else {
        // This modifier forces the specified dimensions,
        // even if it means not preserving aspect ratio
        $modifier = '!';
      }
    }
    
    $exec_str = "convert -resize " . $this->params["dimensions"] . $modifier . ' ' . $source['fullpath'] . "[" . $page . "] " . $dest["fullpath"] . " 2>&1";
    
    $error = exec($exec_str);
    
    if($error) {
      $this->EE->TMPL->log_item('**Thumber** ' . $error);
      return false;
    }
    
    return true;
  }
  
  /** 
   * The function to be called from templates in order to generate thumbnails from PDFs
   */
  public function create()
  {
    $source = array();
    $source["url"] = trim($this->EE->TMPL->fetch_param('src'));
    
    $source["fullpath"] = $this->get_fullpath_from_url($source["url"]);
    if(!$source["fullpath"]) {
      return;
    }
        
    if(!$this->lib_check()) {
      return;
    }

    if(!$this->cache_folder_check()) {
      return;
    }
    
    // populate param and custom_param arrays
    $this->fetch_params();
    
    $source = array_merge($source, pathinfo($source["fullpath"]));
    
    // create dest array
    $dest = array();
    $dest["dirname"] = $this->thumb_cache_dirname;
    
    // create dest filename
    $cropped = ($this->params["width"] && $this->params["height"] && $this->params["crop"] == 'yes') ? '_cropped' : '';
    $param_str = '_pg' . $this->params["page"] . '_' .  $this->params["dimensions"] . $cropped;
    $dest["filename"] = $source["filename"] . $param_str;
    
    // add the rest of the dest array items
    $dest["extension"] = $this->params["extension"];
    $dest["basename"] = $dest["filename"] . "." . $dest["extension"];
    $dest["fullpath"] = $this->EE->functions->remove_double_slashes($this->thumb_cache_dirname . '/' . $dest["basename"]);
    $dest["url"] = $this->EE->functions->remove_double_slashes($this->EE->config->item('site_url') . '/' . $this->thumb_cache_rel_dirname . '/' . $dest["basename"]);
    
    // check whether the image is cached
    if (!file_exists($dest["fullpath"])) {
      // convert pdf to thumb.png
      $success = $this->generate_conversion($source, $dest);
      if(!$success) {
        return;
      }
    }
    
    // generate custom param string
    $custom_param_str = '';
    foreach($this->custom_params as $key => $value) {
      $custom_param_str .= $key . '="' . $value . '" ';
    }
    
    // generate html snippet
    $html_snippet = '<img src="' . $dest["url"] . '" ' . $custom_param_str . ' />';
    
    if($this->params["link"] == "yes") {
      $html_snippet = '<a href="' . $source["url"] . '">' . $html_snippet . '</a>';
    }
    
    return $html_snippet;
  }


  // ----------------------------------------------------------------
  
  /**
   * Plugin Usage
   */
  public static function usage()
  {
    ob_start();
?>

Thumber generates thumbnails from your PDFs. You can call it using a single tag in your template.

Example usage:
  {exp:thumber:create src="/uploads/documents/yourfile.pdf" page='1' extension='jpg' height='250' class='awesome' title='Click to download' link='yes'}

Parameters:
 - src: The source PDF. [Required]
 - width: The width of the generated thumbnail
 - height: The height of the generated thumbnail
 - page: The page of the PDF used to generate the thumbnail [Default: 1]
 - extension: The file type of the generated thumbnail [Default: png]
 - link: Wrap the thumbnail in a link to the PDF [Default: no]
 - crop: Where width and height are both specified, crop to preserve aspect ratio [Default: yes]

Any other parameters will be added to the img tag in the the generated html snippet - so if you want to add an id or class, just add them as parameters.
<?php
    $buffer = ob_get_contents();
    ob_end_clean();
    return $buffer;
  }
}


/* End of file pi.thumber.php */
/* Location: /system/expressionengine/third_party/thumber/pi.thumber.php */