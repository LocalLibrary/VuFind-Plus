<div onmouseup="this.style.cursor='default';" id="popupboxHeader" class="header">
	<a onclick="hideLightbox(); return false;" href="">close</a>
	{translate text='add_favorite_prefix'} {$record->title|escape:"html"} {translate text='add_favorite_suffix'}
</div>
<div id="popupboxContent" class="content">
<form onSubmit="saveRecord('{$id|escape}', '{$source|escape}', this, {literal}{{/literal}add: '{translate text='Add to favorites'}', error: '{translate text='add_favorite_fail'}'{literal}}{/literal}); return false;">
<input type="hidden" name="submit" value="1" />
<input type="hidden" name="record_id" value="{$id|escape}" />
<input type="hidden" name="source" value="{$source|escape}" />
{if !empty($containingLists)}
  <p>
  {translate text='This item is already part of the following list/lists'}:<br />
  {foreach from=$containingLists item="list"}
    <a href="{$path}/MyResearch/MyList/{$list.id}">{$list.title|escape:"html"}</a><br />
  {/foreach}
  </p>
{/if}

{* Only display the list drop-down if the user has lists that do not contain
 this item OR if they have no lists at all and need to create a default list *}
{if (!empty($nonContainingLists) || (empty($containingLists) && empty($nonContainingLists))) }
  {assign var="showLists" value="true"}
{/if}

<table>
  {if $showLists}
  <tr>
    <td>
      {translate text='Choose a List'}
    </td>
  </tr>
  {/if}
  <tr>
    <td>
      {if $showLists}
      <select name="list">
        {foreach from=$nonContainingLists item="list"}
        <option value="{$list.id}">{$list.title|escape:"html"}</option>
        {foreachelse}
        <option value="">{translate text='My Favorites'}</option>
        {/foreach}
      </select>
      {/if}
      <a href="{$path}/MyResearch/ListEdit?id={$id|escape:"url"}&amp;source={$source|escape}"
         onclick="ajaxLightbox('{$path}/MyResearch/ListEdit?id={$id|escape}&source={$source|escape}&lightbox'); return false;">{translate text="or create a new list"}</a>
    </td>
  </tr>
  {if $showLists}
  <tr><td>{translate text='Add Tags'}</td></tr>
  <tr><td><input type="text" name="mytags" value="" size="50" maxlength="255"></td></tr>
  <tr><td colspan="2">{translate text='add_tag_note'}</td></tr>
  <tr><td>{translate text='Add a Note'}</td></tr>
  <tr><td><textarea name="notes" rows="3" cols="50"></textarea></td></tr>
  <tr><td><input type="submit" value="{translate text='Save'}"></td></tr>
  {/if}
</table>
</form>
</div>