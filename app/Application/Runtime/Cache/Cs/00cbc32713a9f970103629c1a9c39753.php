<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>广彩管理平台 </title>
    <link rel="stylesheet" href="/Static/Public/Cs/frame/layui/css/layui.css">
    <link rel="stylesheet" href="/Static/Public/Cs/css/style.css">
    <link rel="icon" href="/Static/Public/Cs/image/code.png">
</head>
<body class="login-body body">

<div class="login-box">
    <form class="layui-form layui-form-pane">
        <div class="layui-form-item">
            <h3>广彩管理平台</h3>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label">账号：</label>

            <div class="layui-input-inline">
                <input type="text" name="username" class="layui-input" lay-verify="required" placeholder="登录账号" autocomplete="on" maxlength="20" value="" />
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label">密码：</label>

            <div class="layui-input-inline">
                <input type="password" name="passwd" class="layui-input" lay-verify="required" placeholder="登录密码" maxlength="20" value="">
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label">验证码：</label>

            <div class="layui-input-inline">
                <input type="number" name="captcha_code" class="layui-input" lay-verify="required" placeholder="4位数" maxlength="4" style="width: 80px" /><a id="kanbuq" href="javascript:reCaptcha();"><img id="captcha" src="<?php echo U('Login/verifyCode');?>" alt="点击更换" style="width: 100px;height: 36px"></a>
            </div>
        </div>
        <!-- <div class="layui-form-item">
    		<label class="layui-form-label">记住登录</label>
    		<div class="layui-input-block">
      			<input type="checkbox" name="remember" lay-skin="switch"  lay-text="记住|不记">
    		</div>
  		</div> -->
        <div class="layui-form-item">
            <button type="reset" class="layui-btn layui-btn-danger btn-reset">重置</button>
            <button type="submit" class="layui-btn btn-submit" lay-submit="" lay-filter="login">立即登录</button>
        </div>
    </form>
    <div class="layui-footer" style="height: 30px">
    	<p>请使用非IE浏览器或IE10以上</p>
    </div>
</div>
<script type="text/javascript">
// location.href = '/Cs/Index/index';
</script>
<script type="text/javascript" src="/Static/Public/Cs/frame/layui/layui.js"></script>
<script type="text/javascript">
/* 解决登录页被嵌套问题js */
    var _topWin = window;
    while (_topWin != _topWin.parent.window) {
        _topWin = _topWin.parent.window;
    }
    if (window != _topWin)_topWin.document.location.href = "/";

function reCaptcha(){
    var img = document.getElementById("captcha");
	img.src = "/Cs/Login/verifyCode.html?rnd=" + Math.random();
}
    layui.use(['form', 'layer'], function () {
        var $ = layui.jquery,form = layui.form(),layer = layui.layer;
        // 验证
        form.verify({
            username: function (value) {
                if (value == "") {
                    return "请输入用户名";
                }
            },
            passwd: function (value) {
                if (value == "") {
                    return "请输入密码";
                }
            },
            captcha_code: function (value) {
                if (value == "") {
                    return "请输入验证码";
                }
            }
        });
        // 提交监听
        form.on('submit(login)', function (data) {
        	layer.msg("正在登录...",{time:1800});
        	$.post("<?php echo U('Login/login');?>",data.field,function(res){
                if(res.code > 0){
                    layer.msg(res.msg,{time:1800,icon: 1},function(){
                        location.href = res.url;
                    });
                }else{
                    layer.msg(res.msg,{time:1800,icon: 5});
                    reCaptcha();
                }
            },'json');
            return false;
        })
        
    })

</script>
</body>
</html>