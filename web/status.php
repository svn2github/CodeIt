<?php
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
        @session_start();
        require_once("./include/db_info.inc.php");
  $cache_time=2;
        require_once("./include/cache_start.php");
        
require_once("./include/my_func.inc.php");
require_once("./include/db_info.inc.php");
if(isset($OJ_LANG)){
                require_once("./lang/$OJ_LANG.php");
        }
require_once("./include/const.inc.php");

?>


<meta http-equiv='refresh' content='60'>
<title>Submission Status</title>
<center>

<?php $str2="";

$sql="SELECT solution_id,user_id,problem_id,result,memory,time,language,code_length,in_date FROM `solution` WHERE 1 ";
if (isset($_GET['cid'])){
        $cid=intval($_GET['cid']);
        $sql=$sql." AND `contest_id`='$cid' ";
        $str2=$str2."&cid=$cid";
        require_once("contest-header.php");
}else{
        require_once("oj-header.php");
}
?>
<div>
<?php $order_str=" ORDER BY `solution_id` DESC ";
$start_first=1;
// check the top arg
if (isset($_GET['top'])){
        $top=strval(intval($_GET['top']));
        if ($top!=-1) $sql=$sql."AND `solution_id`<='".$top."' ";
}

// check the problem arg
$problem_id="";
if (isset($_GET['problem_id'])){
        $problem_id=strval(intval($_GET['problem_id']));
        if ($problem_id!='0'){
                $sql=$sql."AND `problem_id`='".$problem_id."' ";
                $str2=$str2."&problem_id=".$problem_id;
        }
        else $problem_id="";
}
// check the user_id arg
$user_id="";
if (isset($_GET['user_id'])){
        $user_id=trim($_GET['user_id']);
        if (is_valid_user_name($user_id) && $user_id!=""){
                $sql=$sql."AND `user_id`='".$user_id."' ";
                if ($str2!="") $str2=$str2."&";
                $str2=$str2."user_id=".$user_id;
        }else $user_id="";
}
if (isset($_GET['language'])) $language=intval($_GET['language']);
else $language=-1;

if ($language>9 || $language<0) $language=-1;
if ($language!=-1){
        $sql=$sql."AND `language`='".strval($language)."' ";
        $str2=$str2."&language=".$language;
}
?>
<form id=simform action="status.php" method="get">
<?php echo $MSG_PROBLEM_ID?>:<input type=text size=4 name=problem_id value='<?php echo $problem_id?>'>
<?php echo $MSG_USER?>:<input type=text size=4 name=user_id value='<?php echo $user_id?>'>
<?php if (isset($cid)) echo "<input type='hidden' name='cid' value='$cid'>";?>
<?php echo $MSG_LANG?>:<select size="1" name="language">
<?php if (isset($_GET['language'])) $language=$_GET['language'];
else $language=-1;
if ($language<0||$language>9) $language=-1;
if ($language==-1) echo "<option value='-1' selected>All</option>";
else echo "<option value='-1'>All</option>";
for ($i=0;$i<10;$i++){
        if ($i==$language) echo "<option value=$i selected>$language_name[$i]</option>";
        else echo "<option value=$i>$language_name[$i]</option>";
}
?>
</select>
<?php echo $MSG_RESULT?>:<select size="1" name="jresult">
<?php if (isset($_GET['jresult'])) $jresult=intval($_GET['jresult']);
else $jresult=-1;
if ($jresult>=12||$jresult<0) $jresult=-1;
if ($jresult!=-1){
        $sql=$sql."AND `result`='".strval($jresult)."' ";
        $str2=$str2."&jresult=".strval($jresult);
}
if ($jresult==-1) echo "<option value='-1' selected>All</option>";
else echo "<option value='-1'>All</option>";
for ($j=0;$j<12;$j++){
        $i=($j+4)%12;
        if ($i==$jresult) echo "<option value='".strval($jresult)."' selected>".$judge_result[$i]."</option>";
        else echo "<option value='".strval($i)."'>".$judge_result[$i]."</option>"; 
}
echo "</select>";
?>
</select>

<?php if(isset($_SESSION['administrator'])||isset($_SESSION['contest_creator'])){
        if(isset($_GET['showsim']))
                $showsim=intval($_GET['showsim']);
        else
                $showsim=0;
        echo "SIM:
                        <select name=showsim onchange=\"document.getElementById('simform').submit();\">
                        <option value=0 ".($showsim==0?'selected':'').">All</option>
                        <option value=50 ".($showsim==50?'selected':'').">50</option>
                        <option value=60 ".($showsim==60?'selected':'').">60</option>
                        <option value=70 ".($showsim==70?'selected':'').">70</option>
                        <option value=80 ".($showsim==80?'selected':'').">80</option>
                        <option value=90 ".($showsim==90?'selected':'').">90</option>
                        <option value=100 ".($showsim==100?'selected':'').">100</option>
                  </select>";
/*      if (isset($_GET['cid'])) 
                echo "<input type=hidden name=cid value='".$_GET['cid']."'>";
        if (isset($_GET['language'])) 
                echo "<input type=hidden name=language value='".$_GET['language']."'>";
        if (isset($_GET['user_id'])) 
                echo "<input type=hidden name=user_id value='".$_GET['user_id']."'>";
        if (isset($_GET['problem_id'])) 
                echo "<input type=hidden name=problem_id value='".$_GET['problem_id']."'>";
        //echo "<input type=submit>";
*/
        
        
        
}
echo "<input type=submit value='$MSG_SEARCH'></form>";
?>
</div>
<table align=center>
<tr  class='toprow'>
<td ><?php echo $MSG_RUNID?>
<td ><?php echo $MSG_USER?>
<td ><?php echo $MSG_PROBLEM?>
<td ><?php echo $MSG_RESULT?>
<td ><?php echo $MSG_MEMORY?>
<td ><?php echo $MSG_TIME?>
<td ><?php echo $MSG_LANG?>
<td ><?php echo $MSG_CODE_LENGTH?>
<td ><?php echo $MSG_SUBMIT_TIME?>
</tr>
<?php if($OJ_SIM){
        $old=$sql;
        $sql="select * from ($sql order by solution_id desc limit 20) solution left join `sim` on solution.solution_id=sim.s_id WHERE 1 ";
        if(isset($_GET['showsim'])&&intval($_GET['showsim'])>0){
                $showsim=intval($_GET['showsim']);
                $sql="select * from ($old ) solution 
                     left join `sim` on solution.solution_id=sim.s_id WHERE result=4 and sim>=$showsim";
                $sql="SELECT * FROM ($sql) `solution`
                        left join(select solution_id old_s_id,user_id old_user_id from solution) old
                        on old.old_s_id=sim_s_id WHERE  old_user_id!=user_id and sim_s_id!=solution_id ";
                $str2.="&showsim=$showsim";
        }
        //$sql=$sql.$order_str." LIMIT 20";
}

$sql=$sql.$order_str." LIMIT 20";
//echo $sql;
if($OJ_MEMCACHE){
	require("./include/memcache.php");
	$result = mysql_query_cache($sql) or die("Error! ".mysql_error());
	$rows_cnt=count($result);
}else{
		
	$result = mysql_query($sql) or die("Error! ".mysql_error());
	$rows_cnt=mysql_num_rows($result);
}
$top=$bottom=-1;
$cnt=0;
if ($start_first){
        $row_start=0;
        $row_add=1;
}else{
        $row_start=$rows_cnt-1;
        $row_add=-1;
}


<<<<<<< .mine
for ($i=0;$i<$rows_cnt;$i++){
if($OJ_MEMCACHE)
	$row=$result[$i];
else
	$row=mysql_fetch_array($result);
        if ($top==-1) $top=$row[solution_id];
        $bottom=$row['solution_id'];
=======
//for ($i=0;$i<$rows_cnt;$i++){
//      mysql_data_seek($result,$row_start+$row_add*$i);
while(  $row=mysql_fetch_array($result)){
        if ($top==-1) $top=$row[0];
        $bottom=$row[0];
>>>>>>> .r1179
        if ($cnt) echo "<tr align=center class='oddrow'>";
        else echo "<tr align=center class='evenrow'>";
        $flag=(!is_running(intval($row['contest_id']))) ||
                        isset($_SESSION['source_browser']) ||
                        isset($_SESSION['administrator']) || 
<<<<<<< .mine
                        (isset($_SESSION['user_id'])&&!strcmp($row['user_id'],$_SESSION['user_id']));
=======
                        (isset($_SESSION['user_id'])&&!strcmp($row[1],$_SESSION['user_id']));
>>>>>>> .r1179
        $cnt=1-$cnt;
<<<<<<< .mine
        echo "<td>".$row['solution_id'];
        echo "<td><a href='userinfo.php?user=".$row['user_id']."'>".$row['user_id']."</a>";
=======
        echo "<td>".$row[0];
        echo "<td><a href='userinfo.php?user=".$row[1]."'>".$row[1]."</a>";
>>>>>>> .r1179
        if (isset($cid)) 
                echo "<td><a href='problem.php?cid=$cid&pid=$row->num'>".$PID[$row->num]."</a>";
        else 
<<<<<<< .mine
                echo "<td><a href='problem.php?id=".$row['problem_id']."'>".$row['problem_id']."</a>";
        if (intval($row['result'])==11 && ((isset($_SESSION['user_id'])&&$row['user_id']==$_SESSION['user_id']) || isset($_SESSION['source_browser']))){
                echo "<td><a href='ceinfo.php?sid=".$row['solution_id']."' class=".$judge_color[$row['result']].">".$judge_result[$row['result']]."</a>";
        }else if (intval($row['result'])==10 && ((isset($_SESSION['user_id'])&&$row['user_id']==$_SESSION['user_id']) || isset($_SESSION['source_browser']))){
                echo "<td><a href='reinfo.php?sid=".$row['solution_id']."' class=".$judge_color[$row['result']].">".$judge_result[$row['result']]."</a></td>";
=======
                echo "<td><a href='problem.php?id=".$row[2]."'>".$row[2]."</a>";
        if (intval($row[3])==11 && ((isset($_SESSION['user_id'])&&$row[1]==$_SESSION['user_id']) || isset($_SESSION['source_browser']))){
                echo "<td><a href='ceinfo.php?sid=$row[0]' class=".$judge_color[$row[3]].">".$judge_result[$row[3]]."</a>";
        }else if (intval($row[3])==10 && ((isset($_SESSION['user_id'])&&$row[1]==$_SESSION['user_id']) || isset($_SESSION['source_browser']))){
                echo "<td><a href='reinfo.php?sid=$row[0]' class=".$judge_color[$row[3]].">".$judge_result[$row[3]]."</a></td>";
>>>>>>> .r1179
        }else{
<<<<<<< .mine
                if($OJ_SIM&&$row['sim']&&$row['sim_s_id']!=$row->s_id) {
                        echo "<td><span class=".$judge_color[$row['result']].">*".$judge_result[$row['result']]."</span>-<span class=red>";
=======
                if($OJ_SIM&&$row[11]&&$row[10]!=$row[9]) {
                        echo "<td><span class=".$judge_color[$row[3]].">*".$judge_result[$row[3]]."</span>-<span class=red>";
>>>>>>> .r1179
                        if( isset($_SESSION['source_browser'])){
<<<<<<< .mine
                                        echo "<a href=showsource.php?id=".$row['sim_s_id']." target=original>".$row['sim_s_id']."(".$row['sim']."%)</a>";
=======
                                        echo "<a href=showsource.php?id=".$row[10]." target=original>".$row[10]."(".$row[11]."%)</a>";
>>>>>>> .r1179
                        }else{
<<<<<<< .mine
                                        echo $row['sim_s_id'];
=======
                                        echo $row[10];
>>>>>>> .r1179
                        }
                        if(isset($_GET['showsim'])&&isset($row[13])){
                                        echo "$row[13]";
                                
                        }
                        echo     "</span>";
                }else{
<<<<<<< .mine
                        echo "<td class=".$judge_color[$row['result']].">".$judge_result[$row['result']];
=======
                        echo "<td class=".$judge_color[$row[3]].">".$judge_result[$row[3]];
>>>>>>> .r1179
                }
                
        }
        if ($flag){

<<<<<<< .mine
                if ($row['result']>=4){
                        echo "<td class=red>".$row['memory'];
                        echo "<td class=red>".$row['time'];
=======
                if ($row[3]>=4){
                        echo "<td class=red>".$row[4];
                        echo "<td class=red>".$row[5];
>>>>>>> .r1179
                }else{
                        echo "<td>------<td>------";
                }
<<<<<<< .mine
                if (!(isset($_SESSION['user_id'])&&strtolower($row['user_id'])==strtolower($_SESSION['user_id']) || isset($_SESSION['source_browser']))){
                        echo "<td>".$language_name[$row['language']];
=======
                if (!(isset($_SESSION['user_id'])&&strtolower($row[1])==strtolower($_SESSION['user_id']) || isset($_SESSION['source_browser']))){
                        echo "<td>".$language_name[$row[6]];
>>>>>>> .r1179
                }else{
<<<<<<< .mine
                        echo "<td><a target=_blank href=showsource.php?id=".$row['solution_id'].">".$language_name[$row['language']]."</a>/";
=======
                        echo "<td><a target=_blank href=showsource.php?id=".$row[0].">".$language_name[$row[6]]."</a>/";
>>>>>>> .r1179
                        if (isset($cid)) {
<<<<<<< .mine
                                echo "<a target=_self href=\"submitpage.php?cid=".$cid."&pid=$row->num&sid=".$row['solution_id']."\">Edit</a>";
=======
                                echo "<a target=_self href=\"submitpage.php?cid=".$cid."&pid=$row->num&sid=".$row[0]."\">Edit</a>";
>>>>>>> .r1179
                        }else{
<<<<<<< .mine
                                echo "<a target=_self href=\"submitpage.php?id=".$row['problem_id']."&sid=".$row['solution_id']."\">Edit</a>";
=======
                                echo "<a target=_self href=\"submitpage.php?id=".$row[2]."&sid=".$row[0]."\">Edit</a>";
>>>>>>> .r1179
                        }
                }
<<<<<<< .mine
                echo "<td>".$row['code_length']." B";
=======
                echo "<td>".$row[7]." B";
>>>>>>> .r1179
                
<<<<<<< .mine
        }else echo "<td>------<td>------<td>".$language_name[$row['language']]."<td>------";
        echo "<td>".$row['in_date'];
=======
        }else echo "<td>------<td>------<td>".$language_name[$row[6]]."<td>------";
        echo "<td>".$row[8];
>>>>>>> .r1179
        echo "</tr>";
}
mysql_free_result($result);
?>
</table>
<?php echo "[<a href=status.php?".$str2.">Top</a>]&nbsp;&nbsp;";
if (isset($_GET['prevtop']))
        echo "[<a href=status.php?".$str2."&top=".$_GET['prevtop'].">Previous Page</a>]&nbsp;&nbsp;";
else
        echo "[<a href=status.php?".$str2."&top=".($top+20).">Previous Page</a>]&nbsp;&nbsp;";
echo "[<a href=status.php?".$str2."&top=".$bottom."&prevtop=$top>Next Page</a>]";
?>

<?php require_once("oj-footer.php");?>
<?php require_once("./include/cache_end.php");?>
