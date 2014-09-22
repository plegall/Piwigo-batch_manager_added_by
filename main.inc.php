<?php
/*
Plugin Name: Batch Manager, Added By
Version: auto
Description: Add filter "Added by" in Batch Manager
Plugin URI: http://piwigo.org/ext/extension_view.php?eid=
Author: plg
Author URI: http://le-gall.net/pierrick
*/

if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

add_event_handler('loc_end_element_set_global', 'bmab_add_filter');
function bmab_add_filter()
{
  global $conf, $template;

  $query = '
SELECT
    u.'.$conf['user_fields']['username'].' AS username,
    i.added_by,
    COUNT(*) AS counter
  FROM '.USERS_TABLE.' AS u
    INNER JOIN '.IMAGES_TABLE.' AS i ON u.'.$conf['user_fields']['id'].' = i.added_by
  GROUP BY i.added_by
  ORDER BY u.'.$conf['user_fields']['username'].' ASC
;';
  $result = pwg_query($query);
  
  $added_by_options = array();
  while ($row = pwg_db_fetch_assoc($result))
  {
    $added_by_options[$row['added_by']] = $row['username'].' ('.l10n_dec('%d photo', '%d photos', $row['counter']).')';
  }
  $template->assign('added_by_options', $added_by_options);

  $template->assign(
    'added_by_selected',
    isset($_SESSION['bulk_manager_filter']['added_by']) ? $_SESSION['bulk_manager_filter']['added_by'] : ''
    );

  $template->set_prefilter('batch_manager_global', 'bmab_add_filter_prefilter');
}

function bmab_add_filter_prefilter($content, &$smarty)
{
  // first we add the (hidden by default) block to select the user
  $pattern = '#</ul>\s*<p class="actionButtons">#ms';
  $replacement = '
      <li id="filter_added_by" {if !isset($filter.added_by)}style="display:none"{/if}>
        <a href="#" class="removeFilter" title="remove this filter"><span>[x]</span></a>
        <input type="checkbox" name="filter_added_by_use" class="useFilterCheckbox" {if isset($filter.added_by)}checked="checked"{/if}>
        {\'Added by %s\'|@translate|sprintf:""}
        <select name="filter_added_by">
          {html_options options=$added_by_options selected=$added_by_selected}
        </select>
      </li>
    </ul>

    <p class="actionButtons">
';
  $content = preg_replace($pattern, $replacement, $content);

  // then we add the "Added by" in the filter selector
  $pattern = '#</select>\s*<a id="removeFilters"#ms';
  $replacement = '
<option value="filter_added_by" {if isset($filter.added_by)}disabled="disabled"{/if}>{\'Added by %s\'|@translate|sprintf:""}</option>
      </select>
      <a id="removeFilters"
';
  $content = preg_replace($pattern, $replacement, $content);

  return $content;
}

add_event_handler('batch_manager_register_filters', 'bmab_register_filter');
function bmab_register_filter($filters)
{
  if (isset($_POST['filter_added_by_use']))
  {
    check_input_parameter('filter_added_by', $_POST, false, PATTERN_ID);
    
    $filters['added_by'] = $_POST['filter_added_by'];
  }

  return $filters;
}

add_event_handler('batch_manager_perform_filters', 'bmab_perform_filter');
function bmab_perform_filter($filter_sets)
{
  if (isset($_SESSION['bulk_manager_filter']['added_by']))
  {
    $query = '
SELECT
    id
  FROM '.IMAGES_TABLE.'
  WHERE added_by = '.$_SESSION['bulk_manager_filter']['added_by'].'
;';
    $filter_sets[] = array_from_query($query, 'id');
  }

  return $filter_sets;
}
?>