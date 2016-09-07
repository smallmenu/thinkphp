<?php
    if(C('LAYOUT_ON')) {
        echo '{__NOLAYOUT__}';
    }
?>
<!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="UTF-8">
    <title>跳转提示</title>
</head>
<body>
<div class="system-message">
    <?php if(isset($message)) {?>
    <p class="success"><?php echo($message); ?></p>
    <?php }else{?>
    <p class="error"><?php echo($error); ?></p>
    <?php }?>
    <p class="detail"></p>
    <p class="jump">
        页面自动 <a id="href" href="<?php echo($jumpUrl); ?>">跳转</a> 等待时间： <b id="wait"><?php echo($waitSecond); ?></b>
    </p>
</div>
<script type="text/javascript">
    (function(){
        var wait = document.getElementById('wait'),href = document.getElementById('href').href;
        var interval = setInterval(function(){
            var time = --wait.innerHTML;
            if(time <= 0) {
                location.href = href;
                clearInterval(interval);
            };
        }, 1000);
    })();
</script>
</body>
</body>
</html>