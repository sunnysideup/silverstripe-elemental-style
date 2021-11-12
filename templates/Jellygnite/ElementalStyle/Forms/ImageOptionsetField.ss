<div $AttributesHTML <% include SilverStripe/Forms/AriaAttributes %>>
	<% loop $Options %>
		<div class="radio form-check $Class">
			<input class="form-check-input" id="$ID" name="$Name" type="radio" value="$Value"<% if $isChecked %> checked<% end_if %><% if $isDisabled %> disabled<% end_if %> <% if $Up.Required %>required<% end_if %> />
			<% if $Object.Value %><label for="$ID" data-es-image=""><div style="{$Object.Value}"></div></label><% end_if %>
			<label class="form-check-label" for="$ID">$Title</label>
		</div>
	<% end_loop %>
</div>