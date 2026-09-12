<option value="monthly">月度 ¥{{ $level->price_monthly }}</option>
<option value="quarterly">季度 ¥{{ bcmul((string) $level->price_monthly, '3', 2) }}</option>
<option value="yearly">年度 ¥{{ $level->price_yearly }}</option>
@if($level->price_lifetime !== null)<option value="lifetime">永久 ¥{{ $level->price_lifetime }}</option>@endif
