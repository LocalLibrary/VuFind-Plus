{if (isset($title)) }
<script type="text/javascript">
	alert("{$title}");
</script>
{/if}
<div id="page-content" class="content">
	<div id="sidebar">
		{include file="MyResearch/menu.tpl"}
		{include file="Admin/menu.tpl"}
	</div>

	<div id="main-content">
	{if $user}
		<div class="myAccountTitle">{translate text='Available Holds From OverDrive'}</div>
		{if $userNoticeFile}
			{include file=$userNoticeFile}
		{/if}

		{if count($overDriveHolds.available) > 0}
			<div class='sortOptions'>
				Hide Covers <input type="checkbox" onclick="$('.imageColumnOverdrive').toggle();"/>
			</div>

			<div class='holdSectionBody'>
				<table class="myAccountTable">
					<thead>
						<tr><th class='imageColumnOverdrive'></th><th>Title</th><th>Notification Sent</th><th>Expires</th></tr>
					</thead>
					<tbody>
					{foreach from=$overDriveHolds.available item=record}
						<tr>
							<td rowspan="{$record.numRows}" class='imageColumnOverdrive'><img src="{$record.imageUrl}"></td>
							<td>
								{if $record.recordId != -1}<a href="{$path}/EcontentRecord/{$record.recordId}/Home">{/if}{$record.title}{if $record.recordId != -1}</a>{/if}
								{if $record.subTitle}<br/>{$record.subTitle}{/if}
								{if strlen($record.author) > 0}<br/>by: {$record.author}{/if}
							</td>
							<td>{$record.notificationDate|date_format}</td>
							<td>{$record.expirationDate|date_format}</td>
						</tr>
						{foreach from=$record.formats item=format}
							<tr>
								<td colspan="2">{$format.name}</td>
								<td>
									<a href="#" onclick="checkoutOverDriveItem('{$format.overDriveId}','{$format.formatId}')" class="button">Check&nbsp;Out</a><br/>
								</td>
							</tr>
						{/foreach}
					{/foreach}
					</tbody>
				</table>
			</div>
		{else}
			<p>You do not have any holds that are ready for pickup from OverDrive.</p>
		{/if}
	{else}
		You must login to view this information. Click <a href="{$path}/MyResearch/Login">here</a> to login.
	{/if}
	</div>
</div>
