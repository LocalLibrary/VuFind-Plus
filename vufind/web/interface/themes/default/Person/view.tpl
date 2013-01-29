{strip}
{if (isset($title)) }
<script type="text/javascript">
	alert("{$title}");
</script>
{/if}
<div id="page-content" class="content">
	{* Left Sidebar *}
	<div id="sidebar">
		{* Display Book Cover *}
		<div id = "clearcover"> 
			<div class="alignleft"> 
				<a href="{$path}/Person/{$summShortId}">
				{if $record.picture}
				<a target='_blank' href='{$path}/files/original/{$record.picture|escape}'><img src="{$path}/files/medium/{$record.picture|escape}" class="alignleft listResultImage" alt="{translate text='Picture'}"/></a><br />
				{else}
				<img src="{$path}/interface/themes/default/images/person.png" class="alignleft listResultImage" alt="{translate text='No Cover Image'}"/><br />
				{/if}
				</a>
			</div>	
		</div>
	</div>
	<div id="main-content">
		{if $error}<p class="error">{$error}</p>{/if} 

		<h1>
			{$record.firstName|escape} {$record.middleName|escape}{if $record.nickName} "{$record.nickName|escape}"{/if}{if $record.maidenName} ({$record.maidenName}){/if} {$record.lastName|escape}
			{if $userIsAdmin}
				<a href='{$path}/Admin/People?objectAction=edit&amp;id={$id}' ><span title='Edit this person' class='silk user_edit'>&nbsp;</span></a>
				<a href='{$path}/Admin/People?objectAction=delete&amp;id={$id}' onclick='return confirm("Removing this person will permanently remove them from the system.	Are you sure?")'><span title='Delete this person' class='silk user_delete'>&nbsp;</span></a>
			{/if}
		</h1>
		{if $record.otherName}
			<div class='personDetail'><span class='personDetailLabel'>Other Names:</span><span class='personDetailValue'>{$record.otherName|escape}</span></div>
		{/if}
		{if $birthDate}
			<div class='personDetail'><span class='personDetailLabel'>Birth Date:</span><span class='personDetailValue'>{$birthDate}</span></div>
		{/if}
		{if $deathDate}
			<div class='personDetail'><span class='personDetailLabel'>Death Date:</span><span class='personDetailValue'>{$deathDate}</span></div>
		{/if}
		{if $ageAtDeath}
			<div class='personDetail'><span class='personDetailLabel'>Age at Death:</span><span class='personDetailValue'>{$record.ageAtDeath|escape}</span></div>
		{/if}
		{if $record.veteranOf}
			{implode subject=$record.veteranOf glue=", " assign='veteranOf'} 
			<div class='personDetail'><span class='personDetailLabel'>Veteran Of:</span><span class='personDetailValue'>{$veteranOf}</span></div>
		{/if}
					
		{if count($marriages) > 0 || $userIsAdmin}
			<div class="blockhead">Marriages
			{if $userIsAdmin}
				<a href='{$path}/Admin/Marriages?objectAction=add&amp;personId={$id}' title='Add a Marriage'><span class='silk group_add' title='Add a Marriage'>&nbsp;</span></a>
			{/if}
			</div>
			{foreach from=$marriages item=marriage}
				<div class="marriageTitle">
					 {$marriage.spouseName}{if $marriage.formattedMarriageDate} - {$marriage.formattedMarriageDate}{/if}
					 {if $userIsAdmin}
							<a href='{$path}/Admin/Marriages?objectAction=edit&amp;id={$marriage.marriageId}' title='Edit this Marriage'><span class='silk group_edit' title='Edit this Marriage'>&nbsp;</span></a>
							<a href='{$path}/Admin/Marriages?objectAction=delete&amp;id={$marriage.marriageId}' title='Delete this Marriage' onclick='return confirm("Removing this marriage will permanently remove it from the system.	Are you sure?")'><span class='silk group_delete' title='Delete this Marriage'>&nbsp;</span></a>
					 {/if}
				</div>
				{if $marriage.comments}
					<div class="marriageComments">{$marriage.comments|escape}</div>
				{/if}
			{/foreach}
			
		{/if}
		{if $record.cemeteryName || $record.cemeteryLocation || $record.mortuaryName}
			<div class="blockhead">Burial Details</div>
			{if $record.cemeteryName}
			<div class='personDetail'><span class='personDetailLabel'>Cemetery Name:</span><span class='personDetailValue'>{$record.cemeteryName}</span></div>
			{/if}
			{if $record.cemeteryLocation}
			<div class='personDetail'><span class='personDetailLabel'>Cemetery Location:</span><span class='personDetailValue'>{$record.cemeteryLocation}</span></div>
			{/if}
			{if $person->addition || $person->lot || $person->block || $person->grave}
			<div class='personDetail'><span class='personDetailLabel'>Burial Location:</span>
			<span class='personDetailValue'>
				Addition {$person->addition}, Block {$person->block}, Lot {$person->lot}, Grave {$person->grave}
			</span></div>
			{if $person->tombstoneInscription}
			<div class='personDetail'><span class='personDetailLabel'>Tombstone Inscription:</span><div class='personDetailValue'>{$person->tombstoneInscription}</div></div>
			{/if}
			{/if}
			{if $record.mortuaryName}
			<div class='personDetail'><span class='personDetailLabel'>Mortuary Name:</span><span class='personDetailValue'>{$record.mortuaryName}</span></div>
			{/if}
		{/if}
		{if count($obituaries) > 0 || $userIsAdmin}
			<div class="blockhead">Obituaries
			{if $userIsAdmin}
				<a href='{$path}/Admin/Obituaries?objectAction=add&amp;personId={$id}' title='Add an Obituary'><img src='{$path}/images/silk/report_add.png' alt='Add a Marriage' /></a>
			{/if}
			</div>
			{foreach from=$obituaries item=obituary}
				<div class="obituaryTitle">
				{$obituary.source}{if $obituary.sourcePage} page {$obituary.sourcePage}{/if}{if $obituary.formattedObitDate} - {$obituary.formattedObitDate}{/if}
				{if $userIsAdmin}
					 <a href='{$path}/Admin/Obituaries?objectAction=edit&amp;id={$obituary.obituaryId}' title='Edit this Obituary'><span class='silk report_edit' title='Edit this Obituary'>&nbsp;</span></a>
					 <a href='{$path}/Admin/Obituaries?objectAction=delete&amp;id={$obituary.obituaryId}' title='Delete this Obituary' onclick='return confirm("Removing this obituary will permanently remove it from the system.	Are you sure?")'><span class='silk report_delete' title='Delete this Obituary'>&nbsp;</span></a>
				{/if}
				</div>
				{if $obituary.contents && $obituary.picture}
					<div class="obituaryText">{if $obituary.picture|escape}<a href='{$path}/files/original/{$obituary.picture|escape}'><img class='obitPicture' src='{$path}/files/medium/{$obituary.picture|escape}'/></a>{/if}{$obituary.contents|escape}</div>
					<div class="clearer"></div>
				{elseif $obituary.contents}
					<div class="obituaryText">{$obituary.contents|escape|replace:"\r":"<br/>"}</div>
					<div class="clearer"></div>
				{elseif $obituary.picture}
					<div class="obituaryPicture">{if $obituary.picture|escape}<a href='{$path}/files/original/{$obituary.picture|escape}'><img class='obitPicture' src='{$path}/files/medium/{$obituary.picture|escape}'/></a>{/if}</div>
					<div class="clearer"></div>
				{/if}
				
			{/foreach}
			
		{/if}
		<div class="blockhead">Comments</div>
		{if $record.comments}
		<div class='personComments'>{$record.comments|escape}</div>
		{else}
		<div class='personComments'>No comments found.</div>
		{/if}
	</div>
</div>
{/strip}