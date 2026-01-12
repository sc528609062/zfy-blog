<?php
/**
* 注册页面
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
 
<div class="lowin">

		<div class="lowin-wrapper">
			<div class="lowin-box lowin-register">
				<div class="lowin-box-inner">
				    <div class="typecho-login" style="display: none;"></div>
				    
					<form action="<?php $this->options->registerAction();?>" method="post" name="register" role="form" class="sign__form">
					    <input type="hidden" name="_" value="<?php echo $security->getToken($this->request->getRequestUrl());?>">
					    <input type="hidden" name="referer" value="<?php $this->options->siteUrl(); ?>">
						<p>注册账号</p>
						<?php if($this->options->allowRegister): ?>
						<div class="lowin-group">
							<label>用户名</label>
							<input type="text" name="name" autocomplete="name" class="lowin-input" id="author">
						</div>
						<div class="lowin-group">
							<label>邮箱地址</label>
							<input type="mail" autocomplete="mail" name="mail" class="lowin-input" id="mail">
						</div>
						<div class="lowin-group">
							<label>密码</label>
							<input type="password" name="password" autocomplete="current-password" class="lowin-input" id="password">
						</div>
						<div class="lowin-group">
							<label>确认密码</label>
							<input type="password" name="confirm" autocomplete="current-password" class="lowin-input" id="confirm">
						</div>
						<button class="lowin-btn" type="submit" name="loginsubmit" value="true">
							注册
						</button>
						<div class="text-foot">
							已经有一个帐户? <a href="<?php $this->options->siteUrl(); ?><?php if ($this->options->rewrite==0): ?>index.php/<?php endif; ?><?php $this->options->denglu(); ?>.html" class="login-link">登录</a>
						</div>
                        <?php else: ?>
                        <div class="text-foot">
						Registration is closed
						</div>
						<div class="text-foot">
							注册已经关闭
						</div>
						<?php endif; ?>  
					</form>
				</div>
			</div>
		</div>
	</div>
<?php $this->need('footer.php'); ?>
<link rel="stylesheet" href="<?php echo stcdn($this->options->themeUrl); ?>/src/css/popup.css">
<?php require_once("libs/common-js.php");?>
