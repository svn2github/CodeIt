<?
session_start();
//������֤��ͼƬ
Header("Content-type: image/PNG");
$im = imagecreate(44,18);
$back = ImageColorAllocate($im, rand(240,255),rand(240,255),rand(240,255));
imagefill($im,0,0,$back); //����
srand((double)microtime()*1000000);
//����4λ����
for($i=0;$i<4;$i++){
$font = ImageColorAllocate($im, rand(100,255),rand(0,100),rand(100,255));
$authnum=rand(1,9);
$vcodes.=$authnum;
imagestring($im, 5, 2+$i*10, 1, $authnum, $font);
}
for($i=0;$i<180;$i++) //�����������
{ 
$randcolor = ImageColorallocate($im,rand(0,255),rand(0,255),rand(0,255));
imagesetpixel($im, rand()%70 , rand()%30 , $randcolor);
} 
ImagePNG($im);
ImageDestroy($im);
$_SESSION['VCODE'] = $vcodes;
?>