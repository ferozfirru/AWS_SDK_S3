<?php 
/**
 * simplified class aws s3 sdk on 24-jan-17
 * by fenix
 */


require 'aws_sdk/aws-autoloader.php'; // download aws sdk for php
use Aws\S3\S3Client;

class S3API{

	function __construct(){

		$this->s3init = new S3Client([
		    'version' => 'latest',
		    'region'  => 'ap-southeast-1',
		    'credentials' => array(
		        'key'    => 'YOURKEY',
		        'secret' => 'YOURSECREYKEY',
		    )
		]);
		$this->error = false;
	}

	/**
	 * [Copy file from local to s3 bucket]
	 * @param  [string] $from   [ex: __DIR__/temp_scripts/ferfolder/uptest.xls]
	 * @param  [string] $to     [destination path, ex: s3fol1/s3fol2/uptest.xls]
	 * @param  [string] $bucket [bucket name]
	 * @return [s3object]       
	 */
	function copy($from,$to,$bucket){
		try{
			$this->error = false;
			return $this->s3init->putObject(['Bucket'=>$bucket,'Key'=>$to,'SourceFile'=>$from,'ACL'=>'public-read','ContentType' =>$this->getmimetype(pathinfo($from)['basename'])]);
		}
		catch(Exception $e){
			$this->error = true;
			return $e->getMessage();
		}

	}

	/**
	 * [List files in given bucket]
	 * @param  [type] $bucket [description]
	 * @return [s3object]
	 */
	function listfiles($bucket){
		try{
			$this->error = false;
			return $this->s3init->listObjects(['Bucket'=> $bucket])['Contents'];
		}
		catch(Exception $e){
			$this->error = true;
			return $e->getMessage();
		}
	}

	/**
	 * [list all available buckets]
	 * @return [s3object]
	 */
	function listbuckets(){
		try{
			$this->error = false;
			return $this->s3init->listBuckets([])['Buckets'];
		}
		catch(Exception $e){
			$this->error = true;
			return $e->getMessage();
		}
	}

	/**
	 * [get direct url of the file]
	 * @param  [string] $file   [filepath in bucket, ex: s3fol1/s3fol2/uptest.xls]
	 * @param  [string] $bucket [bucket name]
	 * @return [s3object]
	 */
	function geturl($file,$bucket){
		try{
			$this->error = false;
			return $this->s3init->getObjectUrl($bucket,$file);
		}
		catch(Exception $e){
			$this->error = true;
			return $e->getMessage();
		}
	}

	/**
	 * [copy file within diffrent buckets]
	 * @param  [string] $fromfile   [file from 1st bucket, ex: s3fol1/s3fol2/uptest.xls]
	 * @param  [string] $tofile     [filepath for 2nd bucket, ex: s3fol1/s3fol2/uptest2.xls]
	 * @param  [string] $frombucket [1st bucket name]
	 * @param  [string] $tobucket   [2nd bucketname]
	 * @return [s3object]
	 */
	function copyremote($fromfile,$tofile,$frombucket,$tobucket){
		try{
			$this->error = false;
			return $this->s3init->copyObject(
		        array(
		        	'ACL'=>'public-read',
		        	'ContentType' =>$this->getmimetype(pathinfo($tofile)['basename']),
		            'Bucket' => $tobucket,
		            'Key' => $tofile,
		            'CopySource' => urlencode($frombucket . '/' . $fromfile)
		        )
		    );
	    }
	    catch(Exception $e){
			$this->error = true;
			return $e->getMessage();
		}
	}

	/**
	 * [save file locally from bucket]
	 * @param  [string] $file   [filepath in bucket, ex: s3fol1/s3fol2/uptest.xls]
	 * @param  [string] $saveto [localpath, ex: __DIR__/temp_files/ferdowntest.xls]
	 * @param  [string] $bucket [bucketname]
	 * @return [s3object]
	 */
	function savetolocal($file,$saveto,$bucket){
		try{
			$this->error = false;
			return $this->s3init->getObject(["Bucket"=>$bucket,"Key"=>$file,"SaveAs"=>$saveto]);
		}
		catch(Exception $e){
			$this->error = true;
			return $e->getMessage();
		}
	}

	/**
	 * [download description]
	 * @param  [string] $file   [filepath in bucket, ex: s3fol1/s3fol2/uptest.xls]
	 * @param  [string] $bucket [bucketname]
	 * @param  [string] $_typ   ['D' for download (default) & 'I' for showing inline webpage]
	 * @return [s3object]
	 */
	function download($file,$bucket,$_typ ='D'){
		try{
			$Dwntype = 'attachment';
			if(strtolower($_typ) == 'i')
				$Dwntype = 'inline';
			$this->error = false;
			$_unq = uniqid();
			$_rs = $this->s3init->getObject(["Bucket"=>$bucket,"Key"=>$file,"SaveAs"=>'/tmp/'.$_unq]);
			ob_clean();
			header("Content-Type: {$_rs['ContentType']}");
			header("Content-Disposition: $Dwntype; filename='".pathinfo($file)['basename']."'");
			readfile('/tmp/'.$_unq);
			unlink('/tmp/'.$_unq);
		}
		catch(Exception $e){
			$this->error = true;
			return $e->getMessage();
		}
	}


	/**
	 * [uploadDIR upload entire directory from local to S3 bucket]
	 * @param  [string] $file   [local dir path]
	 * @param  [string] $bucket [bucketname]
	 * @return [object]         [transfer object]
	 */
	function uploadDIR($file,$bucket){
		$source = $file;
		$dest = 's3://'.$bucket.'/testfol';
		$manager = new \Aws\S3\Transfer($this->s3init, $source, $dest);
		return $manager->transfer();
	}


	/**
	 * [DownloadDIR Download entire directory to S3 from local]
	 * @param  [string] $file   [local dir path]
	 * @param  [string] $bucket [bucketname]
	 * @return [object]         [transfer object]
	 */
	function DownloadDIR($file,$bucket){
		$source = 's3://'.$bucket.'/testfol';
		$dest = $file;
		$manager = new \Aws\S3\Transfer($this->s3init, $source, $dest);
		return $manager->transfer();
	}

	/**
	 * [delete description]
	 * @param  [string] $file   [filepath in bucket, ex: s3fol1/s3fol2/uptest.xls]
	 * @param  [string] $bucket [bucketname]
	 * @return [s3object]
	 */
	function delete($file,$bucket){
		try{
			$this->error = false;
			return $this->s3init->deleteObject(['Bucket'=>$bucket,'Key'=>$file]);
		}
		catch(Exception $e){
			$this->error = true;
			return $e->getMessage();
		}
	}

	/**
	 * [deletemultiple description]
	 * @param  [array] $filearray [an array of files to delete, ex:array(['Key'=>'/fol2/test.txt'],['Key'=>'/fol2/test2.txt']])
	 * @param  [string] $bucket    [bucketname]
	 * @return [s3object]
	 */
	function deletemultiple($filearray,$bucket){
		try{
			$this->error = false;
			$this->errorcode = '';
			if(!is_array($filearray))
			{
				$this->error = true;
				$this->errorcode = $e->getstatusCode();
				return "Param should be a Array type";
			}else{
		foreach($filearray as $_afil){
			$__filearray[] = ['Key'=>$_afil];
		}
			}
			return $this->s3init->deleteObjects(['Bucket'=>$this->bucket,'Objects'=>$__filearray]);
		}
		catch(S3 $e){
			$this->error = true;
			$this->errorcode = $e->getstatusCode();
			return $e->getMessage();
		}
	}
  
	/**
	 * [local func for getting listof files in local DIR]
	 * @param  [string] $folder  [local folder]
	 * @param  [referenced array]  &$finres [array which will be called by refenrence recursively]
	 * @return [s3object]
	 */
	private function getlist($folder, &$finres = []){
	    $files = scandir($folder);
	    foreach($files as $key => $value){
	        $_path = realpath($folder.'/'.$value);
	        if(!is_dir($_path)) $finres[] = $_path;
	        else if($value != "." && $value != "..") {
	            getlist($_path, $finres);
	            //$finres[] = $_path;
	        }
	    }
	    return $finres;
	}

	/**
	 * [local func for returning mime type for header downloads]
	 * @param  [string] $_fn [file name]
	 * @return [s3object]
	 */
	private function getmimetype($_fn)
	{

		$mime = ['jpg'=> 'image/jpeg','jpeg'=> 'image/jpeg','jpe'=> 'image/jpeg','gif'=> 'image/gif','png'=> 'image/png','bmp'=> 'image/bmp','tif'=> 'image/tiff','tiff'=> 'image/tiff','ico'=> 'image/x-icon','txt'=> 'text/plain','h'  => 'text/plain','csv'=> 'text/csv','tsv'=> 'text/tab-separated-values','ics'=> 'text/calendar','rtx'=> 'text/richtext','css'=> 'text/css','htm'=> 'text/html','html'=> 'text/html','mp3'=> 'audio/mpeg','m4b'=> 'audio/mpeg','m4a'=> 'audio/mpeg','ra' => 'audio/x-realaudio','ram'=> 'audio/x-realaudio','wav'=> 'audio/wav','ogg'=> 'audio/ogg','oga'=> 'audio/ogg','mid'=> 'audio/midi','midi'=> 'audio/midi','wma'=> 'audio/x-ms-wma','wax'=> 'audio/x-ms-wax','mka'=> 'audio/x-matroska','rtf'=> 'application/rtf','js' => 'application/javascript','pdf'=> 'application/pdf','swf'=> 'application/x-shockwave-flash','class'  => 'application/java','tar'=> 'application/x-tar','zip'=> 'application/zip','gz' => 'application/x-gzip','gzip'=> 'application/x-gzip','rar'=> 'application/rar','7z' => 'application/x-7z-compressed','doc'=> 'application/msword','pot'=> 'application/vnd.ms-powerpoint','ppt'=> 'application/vnd.ms-powerpoint','pps'=> 'application/vnd.ms-powerpoint','wri'=> 'application/vnd.ms-write','xla'=> 'application/vnd.ms-excel','xlt'=> 'application/vnd.ms-excel','xlw'=> 'application/vnd.ms-excel','xls'=> 'application/vnd.ms-excel','mdb'=> 'application/vnd.ms-access','mpp'=> 'application/vnd.ms-project','docx'=> 'application/vnd.openxmlformats-officedocument.wordprocessingml.document','docm'=> 'application/vnd.ms-word.document.macroEnabled.12','dotx'=> 'application/vnd.openxmlformats-officedocument.wordprocessingml.template','dotm'=> 'application/vnd.ms-word.template.macroEnabled.12','xlsx'=> 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','xlsm'=> 'application/vnd.ms-excel.sheet.macroEnabled.12','xlsb'=> 'application/vnd.ms-excel.sheet.binary.macroEnabled.12','xltx'=> 'application/vnd.openxmlformats-officedocument.spreadsheetml.template','xltm'=> 'application/vnd.ms-excel.template.macroEnabled.12','xlam'=> 'application/vnd.ms-excel.addin.macroEnabled.12','pptx'=> 'application/vnd.openxmlformats-officedocument.presentationml.presentation','pptm'=> 'application/vnd.ms-powerpoint.presentation.macroEnabled.12','ppsx'=> 'application/vnd.openxmlformats-officedocument.presentationml.slideshow','ppsm'=> 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12','potx'=> 'application/vnd.openxmlformats-officedocument.presentationml.template','potm'=> 'application/vnd.ms-powerpoint.template.macroEnabled.12','ppam'=> 'application/vnd.ms-powerpoint.addin.macroEnabled.12','sldx'=> 'application/vnd.openxmlformats-officedocument.presentationml.slide','sldm'=> 'application/vnd.ms-powerpoint.slide.macroEnabled.12','onetoc' => 'application/onenote','onetmp' => 'application/onenote','onepkg' => 'application/onenote','onetoc2'=> 'application/onenote','odt'=> 'application/vnd.oasis.opendocument.text','odp'=> 'application/vnd.oasis.opendocument.presentation','ods'=> 'application/vnd.oasis.opendocument.spreadsheet','odg'=> 'application/vnd.oasis.opendocument.graphics','odc'=> 'application/vnd.oasis.opendocument.chart','odb'=> 'application/vnd.oasis.opendocument.database','odf'=> 'application/vnd.oasis.opendocument.formula','wp' => 'application/wordperfect','wpd'=> 'application/wordperfect','key'=> 'application/vnd.apple.keynote','numbers'=> 'application/vnd.apple.numbers','pages'  => 'application/vnd.apple.pages'];
		$_rtnmime = $mime[pathinfo($_fn, PATHINFO_EXTENSION)];
		$mime = null;
		return $_rtnmime;
	}

	/**
	* S3resize
	* if the url given to this function is like this 'img/800x600/foodimage/test.jpg' if images exists then it'll show the image in in-line but if thumbimage not found
	* then it will check in foodimage/test.jpg for original image and resizes it and will store the resized image again in S3 as img/800x600/foodimage/test.jpg
	*/

	function S3resize($url){
		$orgurl = $url;
		$re = '/(img\/([\d]+|[\d]+x[\d]+)\/)(.*.(?:\.jpg|\.jpeg|\.bmp|\.png|\.gif))/i';
		$url = trim(str_replace($_SERVER['HTTP_HOST'],'', $url),'/');

		if(!preg_match($re,trim($url,'/'),$m))
		{
			$this->error = 'Path should be like img/wxh/folder/../filename.ext'; // img/800x600/images/test.jpg
			return false;
		}

		$wh = explode('x', $m[2]);
		$_finarray['w'] = $wh[0];
		$_finarray['h'] = $wh[1];
		$_finarray['dir'] = $m[3];
		$_finarray['org'] = $m[0];
		$this->resizeIMG($_finarray);
	}


	function resizeIMG($filearray){

		$_w = $filearray['w'];
		$_h = $filearray['h'];
		$_s3dir = $filearray['dir'];
		$_ORGimgdr = pathinfo($_s3dir,PATHINFO_DIRNAME);
		$_ORGimgfn = pathinfo($_s3dir,PATHINFO_BASENAME);
		try{
			 $_rs = $this->download($filearray['org'],'I');
			 if($this->errorcode == 404)
			 {
				$_ext = pathinfo($_s3dir,PATHINFO_EXTENSION);
				$_fname = uniqid().'.'.$_ext;
				$_saved = uniqid().'.'.$_ext;
				$r = $this->savetolocal($_s3dir,sys_get_temp_dir().'/'.$_fname);

				$phpThumb = new phpThumb();
				$phpThumb->config_allow_src_above_docroot = true;
				$phpThumb->setSourceFilename(sys_get_temp_dir().'/'.$_fname);
   				$phpThumb->setParameter('w', $_w);
   				$phpThumb->setParameter('h', $_h);

   				$out_fn = $this->tmpdir.$_saved;

			    if ($phpThumb->GenerateThumbnail()){

			        if ($phpThumb->RenderToFile($out_fn)){
			        	$_rscpy = $this->copy($out_fn,'img'.DIRECTORY_SEPARATOR.$_w.'x'.$_h.DIRECTORY_SEPARATOR.$_s3dir);
			        	if($_rscpy['@metadata']['statusCode'] == 200 && $_rscpy['ObjectURL'] <> '')
			        	{
			        		$Fmime = $this->getmimetype($_fname);
			        		header("Content-Type: {$Fmime}");
							header("Content-Disposition: inline; filename='".$_fname."'");
							ob_clean();
							flush();
							readfile($out_fn);
							return;
			        	}
			        }
			    }
			}
		}




}
?>
