<?php
/**
 * simplified class aws s3 sdk on 24-jan-17 - fnx
 */

require 'aws_sdk/aws-autoloader.php';
include_once($_SERVER['DOCUMENT_ROOT']."/imgthumbresize/phpthumb.class.php"); //For Creating Image Thumbs

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception as S3;

class S3API{

	function __construct(){

		$this->s3init = new S3Client([
		    'version' => 'latest',
		    'region'  => 's3region',
		    'credentials' => array(
		        'key'    => '',
		        'secret' => '',
		    )
		]);
		$this->bucket = "fnxbucket"	;
		$this->error  = false;
		$this->tmpdir = sys_get_temp_dir().'/';
	}

	/**
	 * [Copy file from local to s3 bucket]
	 * @param  [string] $from   [ex: __DIR__/temp_scripts/ferfolder/uptest.xls]
	 * @param  [string] $to     [destination path, ex: s3fol1/s3fol2/uptest.xls]
	 * @return [s3object]       
	 */
	function copy($from,$to){ 
		$to = str_replace("//", "/", $to);
		$to = ltrim($to, "/");
		try{
			$this->error = false;
			$this->errorcode = '';

			return $this->s3init->putObject(['Bucket'=>$this->bucket,'Key'=>$to,'SourceFile'=>$from,'ACL'=>'public-read','ContentType' =>$this->getmimetype(pathinfo($from)['basename'])]);
		}
		catch(S3 $e){
			$this->error = true;
			$this->errorcode = $e->getstatusCode();
			return $e->getMessage();
		}

	}

	/**
	 * [List files in given bucket]
	 * @param  [type] $bucket [description]
	 * @return [s3object]
	 */
	function listfiles(){
		try{
			$this->error = false;
			$this->errorcode = '';
			return $this->s3init->listObjects(['Bucket'=> $this->bucket])['Contents'];
		}
		catch(S3 $e){
			$this->error = true;
			$this->errorcode = $e->getstatusCode();
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
			$this->errorcode = '';
			return $this->s3init->listBuckets([])['Buckets'];
		}
		catch(S3 $e){
			$this->error = true;
			$this->errorcode = $e->getstatusCode();
			return $e->getMessage();
		}
	}

	/**
	 * [get direct url of the file]
	 * @param  [string] $file   [filepath in bucket, ex: s3fol1/s3fol2/uptest.xls]
	 * @return [s3object]
	 */
	function geturl($file){
		try{
			$this->error = false;
			$this->errorcode = '';
			return $this->s3init->getObjectUrl($this->bucket,$file);
		}
		catch(S3 $e){
			$this->error = true;
			$this->errorcode = $e->getstatusCode();
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
			$this->errorcode = '';
			$tofile = ltrim(str_replace("//", "/", $tofile),"/");
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
	    catch(S3 $e){
			$this->error = true;
			$this->errorcode = $e->getstatusCode();
			return $e->getMessage();
		}
	}

	/**
	 * [save file locally from bucket]
	 * @param  [string] $file   [filepath in bucket, ex: s3fol1/s3fol2/uptest.xls]
	 * @param  [string] $saveto [localpath, ex: __DIR__/temp_files/ferdowntest.xls]
	 * @return [s3object]
	 */
	function savetolocal($file,$saveto){
		try{
			$this->error = false;
			$this->errorcode = '';
			return $this->s3init->getObject(["Bucket"=>$this->bucket,"Key"=>$file,"SaveAs"=>$saveto]);
		}
		catch(S3 $e){
			$this->error = true;
			$this->errorcode = $e->getstatusCode();
			return $e->getMessage();
		}
	}

	/**
	 * [download description]
	 * @param  [string] $file   [filepath in bucket, ex: s3fol1/s3fol2/uptest.xls]
	 * @param  [string] $_typ   ['D' for download (default) & 'I' for showing inline webpage]
	 * @return [s3object]
	 */
	function download($file,$_typ ='D'){
		try{
			$Dwntype = 'attachment';
			if(strtolower($_typ) == 'i' && in_array(strtolower(pathinfo($file,PATHINFO_EXTENSION)),['jpg','jpeg','bmp','png','gif']))
				$Dwntype = 'inline';
			$this->error = false;
			$this->errorcode = '';
			$_unq = uniqid();
			$_rs = $this->s3init->getObject(["Bucket"=>$this->bucket,"Key"=>$file,"SaveAs"=>$this->tmpdir.$_unq]);
			ob_clean();
			header("Content-Type: {$_rs['ContentType']}");
			header("Content-Disposition: $Dwntype; filename='".pathinfo($file)['basename']."'");
			readfile($this->tmpdir.$_unq);
			unlink($this->tmpdir.$_unq);
		}
		catch(S3 $e){
			$this->error = true;
			$this->errorcode = $e->getstatusCode();
			return $e->getMessage();
		}
	}

	/**
	 * [delete description]
	 * @param  [string] $file   [filepath in bucket, ex: s3fol1/s3fol2/uptest.xls]
	 * @return [s3object]
	 */
	function delete($file){
		$file = str_replace("//", "/", $file);
		try{
			$this->error = false;
			$this->errorcode = '';
			return $this->s3init->deleteObject(['Bucket'=>$this->bucket,'Key'=>$file]);
		}
		catch(S3 $e){
			$this->error = true;
			$this->errorcode = $e->getstatusCode();
			return $e->getMessage();
		}
	}

	/**
	 * [deletemultiple description]
	 * @param  [array] $filearray [an array of files to delete, ex:array(['Key'=>'/fol2/test.txt'],['Key'=>'/fol2/test2.txt']])
	 * @return [s3object]
	 */
	function deletemultiple($filearray){
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

	function resizeIMG($filearray){
		$_w = $filearray['w'];
		$_h = $filearray['h'];
		$_s3dir = $filearray['dir'];
		$_s3dirORG = $_s3dir;
		$_ORGimgdr = pathinfo($_s3dir,PATHINFO_DIRNAME);
		$_ORGimgfn = pathinfo($_s3dir,PATHINFO_BASENAME);

		try{
				$_ext   = pathinfo($_s3dir,PATHINFO_EXTENSION);
				$_fname = uniqid().'.'.$_ext;
				$_saved = uniqid().'.'.$_ext;
				$r      = $this->savetolocal($_s3dirORG,sys_get_temp_dir().'/'.$_fname);
				if(file_exists(sys_get_temp_dir().'/'.$_fname) && isset($r['@metadata']) && $r['@metadata']['effectiveUri'] != '')
				{
					$phpThumb = new phpThumb();
					$phpThumb->config_allow_src_above_docroot = true;
					$phpThumb->setSourceFilename(sys_get_temp_dir().'/'.$_fname);
	   				$phpThumb->setParameter('w', $_w);
	   				$phpThumb->setParameter('h', $_h);
	   				$out_fn = $this->tmpdir.$_saved;
				}
				else
				{
				    header("Content-Type: image/jpeg");
					header("Content-Disposition: inline; filename='noimage.jpg'");
					ob_clean();
					flush();
					readfile($_SERVER['DOCUMENT_ROOT'].'/error.jpg');
					return;
				}
			    if ($phpThumb->GenerateThumbnail()){
			        if ($phpThumb->RenderToFile($out_fn)){
			        	$_rscpy = $this->copy($out_fn,'img'.DIRECTORY_SEPARATOR.$_w.'x'.$_h.DIRECTORY_SEPARATOR.$_s3dir);
					if($_rscpy['@metadata']['statusCode'] == 200 && $_rscpy['ObjectURL'] != '')
			        	{
			        		$Fmime = $this->getmimetype($_fname);
			        		header("Content-Type: {$Fmime}");
							header("Content-Disposition: inline; filename='".$_fname."'");
							ob_clean();
							flush();
							readfile($out_fn);
							@unlink(sys_get_temp_dir().'/'.$_fname);
							@unlink($out_fn);
							return;
			        	}
			        }
			    }
		}
		catch(S3 $e){
			$this->error = true;
			$this->errorcode = $e->getstatusCode();
		}
	}
  
	/**
	 * [local func for getting listof files in local DIR]
	 * @param  [string] $folder  [local folder]
	 * @param  [referenced array]  &$finres [array which will be called by refenrence recursively]
	 * @return [s3object]
	 */
	 function getlocallist($folder, &$finres = []){
	    $files = scandir($folder);
	    foreach($files as $key => $value){
	        $_path = realpath($folder.'/'.$value);
	        if(!is_dir($_path)) $finres[] = $_path;
	        else if($value != "." && $value != "..") {
	            getlocallist($_path, $finres);
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
		$_rtnmime = $mime[strtolower(pathinfo($_fn, PATHINFO_EXTENSION))];
		$mime = null;
		return $_rtnmime;
	}

}
?>