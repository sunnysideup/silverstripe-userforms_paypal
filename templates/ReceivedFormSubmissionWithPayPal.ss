<% if BusinessEmail %>
<!-- see https://cms.paypal.com/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_html_formbasics -->
<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
	<input type="hidden" name="cmd" value="_xclick">
	<input type="hidden" name="business" value="$BusinessEmail" />
	<input type="hidden" name="return" value="$ReturnLink">
	<input type="hidden" name="custom" value="$Custom" />
	<% if CurrencyCode %>			<input type="hidden" name="currency_code" value="$CurrencyCode" /><% end_if %>
	<% if ProductName %>			<input type="hidden" name="item_name" value="$ProductName" /><% end_if %>
	<% if Amount %>						<input type="hidden" name="amount" value="$Amount" /><% end_if %>
	<% if FirstName %>				<input type="hidden" name="first_name" value="$FirstName" /><% end_if %>
	<% if Surname %>					<input type="hidden" name="last_name" value="$Surname" /><% end_if %>
	<% if Address1 %>					<input type="hidden" name="address1" value="$Address1" /><% end_if %>
	<% if Address2 %>					<input type="hidden" name="address2" value="$Address2" /><% end_if %>
	<% if City %>							<input type="hidden" name="city" value="$City" /><% end_if %>
	<% if State %>						<input type="hidden" name="state" value="$State" /><% end_if %>
	<% if Country %>					<input type="hidden" name="country" value="$Country" /><% end_if %>
	<% if Zip %>							<input type="hidden" name="zip" value="$Zip" /><% end_if %>
	<% if Email %>						<input type="hidden" name="email" value="$Email" /><% end_if %>
	<input type="submit" name="submit" value="$PaypalButtonLabel" />
</form>
<div id="BeforePaymentInstructions">$BeforePaymentInstructions</div>
<% else %>
<p><!-- Payments can not be completed because the business credentials have not beed entered. --></p>
<% end_if %>


