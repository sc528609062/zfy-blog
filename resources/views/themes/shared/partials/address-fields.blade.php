<label>收货人 <input name="recipient" value="{{ $address?->recipient }}" required maxlength="100" autocomplete="name"></label>
<label>联系电话 <input name="phone" value="{{ $address?->phone }}" required maxlength="30" autocomplete="tel"></label>
<label>省市区 <input name="region" value="{{ $address?->region }}" required maxlength="200"></label>
<label>详细地址 <input name="address" value="{{ $address?->address }}" required maxlength="500" autocomplete="street-address"></label>
<label>邮编 <input name="postal_code" value="{{ $address?->postal_code }}" maxlength="20" autocomplete="postal-code"></label>
<input type="hidden" name="is_default" value="0"><label><input type="checkbox" name="is_default" value="1" @checked($address?->is_default)>默认地址</label>
