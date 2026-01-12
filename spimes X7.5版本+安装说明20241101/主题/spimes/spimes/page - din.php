<?php
/**
* 登录页面
*
* @package custom
*/

include 'libs/common.php';

if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>

<?php $this->need('header.php'); ?>
<style>.top-bar,.site-footer{ display:none; }</style>
<?php if($this->user->hasLogin()): ?>
<script language="javascript" type="text/javascript">
window.location.href='<?php $this->options->siteUrl(); ?>';
</script>  
<?php endif; ?>

<style>
body { background: #E7EBF0 url(<?php echo stcdn($this->options->themeUrl); ?>/src/images/bg-1.jpg); } 
.popup{ z-index: 999 !important;}
</style>
<link rel="stylesheet" href="<?php echo stcdn($this->options->themeUrl); ?>/src/css/auth.css">

<div class="lowin" >
   
        
		<div class="lowin-wrapper">
			<div class="lowin-box lowin-login">
				<div class="lowin-box-inner">
				    <div class="typecho-login" style="display: none;"></div>
					<form action="<?php $this->options->loginAction()?>" method="post" name="login" rold="form">
					    
					    <input type="hidden" name="referer" value="<?php $this->options->siteUrl(); ?>">
						<p>用户登录</p>
						<div class="lowin-group">
							<label><i class="ri-account-box-line ri-lg"></i> 账号/邮箱 <a href="#" class="login-back-link">Sign in?</a></label>
							
							<input type="text" class="lowin-input" name="name" autocomplete="username" placeholder="请输入邮箱/账号" required>
						</div>
						<div class="lowin-group password-group">
							<label><i class="ri-lock-line ri-lg"></i> 密码</label>
							
							<input type="password" class="lowin-input" name="password" autocomplete="current-password" placeholder="请输入密码" required/>
						</div>
						<button class="lowin-btn login-btn" type="submit">
							登录
						</button>

						<div class="text-foot">
							还没有一个帐号? <a href="<?php $this->options->siteUrl(); ?><?php if ($this->options->rewrite==0): ?>index.php/<?php endif; ?><?php $this->options->zhuce(); ?>.html" class="register-link">注册</a> or   <a href="<?php $this->options->siteUrl(); ?>Deng/forgot" class="forgot-link">忘记密码?</a>
						</div>
					
					</form>
				</div>
			</div>


		</div>

	</div>


<?php $this->need('footer.php'); ?>

<link rel="stylesheet" href="<?php echo stcdn($this->options->themeUrl); ?>/src/css/popup.css">
<?php
require_once("libs/common-js.php");
?>
