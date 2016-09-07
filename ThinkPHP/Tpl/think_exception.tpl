<?php
    if(C('LAYOUT_ON')) {
        echo '{__NOLAYOUT__}';
    }
?>
<!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="UTF-8">
    <title>系统错误</title>
</head>
<body>
<div class="error">
    <div><?php echo strip_tags($e['message']);?></div>
    <div class="content">
        <?php if(isset($e['file'])) {?>
        <div class="info">
        <div class="title">
        <h4>错误位置</h4>
        </div>
        <div class="text">
        <p>FILE: <?php echo $e['file'] ;?> &#12288;LINE: <?php echo $e['line'];?></p>
        </div>
        </div>
        <?php }?>
        <?php if(isset($e['trace'])) {?>
        <div class="info">
        <div class="title">
        <h4>TRACE</h4>
        </div>
        <div class="text">
        <p><?php echo nl2br($e['trace']);?></p>
        </div>
        </div>
        <?php }?>
    </div>
</div>
</body>
</html>