<?php

/*
Plugin Name: Pager Widget
Plugin URI: http://dcdcgroup.org
Description: Widget that provides "Parent | Previous | Next" buttons to navigate between pages at the same hierarchy level (and up to the parent page). You can modify the settings to choose which words you want to use. To enable, first activate the plugin, then add the widget to a sidebar in the Widgets settings page.
Author: Paul Ryan, Programmer, Distance Course Design & Consulting (DCDC), College of Education, University of Hawai'i at Manoa
Version: 1.7.3
Author URI: http://combinelabs.com/paul
*/

/**
 * PagerWidget Class
 */
class PagerWidget extends WP_Widget {
  // constructor
  function PagerWidget() {
    parent::WP_Widget(
      'wp-pager', 
      $name='Pager Widget', 
      $widget_options = array(
        'description' => 'Print "Parent | Previous | Next" links to navigate between pages at the same level in the page hierarchy (and up to the parent page).'
      )
    );
  }
  
  // @see WP_Widget::widget ... (function to display widget)
  function widget($args, $instance) {
    extract($args);
    $labelParent = esc_attr($instance['labelParent']);
    $labelPrev = esc_attr($instance['labelPrev']);
    $labelNext = esc_attr($instance['labelNext']);
    $pageDepth = intval($instance['pageDepth']);
    $isStoryMode = intval($instance['isStoryMode']);
    
    echo $before_widget;
    echo $before_title;
    echo $after_title;
    
    // Get page object (since we're outside of the loop)
    //global $wp_query;
    //$post = $wp_query->get_queried_object();
    global $post;
    
    if ($isStoryMode==1) { // Operate the pager in story mode (walk through site map tree)
      // Print links to parent, previous, and next page
      echo "<div id='linksPrevNext'>";
      
      $allPages = get_pages(array(
        'sort_column' => 'menu_order',
        'sort_order' => 'asc',
      ));
      for ($i=0; $i<count($allPages); $i++) // advance iterator to current page
        if ($allPages[$i]->ID == $post->ID)
          break;
      $nextURI = $i<count($allPages)-1 ? get_permalink($allPages[$i+1]->ID) : "";
      $nextTitle = strlen($nextURI)>0 ? $allPages[$i+1]->post_title : "";
      $prevURI = $i>0 ? get_permalink($allPages[$i-1]->ID) : "";
      $prevTitle = strlen($prevURI)>0 ? $allPages[$i-1]->post_title : "";
      
      if (strlen($prevURI)>0)
        echo "<a href='$prevURI'>".str_replace("%title",$prevTitle,$labelPrev)."</a>";
      if (strlen($prevURI)>0 && strlen($nextURI)>0) // Display a break if both prev and next links are shown
        echo "&nbsp;&nbsp; | &nbsp;&nbsp;";
      if (strlen($nextURI)>0)
        echo "<a href='$nextURI'>".str_replace("%title",$nextTitle,$labelNext)."</a>";
      
      echo "</div>";

    } else { // Operate the pager in classic mode (page between siblings at a certain depth and their parent)
      // Make sure we're on a level $pageDepth page in the hierarchy that has siblings, 
      // or on a level $pageDepth-1 page that has children
      $hierarchyDepth = 0;
      $page = $post;
      while ($page->post_parent) {
        $page = get_post($page->post_parent);
        $hierarchyDepth++;
      }
      $children = wp_list_pages("child_of={$post->ID}&echo=0");
      $siblings = wp_list_pages("title_li=&child_of={$post->post_parent}&echo=0&depth=1");
  
      if (!(($hierarchyDepth==$pageDepth && $siblings) || ($hierarchyDepth==($pageDepth-1) && $children))) {
        echo $after_widget;
        return;
      }
    
      // Print links to parent, previous, and next page
      echo "<div id='linksPrevNext'>";
      if ($hierarchyDepth==($pageDepth-1) && $children) { // we're on a level $pageDepth-1 page that has children
        // Get links to children pages
        $numberOfMatches = preg_match_all("/<a href=[\"|\'](.*?)[\"|\'].*?>(.*?)<\/a>/i",$children,$matches,PREG_PATTERN_ORDER);
        $parentURI = get_permalink($post->ID);
        $parentTitle = $post->post_title;
        $nextURI = "";
        if (count($matches[1]) > 0 ) {
          $nextURI = $matches[1][0];
          $nextTitle = $matches[2][0];
        }
        if (strlen($nextURI)>0) {
          echo "<a href='$nextURI'>".str_replace("%title",$nextTitle,$labelNext)."</a>";
        }
      } else if ($hierarchyDepth==$pageDepth && $siblings) { // level $pageDepth page that has siblings
        // Get links to sibling pages
        $numberOfMatches = preg_match_all("/<a href=[\"|\'](.*?)[\"|\'].*?>(.*?)<\/a>/i",$siblings,$matches,PREG_PATTERN_ORDER);
        // Get links to parent, previous, and next page
        $currentSlug = get_permalink($post->ID); //$post->post_name;
        $parentURI = get_permalink($post->post_parent);
        $parentTitle = $post->post_title;
        $prevURI = get_permalink($post->post_parent);
        $nextURI = "";
        for ($i=0; $i<count($matches[1]); $i++) {
          if (strpos($matches[1][$i],$currentSlug) !== FALSE) { // we found the current page
            if ($i < count($matches[1])-1) {
              $nextURI = $matches[1][$i+1];
              $nextTitle = $matches[2][$i+1];
            }
            break;
          }
          $prevURI = $matches[1][$i];
          $prevTitle = $matches[2][$i];
        }
        echo "  <a href='$parentURI'>".str_replace("%title",$parentTitle,$labelParent)."</a>";
        echo "  &nbsp;&nbsp; | &nbsp;&nbsp;";
        if (strlen($prevURI)>0 && $prevURI!==$parentURI)
          echo "<a href='$prevURI'>".str_replace("%title",$prevTitle,$labelPrev)."</a>";
        if (strlen($prevURI)>0 && $prevURI!==$parentURI && strlen($nextURI)>0)
          echo "&nbsp;&nbsp; | &nbsp;&nbsp;";
        if (strlen($nextURI)>0)
          echo "<a href='$nextURI'>".str_replace("%title",$nextTitle,$labelNext)."</a>";
      }
      echo "</div>";
    }

    echo $after_widget;
  }
  
  // @see WP_Widget::update ... (function to save posted form data from widget admin panel)
  function update($new_instance, $old_instance) {
    if (!isset($new_instance['submit']))
      return false;
    $instance = $old_instance;
    $instance['labelParent'] = strip_tags($new_instance['labelParent']);
    $instance['labelPrev'] = strip_tags($new_instance['labelPrev']);
    $instance['labelNext'] = strip_tags($new_instance['labelNext']);
    $instance['pageDepth'] = intval($new_instance['pageDepth']);
    $instance['isStoryMode'] = intval($new_instance['isStoryMode']);
    return $instance;
  }
  
  // @see WP_Widget::form ... (function to display options when widget added to sidebar)
  function form($instance) {
    $instance = wp_parse_args((array)$instance, array(
      'labelParent' => 'Back to Overview', 
      'labelPrev'   => '&laquo; Previous Page', 
      'labelNext'   => 'Next Page &raquo;',
      'pageDepth'   => 1,
      'isStoryMode' => 0
    ));
    
    $valueParent = esc_attr($instance['labelParent']);
    $idParent = $this->get_field_id('labelParent');
    $nameParent = $this->get_field_name('labelParent');
    echo "<label for='$idParent'>Label for Parent link: ";
    echo "<input type='text' class='widefat' id='$idParent' name='$nameParent' value='$valueParent' />";
    echo "</label><br/><br/>";

    $valuePrev = esc_attr($instance['labelPrev']);
    $idPrev = $this->get_field_id('labelPrev');
    $namePrev = $this->get_field_name('labelPrev');
    echo "<label for='$idPrev'>Label for Previous link: ";
    echo "<input type='text' class='widefat' id='$idPrev' name='$namePrev' value='$valuePrev' />";
    echo "</label><br/><br/>";

    $valueNext = esc_attr($instance['labelNext']);
    $idNext = $this->get_field_id('labelNext');
    $nameNext = $this->get_field_name('labelNext');
    echo "<label for='$idNext'>Label for Next link: ";
    echo "<input type='text' class='widefat' id='$idNext' name='$nameNext' value='$valueNext' />";
    echo "</label><br/><br/>";
    
    echo "<small>Note: you can use %title to display the page title in the pager links</small><br/><br/>";

    $valueDepth = intval($instance['pageDepth']);
    $idDepth = $this->get_field_id('pageDepth');
    $nameDepth = $this->get_field_name('pageDepth');
    echo "<label for='$idDepth'>Show pager on this level of the hierarchy (0 is top level): ";
    echo "<input type='text' class='widefat' id='$idDepth' name='$nameDepth' value='$valueDepth' />";
    echo "</label><br/><br/>";
    
    $valueStoryMode = intval($instance['isStoryMode']);
    $checked = $valueStoryMode==1 ? "checked='checked'" : "";
    $idStoryMode = $this->get_field_id('isStoryMode');
    $nameStoryMode = $this->get_field_name('isStoryMode');
    echo "<input type='checkbox' id='$idStoryMode' name='$nameStoryMode' value='1' $checked />";
    echo "<label for='$idStoryMode'> Enable Story Mode (page through all site content, not just content under a parent item). Note that the level specified above has no meaning if this is enabled.</label><br/><br/>";

    echo "<small>Note: you can apply CSS styles to #linksPrevNext</small><br/>";

    $idSubmit = $this->get_field_id('submit');
    $nameSubmit = $this->get_field_name('submit');
    echo "<input type='hidden' id='$idSubmit' name='$nameSubmit' value='1' />";
  }
}

add_action('widgets_init', create_function('', 'return register_widget("PagerWidget");'));

?>
