
<div id=foot>
﻿<!--<br>

<a href="./"><span class=red>HOME</span></a>
<a href="javascript:history.go(-1)"><span class=red>Back</span></a>

<hr>
-->
<center>
	<p>
	<?php if(file_exists("setlang.php")){?>
		<a href=setlang.php?lang=ko>한국어</a>&nbsp;
		<a href=setlang.php?lang=cn>中文</a>&nbsp;
		<a href=setlang.php?lang=fa>فارسی</a>&nbsp;
		<a href=setlang.php?lang=en>English</a>&nbsp;
		<a href=setlang.php?lang=th>ไทย</a>
	<?php }?>
		<br>
		All Copyright Reserved 2010-2011 <a href='<?php echo $OJ_HOME?>'><?php echo $OJ_NAME?></a> TEAM<br>
		<a href=gpl-2.0.txt><span class=green>GPL2.0</span></a> 2003-2011 <a href='http://code.google.com/p/hustoj/'>HUSTOJ Project</a> TEAM<br>
		Anything about the Problems, Please Contact Admin:<a href="mailto:<?php echo $OJ_ADMIN?>">admin</a><br>
		
	</p>
</center>
</div><!--end foot-->
</div><!--end main-->
</body>
</html>
<?php require_once('./include/cache_end.php');?>
